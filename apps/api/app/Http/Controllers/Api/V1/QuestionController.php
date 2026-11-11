<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Http\Resources\QuestionResource;
use App\Models\AuditLog;
use App\Models\Question;
use App\Models\QuestionSeries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Question::class);

        $questions = Question::query()
            ->with(['series', 'author'])
            ->when($request->string('status')->isNotEmpty(), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->string('q')->isNotEmpty(), function (Builder $query) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(fn (Builder $builder) => $builder->where('title', 'like', $term)->orWhere('prompt', 'like', $term));
            })
            ->latest('updated_at')
            ->paginate(min(max($request->integer('per_page', 100), 1), 100));

        return QuestionResource::collection($questions);
    }

    public function show(string $code, int $revision): QuestionResource
    {
        Gate::authorize('viewAny', Question::class);

        return QuestionResource::make($this->findRevision($code, $revision));
    }

    public function store(StoreQuestionRequest $request): QuestionResource
    {
        Gate::authorize('create', Question::class);
        $data = $request->validated();
        $question = DB::transaction(function () use ($data, $request): Question {
            $series = QuestionSeries::create([
                'code' => 'Q-'.Str::upper(Str::random(12)),
                'created_by' => $request->user()->id,
            ]);
            $series->update(['code' => 'Q-'.str_pad((string) $series->id, 4, '0', STR_PAD_LEFT)]);

            return $this->createRevision($series, 1, $data, $request);
        });

        AuditLog::record($request, 'question.created', 'Question', (string) $question->id, null, $this->auditState($question), $data['reason'] ?? 'Soal dibuat');

        return QuestionResource::make($question->load(['series', 'author']));
    }

    public function update(UpdateQuestionRequest $request, string $code, int $revision): QuestionResource
    {
        $question = $this->findRevision($code, $revision);
        Gate::authorize('update', $question);
        $data = $request->validated();
        $before = $this->auditState($question);

        $question->fill($this->questionAttributes($data));
        if ($request->hasFile('image')) {
            $question->image_path = $request->file('image')->store('questions', 'public');
        } elseif ($request->boolean('removeImage')) {
            $question->image_path = null;
        }

        if (($data['status'] ?? 'Draf') === 'Pemeriksaan') {
            Gate::authorize('transition', [$question, 'Pemeriksaan']);
            $question->status = 'Pemeriksaan';
            $question->submitted_at = now();
        }
        $question->save();

        AuditLog::record($request, 'question.updated', 'Question', (string) $question->id, $before, $this->auditState($question), $data['reason'] ?? 'Soal diperbarui');

        return QuestionResource::make($question->load(['series', 'author']));
    }

    public function revise(StoreQuestionRequest $request, string $code): QuestionResource
    {
        Gate::authorize('create', Question::class);
        $series = QuestionSeries::query()->where('code', $code)->firstOrFail();
        $source = $series->revisions()->firstOrFail();
        abort_unless(in_array($source->status, ['Terbit', 'Arsip'], true), 422, 'Revisi baru hanya dapat dibuat dari soal terbit atau arsip.');
        $data = $request->validated();
        $revision = DB::transaction(function () use ($series, $source, $data, $request): Question {
            $nextRevision = (int) $series->revisions()->max('revision') + 1;
            $revision = $this->createRevision($series, $nextRevision, $data, $request);
            if (! $request->hasFile('image') && empty($data['removeImage']) && $source->image_path) {
                $revision->update(['image_path' => $source->image_path]);
            }

            return $revision;
        });

        AuditLog::record($request, 'question.revised', 'Question', (string) $revision->id, null, $this->auditState($revision), $data['reason'] ?? 'Revisi soal dibuat');

        return QuestionResource::make($revision->load(['series', 'author']));
    }

    public function destroy(Request $request, string $code, int $revision)
    {
        $question = $this->findRevision($code, $revision);
        Gate::authorize('delete', $question);
        $series = $question->series;
        $before = $this->auditState($question);
        $question->delete();
        if (! $series->revisions()->exists()) {
            $series->delete();
        }

        AuditLog::record($request, 'question.deleted', 'Question', (string) $question->id, $before, null, $request->string('reason')->toString() ?: 'Draf soal dihapus');

        return response()->noContent();
    }

    public function transition(Request $request, string $code, int $revision)
    {
        $question = $this->findRevision($code, $revision);
        $data = $request->validate([
            'status' => ['required', 'in:Draf,Pemeriksaan,Disetujui,Terbit,Arsip'],
            'reason' => ['required_if:status,Draf', 'nullable', 'string', 'max:500'],
        ]);
        Gate::authorize('transition', [$question, $data['status']]);
        $before = $this->auditState($question);
        $question->status = $data['status'];

        if ($data['status'] === 'Pemeriksaan') {
            $question->submitted_at = now();
            $question->review_reason = null;
        } elseif ($data['status'] === 'Draf') {
            $question->reviewer_id = $request->user()->id;
            $question->review_reason = $data['reason'];
        } elseif ($data['status'] === 'Disetujui') {
            $question->reviewer_id = $request->user()->id;
            $question->approved_by = $request->user()->id;
            $question->approved_at = now();
            $question->review_reason = null;
        } elseif ($data['status'] === 'Terbit') {
            $question->published_at = now();
        } elseif ($data['status'] === 'Arsip') {
            $question->archived_at = now();
        }
        $question->save();

        AuditLog::record($request, 'question.status_changed', 'Question', (string) $question->id, $before, $this->auditState($question), $data['reason'] ?? 'Status soal diubah');

        return QuestionResource::make($question->load(['series', 'author']));
    }

    private function findRevision(string $code, int $revision): Question
    {
        return Question::query()
            ->with(['series', 'author'])
            ->where('revision', $revision)
            ->whereHas('series', fn (Builder $query) => $query->where('code', $code))
            ->firstOrFail();
    }

    private function createRevision(QuestionSeries $series, int $revision, array $data, Request $request): Question
    {
        $attributes = $this->questionAttributes($data);
        $attributes['question_series_id'] = $series->id;
        $attributes['revision'] = $revision;
        $attributes['author_id'] = $request->user()->id;
        $attributes['status'] = $data['status'] ?? 'Draf';
        $attributes['submitted_at'] = $attributes['status'] === 'Pemeriksaan' ? now() : null;
        $attributes['image_path'] = $request->hasFile('image')
            ? $request->file('image')->store('questions', 'public')
            : null;

        return Question::create($attributes);
    }

    private function questionAttributes(array $data): array
    {
        return [
            'title' => $data['title'],
            'format' => $data['format'],
            'education_level' => $data['educationLevel'] ?? 'SMP/MTs',
            'subject' => $data['subject'] ?? 'Matematika',
            'framework_version' => $data['frameworkVersion'] ?? '2025.1',
            'topic' => $data['topic'],
            'subelement' => $data['subelement'] ?? $data['topic'],
            'cognitive_level' => $data['level'],
            'prompt' => $data['prompt'],
            'options' => $data['options'] ?? [],
            'answer_data' => [
                'answerKey' => $data['answerKey'] ?? null,
                'correctOptions' => $data['correctOptions'] ?? [],
                'categoryStatements' => $data['categoryStatements'] ?? [],
                'categoryAnswers' => $data['categoryAnswers'] ?? [],
            ],
            'explanation' => $data['explanation'],
            'misconception' => $data['commonMistakes'] ?? null,
            'tags' => $data['tags'] ?? [],
        ];
    }

    private function auditState(Question $question): array
    {
        return [
            'code' => $question->series?->code,
            'revision' => $question->revision,
            'title' => $question->title,
            'status' => $question->status,
            'format' => $question->format,
            'topic' => $question->subelement,
        ];
    }
}
