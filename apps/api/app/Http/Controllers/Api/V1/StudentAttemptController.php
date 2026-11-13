<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PackageItem;
use App\Models\Question;
use App\Models\StudentAccess;
use App\Models\StudentAccount;
use App\Models\StudentAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentAttemptController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $attempts = StudentAttempt::query()->with('testPackage.series')->where('student_account_id', $request->user('student')->id)->latest()->paginate(30);

        return response()->json(['data' => $attempts->through(fn (StudentAttempt $attempt): array => $this->summary($attempt))]);
    }

    public function start(Request $request, int $accessId): JsonResponse
    {
        $data = $request->validate(['idempotencyKey' => ['nullable', 'string', 'max:120']]);
        $key = $data['idempotencyKey'] ?? $request->header('Idempotency-Key');
        if (! is_string($key) || trim($key) === '' || mb_strlen($key) > 120) {
            throw ValidationException::withMessages(['idempotencyKey' => 'Idempotency-Key wajib berupa teks maksimal 120 karakter agar percobaan aman diulang.']);
        }
        $key = trim($key);
        $student = $request->user('student');

        $attempt = DB::transaction(function () use ($request, $student, $accessId, $key): StudentAttempt {
            $access = StudentAccess::query()->with('testPackage.items.question.series')
                ->where('student_account_id', $student->id)->lockForUpdate()->findOrFail($accessId);

            $duplicate = StudentAttempt::query()->where('idempotency_key', $key)->first();
            if ($duplicate) {
                abort_unless($duplicate->student_account_id === $student->id && $duplicate->student_access_id === $access->id, 409, 'Idempotency-Key telah dipakai untuk percobaan lain.');

                return $duplicate->load('testPackage.series', 'responses.question.series');
            }

            $inProgress = $access->attempts()->where('status', 'In progress')->latest('id')->first();
            if ($inProgress) {
                if (! $inProgress->deadline_at?->isPast()) {
                    return $inProgress->load('testPackage.series', 'responses.question.series');
                }
                $this->submitAttempt($request, $inProgress, 'Timer habis');
            }

            abort_unless($access->status === 'Aktif' && $access->starts_at->isPast(), 403, 'Hak akses belum aktif.');
            abort_if($access->expires_at?->isPast(), 403, 'Masa akses sudah berakhir.');
            abort_if($access->attempt_quota !== null && $access->attempts_used >= $access->attempt_quota, 403, 'Kuota percobaan sudah habis.');
            abort_unless($access->testPackage && $access->testPackage->status === 'Terbit', 404, 'Paket latihan tidak tersedia.');

            $now = now();
            $attempt = StudentAttempt::query()->create([
                'student_account_id' => $student->id,
                'student_access_id' => $access->id,
                'test_package_id' => $access->test_package_id,
                'idempotency_key' => $key,
                'status' => 'In progress',
                'started_at' => $now,
                'deadline_at' => $access->testPackage->duration_minutes ? $now->copy()->addMinutes($access->testPackage->duration_minutes) : null,
                'total_points' => $access->testPackage->items->count(),
            ]);
            $access->increment('attempts_used');
            StudentAccount::query()->whereKey($student->id)->increment('attempts_used');
            $access->testPackage->increment('attempts');

            return $attempt->load('testPackage.series');
        });

        return response()->json(['data' => $this->attemptData($attempt)], 201);
    }

    public function show(Request $request, int $attemptId): JsonResponse
    {
        $attempt = StudentAttempt::query()->with(['testPackage.series', 'testPackage.items.question.series', 'responses.question.series'])
            ->where('student_account_id', $request->user('student')->id)->findOrFail($attemptId);

        if ($attempt->status === 'In progress' && $attempt->deadline_at?->isPast()) {
            $this->submitAttempt($request, $attempt, 'Timer habis');
            $attempt->refresh()->load(['testPackage.series', 'testPackage.items.question.series', 'responses.question.series']);
        }

        return response()->json(['data' => $this->attemptData($attempt)]);
    }

    public function answer(Request $request, int $attemptId, int $questionId): JsonResponse
    {
        $data = $request->validate(['answer' => ['present']]);
        $saved = DB::transaction(function () use ($request, $attemptId, $questionId, $data): bool {
            $attempt = StudentAttempt::query()->with('testPackage.items')->where('student_account_id', $request->user('student')->id)
                ->lockForUpdate()->findOrFail($attemptId);
            abort_unless($attempt->status === 'In progress', 422, 'Percobaan sudah dikumpulkan.');
            if ($attempt->deadline_at?->isPast()) {
                $this->submitAttempt($request, $attempt, 'Timer habis');

                return false;
            }

            $question = $attempt->testPackage->items->firstWhere('question_id', $questionId)?->question;
            abort_unless($question instanceof Question, 404, 'Soal tidak termasuk dalam paket ini.');

            $answer = $data['answer'];
            if (is_array($answer)) {
                abort_if(count($answer) > 20, 422, 'Jawaban melebihi batas pilihan yang diizinkan.');
            } elseif (is_string($answer)) {
                abort_if(mb_strlen($answer) > 4000, 422, 'Jawaban terlalu panjang.');
            }

            $correct = $this->isCorrect($question, $answer);
            $attempt->responses()->updateOrCreate(
                ['question_id' => $question->id],
                ['response' => ['answer' => $answer], 'is_correct' => $correct, 'score_points' => $correct ? 1 : 0, 'answered_at' => now()],
            );

            return true;
        });

        if (! $saved) {
            return response()->json(['message' => 'Waktu habis dan percobaan dikumpulkan otomatis.'], 422);
        }

        return response()->json(['saved' => true, 'answeredAt' => now()->toISOString()]);
    }

    public function submit(Request $request, int $attemptId): JsonResponse
    {
        $attempt = StudentAttempt::query()->with(['testPackage.items.question', 'responses'])
            ->where('student_account_id', $request->user('student')->id)->findOrFail($attemptId);

        if ($attempt->status === 'In progress') {
            $this->submitAttempt($request, $attempt, $attempt->deadline_at?->isPast() ? 'Timer habis' : 'Dikumpulkan murid');
        }

        $attempt->refresh()->load(['testPackage.series', 'testPackage.items.question.series', 'responses.question.series']);

        return response()->json(['data' => $this->attemptData($attempt)]);
    }

    private function submitAttempt(Request $request, StudentAttempt $attempt, string $reason): void
    {
        DB::transaction(function () use ($request, $attempt, $reason): void {
            $locked = StudentAttempt::query()->lockForUpdate()->with(['testPackage.items.question', 'responses'])
                ->findOrFail($attempt->id);
            if ($locked->status !== 'In progress') {
                return;
            }

            foreach ($locked->testPackage->items as $item) {
                $response = $locked->responses->firstWhere('question_id', $item->question_id);
                if (! $response) {
                    $locked->responses()->create([
                        'question_id' => $item->question_id,
                        'response' => null,
                        'is_correct' => false,
                        'score_points' => 0,
                    ]);
                }
            }

            $score = (int) $locked->responses()->where('is_correct', true)->count();
            $total = $locked->testPackage->items()->count();
            $locked->forceFill([
                'status' => 'Submitted',
                'submitted_at' => now(),
                'score_points' => $score,
                'total_points' => $total,
                'score_percent' => $total > 0 ? round($score * 100 / $total, 2) : 0,
            ])->save();

            AuditLog::record($request, 'attempt.submitted', 'StudentAttempt', (string) $locked->id, null, [
                'scorePoints' => $score,
                'totalPoints' => $total,
                'scorePercent' => $locked->score_percent,
            ], $reason);
        });
    }

    private function isCorrect(Question $question, mixed $answer): bool
    {
        $key = $question->answer_data ?? [];

        return match ($question->format) {
            'Pilihan ganda' => is_numeric($answer) && (int) $answer === (int) ($key['answerKey'] ?? -1),
            'PG kompleks · MCMA' => is_array($answer) && $this->sameSet($answer, $key['correctOptions'] ?? []),
            'PG kompleks · Kategori' => is_array($answer) && array_values($answer) === array_values($key['categoryAnswers'] ?? []),
            default => false,
        };
    }

    private function sameSet(array $submitted, array $correct): bool
    {
        if (count($submitted) !== count(array_unique($submitted)) || count($correct) !== count(array_unique($correct))) {
            return false;
        }

        $submitted = array_map('intval', $submitted);
        $correct = array_map('intval', $correct);
        sort($submitted);
        sort($correct);

        return $submitted === $correct;
    }

    private function summary(StudentAttempt $attempt): array
    {
        return [
            'id' => $attempt->id,
            'package' => $attempt->testPackage?->name,
            'status' => $attempt->status,
            'startedAt' => $attempt->started_at?->toISOString(),
            'submittedAt' => $attempt->submitted_at?->toISOString(),
            'scorePoints' => $attempt->score_points,
            'totalPoints' => $attempt->total_points,
            'scorePercent' => (float) $attempt->score_percent,
        ];
    }

    private function attemptData(StudentAttempt $attempt): array
    {
        $items = $attempt->testPackage?->items ?? collect();
        $responses = $attempt->responses->keyBy('question_id');

        return [
            ...$this->summary($attempt),
            'deadlineAt' => $attempt->deadline_at?->toISOString(),
            'remainingSeconds' => $attempt->deadline_at ? max(0, now()->diffInSeconds($attempt->deadline_at, false)) : null,
            'answers' => $items->map(fn (PackageItem $item): array => [
                'questionId' => $item->question?->series?->code,
                'recordId' => $item->question_id,
                'revision' => $item->question?->revision,
                'title' => $item->question?->title,
                'prompt' => $item->question?->prompt,
                'format' => $item->question?->format,
                'options' => $item->question?->options ?? [],
                'imageUrl' => $item->question?->image_path ? asset('storage/'.$item->question->image_path) : null,
                'response' => $responses->get($item->question_id)?->response['answer'] ?? null,
                ...($attempt->status === 'Submitted' ? [
                    'isCorrect' => (bool) $responses->get($item->question_id)?->is_correct,
                    'scorePoints' => (int) ($responses->get($item->question_id)?->score_points ?? 0),
                    'answerKey' => $item->question?->answer_data,
                    'explanation' => $item->question?->explanation,
                ] : []),
            ])->values(),
        ];
    }
}
