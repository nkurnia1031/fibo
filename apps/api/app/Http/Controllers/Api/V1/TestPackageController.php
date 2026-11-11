<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTestPackageRequest;
use App\Http\Requests\UpdateTestPackageRequest;
use App\Http\Resources\TestPackageResource;
use App\Models\AuditLog;
use App\Models\PackageItem;
use App\Models\PackageSeries;
use App\Models\Question;
use App\Models\TestPackage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class TestPackageController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', TestPackage::class);

        $packages = TestPackage::query()
            ->with(['series', 'items.question.series'])
            ->when($request->string('status')->isNotEmpty(), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->string('q')->isNotEmpty(), fn (Builder $query) => $query->where('name', 'like', '%'.$request->string('q')->toString().'%'))
            ->latest('updated_at')
            ->paginate(min(max($request->integer('per_page', 100), 1), 100));

        return TestPackageResource::collection($packages);
    }

    public function show(string $code, int $version): TestPackageResource
    {
        Gate::authorize('viewAny', TestPackage::class);

        return TestPackageResource::make($this->findVersion($code, $version));
    }

    public function store(StoreTestPackageRequest $request): TestPackageResource
    {
        Gate::authorize('create', TestPackage::class);
        $data = $request->validated();
        $questions = $this->resolvePublishedQuestions($data['questionItems']);
        $this->ensureSameFramework($questions);

        $package = DB::transaction(function () use ($data, $questions, $request): TestPackage {
            $series = PackageSeries::create([
                'code' => 'PK-'.Str::upper(Str::random(12)),
                'created_by' => $request->user()->id,
            ]);
            $series->update(['code' => 'PK-'.str_pad((string) $series->id, 3, '0', STR_PAD_LEFT)]);

            return $this->createVersion($series, 1, $data, $questions, $request);
        });

        AuditLog::record($request, 'package.created', 'TestPackage', (string) $package->id, null, $this->auditState($package), $data['reason'] ?? 'Paket dibuat');

        return TestPackageResource::make($package->load(['series', 'items.question.series']));
    }

    public function update(UpdateTestPackageRequest $request, string $code, int $version): TestPackageResource
    {
        $package = $this->findVersion($code, $version);
        Gate::authorize('update', $package);
        $data = $request->validated();
        $questions = $this->resolvePublishedQuestions($data['questionItems']);
        $this->ensureSameFramework($questions);
        $before = $this->auditState($package);

        DB::transaction(function () use ($package, $data, $questions): void {
            $first = $questions->first();
            $package->fill($this->packageAttributes($data, $first));
            $package->items()->delete();
            $this->replaceItems($package, $questions);

            if (($data['status'] ?? 'Draf') === 'Pemeriksaan') {
                Gate::authorize('transition', [$package, 'Pemeriksaan']);
                $package->status = 'Pemeriksaan';
                $package->submitted_at = now();
            }
            $package->save();
        });

        AuditLog::record($request, 'package.updated', 'TestPackage', (string) $package->id, $before, $this->auditState($package), $data['reason'] ?? 'Paket diperbarui');

        return TestPackageResource::make($package->load(['series', 'items.question.series']));
    }

    public function revise(StoreTestPackageRequest $request, string $code): TestPackageResource
    {
        Gate::authorize('create', TestPackage::class);
        $series = PackageSeries::query()->where('code', $code)->firstOrFail();
        $source = $series->versions()->firstOrFail();
        abort_unless(in_array($source->status, ['Terbit', 'Arsip'], true), 422, 'Revisi paket hanya dapat dibuat dari paket terbit atau arsip.');
        $data = $request->validated();
        $questions = $this->resolvePublishedQuestions($data['questionItems']);
        $this->ensureSameFramework($questions);

        $package = DB::transaction(function () use ($series, $data, $questions, $request): TestPackage {
            $version = (int) $series->versions()->max('version') + 1;

            return $this->createVersion($series, $version, $data, $questions, $request);
        });

        AuditLog::record($request, 'package.revised', 'TestPackage', (string) $package->id, null, $this->auditState($package), $data['reason'] ?? 'Revisi paket dibuat');

        return TestPackageResource::make($package->load(['series', 'items.question.series']));
    }

    public function destroy(Request $request, string $code, int $version)
    {
        $package = $this->findVersion($code, $version);
        Gate::authorize('delete', $package);
        $before = $this->auditState($package);
        $package->delete();
        if (! $package->series->versions()->exists()) {
            $package->series->delete();
        }

        AuditLog::record($request, 'package.deleted', 'TestPackage', (string) $package->id, $before, null, $request->string('reason')->toString() ?: 'Draf paket dihapus');

        return response()->noContent();
    }

    public function transition(Request $request, string $code, int $version)
    {
        $package = $this->findVersion($code, $version);
        $data = $request->validate([
            'status' => ['required', 'in:Draf,Pemeriksaan,Disetujui,Terbit,Arsip'],
            'reason' => ['required_if:status,Draf', 'nullable', 'string', 'max:500'],
        ]);
        Gate::authorize('transition', [$package, $data['status']]);
        $before = $this->auditState($package);
        $package->status = $data['status'];

        if ($data['status'] === 'Pemeriksaan') {
            $package->submitted_at = now();
            $package->review_reason = null;
        } elseif ($data['status'] === 'Draf') {
            $package->reviewer_id = $request->user()->id;
            $package->review_reason = $data['reason'];
        } elseif ($data['status'] === 'Disetujui') {
            $package->reviewer_id = $request->user()->id;
            $package->approved_by = $request->user()->id;
            $package->approved_at = now();
            $package->review_reason = null;
        } elseif ($data['status'] === 'Terbit') {
            $package->published_at = now();
        } elseif ($data['status'] === 'Arsip') {
            $package->archived_at = now();
        }
        $package->save();

        AuditLog::record($request, 'package.status_changed', 'TestPackage', (string) $package->id, $before, $this->auditState($package), $data['reason'] ?? 'Status paket diubah');

        return TestPackageResource::make($package->load(['series', 'items.question.series']));
    }

    private function findVersion(string $code, int $version): TestPackage
    {
        return TestPackage::query()
            ->with(['series', 'items.question.series'])
            ->where('version', $version)
            ->whereHas('series', fn (Builder $query) => $query->where('code', $code))
            ->firstOrFail();
    }

    private function createVersion(PackageSeries $series, int $version, array $data, Collection $questions, Request $request): TestPackage
    {
        $first = $questions->first();
        $package = TestPackage::create([
            ...$this->packageAttributes($data, $first),
            'package_series_id' => $series->id,
            'version' => $version,
            'author_id' => $request->user()->id,
            'status' => $data['status'] ?? 'Draf',
            'submitted_at' => ($data['status'] ?? '') === 'Pemeriksaan' ? now() : null,
        ]);
        $this->replaceItems($package, $questions);

        return $package;
    }

    private function packageAttributes(array $data, ?Question $firstQuestion): array
    {
        return [
            'name' => $data['name'],
            'education_level' => $data['educationLevel'] ?? $firstQuestion?->education_level ?? 'SMP/MTs',
            'subject' => $data['subject'] ?? $firstQuestion?->subject ?? 'Matematika',
            'framework_version' => $data['frameworkVersion'] ?? $firstQuestion?->framework_version ?? '2025.1',
            'duration_minutes' => ($data['duration'] ?? 0) > 0 ? $data['duration'] : null,
        ];
    }

    private function resolvePublishedQuestions(array $items): Collection
    {
        $questions = collect($items)->map(function (array $item): Question {
            return Question::query()
                ->with('series')
                ->where('revision', $item['revision'])
                ->where('status', 'Terbit')
                ->whereHas('series', fn (Builder $query) => $query->where('code', $item['questionId']))
                ->firstOrFail();
        });

        if ($questions->count() !== $questions->unique('id')->count()) {
            abort(422, 'Soal yang sama tidak dapat dimasukkan dua kali.');
        }

        return new Collection($questions->all());
    }

    private function ensureSameFramework(Collection $questions): void
    {
        $frameworks = $questions->map(fn (Question $question): string => implode('|', [
            $question->education_level,
            $question->subject,
            $question->framework_version,
        ]))->unique();

        if ($frameworks->count() > 1) {
            abort(422, 'Semua soal dalam satu paket harus memakai jenjang, mata pelajaran, dan versi kerangka yang sama.');
        }
    }

    private function replaceItems(TestPackage $package, Collection $questions): void
    {
        foreach ($questions->values() as $position => $question) {
            PackageItem::create([
                'test_package_id' => $package->id,
                'question_id' => $question->id,
                'position' => $position + 1,
            ]);
        }
    }

    private function auditState(TestPackage $package): array
    {
        return [
            'code' => $package->series?->code,
            'version' => $package->version,
            'name' => $package->name,
            'status' => $package->status,
            'questions' => $package->items()->count(),
        ];
    }
}
