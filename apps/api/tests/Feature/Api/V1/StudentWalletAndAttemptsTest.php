<?php

namespace Tests\Feature\Api\V1;

use App\Models\AccessOffer;
use App\Models\AuditLog;
use App\Models\PackageItem;
use App\Models\PackageSeries;
use App\Models\PaymentMethod;
use App\Models\PointBundle;
use App\Models\PointTransaction;
use App\Models\Question;
use App\Models\QuestionSeries;
use App\Models\StudentAccess;
use App\Models\StudentAccount;
use App\Models\StudentAttempt;
use App\Models\TestPackage;
use App\Models\TopUpRequest;
use App\Models\User;
use App\Notifications\StudentPasswordReset;
use App\Notifications\StudentVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class StudentWalletAndAttemptsTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_registers_with_signup_bonus_verifies_and_cannot_use_staff_guard(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/student/register', [
            'name' => 'Alya Murid',
            'email' => 'ALYA@example.test',
            'password' => 'AlyaSecurePass-123',
            'password_confirmation' => 'AlyaSecurePass-123',
        ])->assertCreated()
            ->assertJsonPath('data.email', 'alya@example.test')
            ->assertJsonPath('data.pointBalance', 100)
            ->assertJsonPath('data.emailVerified', false);

        $student = StudentAccount::query()->where('email', 'alya@example.test')->firstOrFail();
        $this->assertDatabaseHas('point_transactions', [
            'student_account_id' => $student->id,
            'type' => 'signup_bonus',
            'points_delta' => 100,
        ]);
        Notification::assertSentTo($student, StudentVerifyEmail::class);

        $this->postJson('/api/v1/student/login', [
            'email' => 'alya@example.test',
            'password' => 'AlyaSecurePass-123',
        ])->assertOk()->assertJsonPath('emailVerified', false);
        $this->postJson('/api/v1/student/top-ups', [])->assertForbidden();

        $this->getJson('/api/v1/auth/me')->assertUnauthorized();

        $verificationUrl = URL::temporarySignedRoute(
            'student.verification.verify',
            now()->addHour(),
            ['student' => $student->id, 'hash' => sha1($student->email)],
        );
        $this->getJson($verificationUrl)->assertOk();
        $this->assertNotNull($student->fresh()->email_verified_at);
    }

    public function test_student_password_reset_uses_generic_response_and_updates_password(): void
    {
        Notification::fake();
        $student = StudentAccount::factory()->create(['status' => 'Undangan', 'email_verified_at' => null]);

        $this->postJson('/api/v1/student/password/forgot', ['email' => $student->email])
            ->assertOk()
            ->assertJsonPath('message', 'Jika email terdaftar, instruksi reset kata sandi telah dikirim.');
        $this->postJson('/api/v1/student/password/forgot', ['email' => 'missing@example.test'])
            ->assertOk()
            ->assertJsonPath('message', 'Jika email terdaftar, instruksi reset kata sandi telah dikirim.');
        Notification::assertSentTo($student, StudentPasswordReset::class);

        $token = Password::broker('students')->createToken($student);
        $this->postJson('/api/v1/student/password/reset', [
            'email' => $student->email,
            'token' => $token,
            'password' => 'NewSecurePass-456',
            'password_confirmation' => 'NewSecurePass-456',
        ])->assertOk();

        $this->assertTrue(password_verify('NewSecurePass-456', $student->fresh()->password));
        $this->assertSame('Aktif', $student->fresh()->status);
        $this->assertNotNull($student->fresh()->email_verified_at);
    }

    public function test_top_up_credits_points_only_once_after_finance_confirms(): void
    {
        Notification::fake();
        Storage::fake('local');
        $student = StudentAccount::factory()->create(['point_balance' => 0, 'email_verified_at' => now()]);
        $bundle = PointBundle::query()->create([
            'code' => 'PB-0001', 'name' => '500 Poin', 'transfer_amount' => 50000,
            'points' => 500, 'bonus_points' => 25, 'is_active' => true,
        ]);
        $method = PaymentMethod::query()->create([
            'code' => 'PM-0001', 'name' => 'Transfer Bank BCA', 'type' => 'Transfer bank',
            'provider' => 'BCA', 'is_active' => true,
        ]);

        $topUpRequest = [
            'bundleId' => $bundle->code,
            'paymentMethodId' => $method->code,
            'paymentReference' => 'TRF-12345',
            'idempotencyKey' => 'topup-demo-1',
            'proof' => UploadedFile::fake()->create('transfer.pdf', 25, 'application/pdf'),
        ];
        $response = $this->actingAs($student, 'student')->post('/api/v1/student/top-ups', $topUpRequest, ['Accept' => 'application/json'])->assertCreated();
        $this->post('/api/v1/student/top-ups', [
            ...$topUpRequest,
            'proof' => UploadedFile::fake()->create('transfer-retry.pdf', 25, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertOk();
        $this->assertSame(1, $student->topUpRequests()->count());
        $this->post('/api/v1/student/top-ups', [
            ...$topUpRequest,
            'paymentReference' => 'DIFFERENT-REFERENCE',
            'proof' => UploadedFile::fake()->create('transfer-conflict.pdf', 25, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertStatus(409);

        $topupId = $response->json('data.id');
        $this->assertSame(0, (int) $student->fresh()->point_balance);
        $this->assertDatabaseHas('top_up_requests', ['id' => $topupId, 'status' => 'Pending']);

        $finance = User::factory()->create(['role' => 'Finance', 'is_active' => true, 'must_change_password' => false]);
        $this->actingAs($finance)->get('/api/v1/top-ups/'.$topupId.'/proof')->assertOk();
        $review = ['decision' => 'Confirmed', 'reason' => 'Transfer diterima di rekening.'];
        $this->actingAs($finance)->postJson('/api/v1/top-ups/'.$topupId.'/review', $review)->assertOk();
        $this->postJson('/api/v1/top-ups/'.$topupId.'/review', $review)->assertOk();

        $this->assertSame(525, (int) $student->fresh()->point_balance);
        $this->assertSame(1, $student->pointTransactions()->where('type', 'topup_credit')->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'topup.reviewed', 'subject_id' => (string) $topupId]);
        $this->getJson('/api/v1/wallet-summary')->assertOk()
            ->assertJsonPath('confirmedTransferAmount', 50000)
            ->assertJsonPath('topUpPoints', 525)
            ->assertJsonPath('outstandingPoints', 525);
    }

    public function test_purchase_is_idempotent_and_attempt_is_scored_and_limited_by_access_quota(): void
    {
        $owner = User::factory()->create(['role' => 'Owner', 'is_active' => true, 'must_change_password' => false]);
        $student = StudentAccount::factory()->create(['point_balance' => 100, 'email_verified_at' => now()]);
        $questionSeries = QuestionSeries::query()->create(['code' => 'Q-9001', 'created_by' => $owner->id]);
        $question = Question::query()->create([
            'question_series_id' => $questionSeries->id,
            'revision' => 1,
            'title' => 'Hitung 2 + 3',
            'format' => 'Pilihan ganda',
            'education_level' => 'SMP/MTs',
            'subject' => 'Matematika',
            'framework_version' => '2025.1',
            'topic' => 'Bilangan',
            'subelement' => 'Operasi bilangan',
            'cognitive_level' => 'Aplikasi',
            'prompt' => 'Berapakah 2 + 3?',
            'options' => ['4', '5'],
            'answer_data' => ['answerKey' => 1],
            'explanation' => '2 + 3 = 5.',
            'status' => 'Terbit',
            'author_id' => $owner->id,
        ]);
        $packageSeries = PackageSeries::query()->create(['code' => 'PK-9001', 'created_by' => $owner->id]);
        $package = TestPackage::query()->create([
            'package_series_id' => $packageSeries->id,
            'version' => 1,
            'name' => 'Paket demo murid',
            'education_level' => 'SMP/MTs',
            'subject' => 'Matematika',
            'framework_version' => '2025.1',
            'duration_minutes' => 30,
            'attempts' => 0,
            'status' => 'Terbit',
            'author_id' => $owner->id,
        ]);
        PackageItem::query()->create(['test_package_id' => $package->id, 'question_id' => $question->id, 'position' => 1]);
        $offer = AccessOffer::query()->create([
            'code' => 'AO-9001',
            'product_type' => 'package',
            'test_package_id' => $package->id,
            'title' => $package->name,
            'price_points' => 40,
            'validity_days' => 30,
            'attempt_quota' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($student, 'student')->postJson('/api/v1/student/offers/'.$offer->code.'/purchase', [
            'idempotencyKey' => 'purchase-demo-1',
        ])->assertCreated();
        $this->postJson('/api/v1/student/offers/'.$offer->code.'/purchase', [
            'idempotencyKey' => 'purchase-demo-1',
        ])->assertOk();
        $otherOffer = AccessOffer::query()->create([
            'code' => 'AO-9002', 'product_type' => 'package', 'test_package_id' => $package->id,
            'title' => 'Penawaran lain', 'price_points' => 20, 'attempt_quota' => 1, 'is_active' => true,
        ]);
        $this->postJson('/api/v1/student/offers/'.$otherOffer->code.'/purchase', [
            'idempotencyKey' => 'purchase-demo-1',
        ])->assertStatus(409);
        $this->assertSame(60, (int) $student->fresh()->point_balance);
        $this->assertSame(1, $student->accesses()->count());
        $expensiveOffer = AccessOffer::query()->create([
            'code' => 'AO-9003', 'product_type' => 'package', 'test_package_id' => $package->id,
            'title' => 'Penawaran di luar saldo', 'price_points' => 200, 'attempt_quota' => 1, 'is_active' => true,
        ]);
        $this->postJson('/api/v1/student/offers/'.$expensiveOffer->code.'/purchase', [
            'idempotencyKey' => 'purchase-insufficient-1',
        ])->assertUnprocessable();
        $this->assertSame(60, (int) $student->fresh()->point_balance);
        $this->assertSame(1, $student->accesses()->count());

        $access = $student->accesses()->firstOrFail();
        $started = $this->postJson('/api/v1/student/accesses/'.$access->id.'/attempts', [
            'idempotencyKey' => 'attempt-demo-1',
        ])->assertCreated()->json('data');

        $this->putJson('/api/v1/student/attempts/'.$started['id'].'/answers/'.$question->id, ['answer' => 1])
            ->assertOk()->assertJsonPath('saved', true);
        $this->postJson('/api/v1/student/attempts/'.$started['id'].'/submit')
            ->assertOk()
            ->assertJsonPath('data.scorePoints', 1)
            ->assertJsonPath('data.totalPoints', 1)
            ->assertJsonPath('data.scorePercent', 100);
        $this->postJson('/api/v1/student/accesses/'.$access->id.'/attempts', [
            'idempotencyKey' => 'attempt-demo-2',
        ])->assertForbidden();

        $otherStudent = StudentAccount::factory()->create();
        $this->actingAs($otherStudent, 'student')->getJson('/api/v1/student/attempts/'.$started['id'])->assertNotFound();
    }

    public function test_finance_can_manage_manual_point_catalog_and_reasoned_adjustments(): void
    {
        $finance = User::factory()->create(['role' => 'Finance', 'is_active' => true, 'must_change_password' => false]);
        $student = StudentAccount::factory()->create(['point_balance' => 0]);

        $method = $this->actingAs($finance)->postJson('/api/v1/payment-methods', [
            'name' => 'Rekening QA Finance',
            'type' => 'Transfer bank',
            'provider' => 'BCA',
            'accountName' => 'Fibo QA',
            'accountNumber' => '1234567890',
            'instructions' => 'Cantumkan email akun pada catatan transfer.',
            'isActive' => true,
            'reason' => 'Kanal manual untuk pengujian.',
        ])->assertCreated()->json('data');
        $this->patchJson('/api/v1/payment-methods/'.$method['id'], [
            'accountNumber' => '0987654321',
            'reason' => 'Nomor rekening diperbarui.',
        ])->assertOk()->assertJsonPath('data.accountNumber', '0987654321');
        $this->deleteJson('/api/v1/payment-methods/'.$method['id'], ['reason' => 'Kanal QA belum dipakai.'])->assertNoContent();

        $bundle = $this->postJson('/api/v1/point-bundles', [
            'name' => 'Paket QA',
            'transferAmount' => 25000,
            'points' => 250,
            'bonusPoints' => 10,
            'isActive' => true,
            'reason' => 'Paket manual untuk QA.',
        ])->assertCreated()->json('data');
        $this->putJson('/api/v1/point-bundles/'.$bundle['id'], [
            'bonusPoints' => 20,
            'reason' => 'Bonus disesuaikan.',
        ])->assertOk()->assertJsonPath('data.bonusPoints', 20);
        $this->deleteJson('/api/v1/point-bundles/'.$bundle['id'], ['reason' => 'Paket QA belum dipakai.'])->assertNoContent();

        $offer = $this->postJson('/api/v1/access-offers', [
            'productType' => 'material',
            'materialCode' => 'MATERI-QA',
            'title' => 'Materi QA',
            'description' => 'Materi contoh untuk pengujian.',
            'pricePoints' => 25,
            'validityDays' => 30,
            'attemptQuota' => 3,
            'isActive' => true,
            'reason' => 'Penawaran material QA.',
        ])->assertCreated()->json('data');
        $this->putJson('/api/v1/access-offers/'.$offer['id'], [
            'pricePoints' => 30,
            'reason' => 'Harga disesuaikan.',
        ])->assertOk()->assertJsonPath('data.pricePoints', 30);
        $this->deleteJson('/api/v1/access-offers/'.$offer['id'], ['reason' => 'Penawaran QA belum dibeli.'])->assertNoContent();

        $this->putJson('/api/v1/wallet-settings', [
            'signupBonusPoints' => 75,
            'reason' => 'Bonus awal disesuaikan untuk QA.',
        ])->assertOk()->assertJsonPath('signupBonusPoints', 75);

        $studentCode = 'M-'.str_pad((string) $student->id, 4, '0', STR_PAD_LEFT);
        $adjustment = ['points' => 50, 'reason' => 'Bonus kampanye QA.', 'idempotencyKey' => 'qa-manual-adjustment-1'];
        $this->postJson('/api/v1/students/'.$studentCode.'/points', $adjustment)
            ->assertOk()->assertJsonPath('balance', 50);
        $this->postJson('/api/v1/students/'.$studentCode.'/points', $adjustment)
            ->assertOk()->assertJsonPath('balance', 50);
        $this->postJson('/api/v1/students/'.$studentCode.'/points', [...$adjustment, 'points' => 75])
            ->assertStatus(409);
        $this->getJson('/api/v1/wallet-summary')->assertOk()
            ->assertJsonPath('promotionalPoints', 50)
            ->assertJsonPath('outstandingPoints', 50);
        $this->assertSame(1, $student->pointTransactions()->count());
    }

    public function test_closed_student_is_anonymized_after_retention_while_history_is_preserved(): void
    {
        Storage::fake('local');
        config()->set('privacy.student_data_retention_days', 730);

        $student = StudentAccount::factory()->create([
            'name' => 'Student Private Name',
            'email' => 'private-student@example.test',
            'status' => 'Ditutup',
            'closed_at' => now()->subDays(731),
            'anonymized_at' => null,
            'point_balance' => 25,
        ]);
        $recentlyClosedStudent = StudentAccount::factory()->create([
            'email' => 'recent-closed-student@example.test',
            'status' => 'Ditutup',
            'closed_at' => now()->subDays(729),
            'anonymized_at' => null,
        ]);
        $owner = User::factory()->create();
        $series = PackageSeries::query()->create(['code' => 'PK-RET-1', 'created_by' => $owner->id]);
        $package = TestPackage::query()->create([
            'package_series_id' => $series->id,
            'version' => 1,
            'name' => 'Riwayat dipertahankan',
            'status' => 'Terbit',
            'author_id' => $owner->id,
        ]);
        $access = StudentAccess::query()->create([
            'student_account_id' => $student->id,
            'test_package_id' => $package->id,
            'offer_title' => $package->name,
            'price_points' => 1,
            'attempt_quota' => 1,
            'attempts_used' => 1,
            'starts_at' => now()->subDays(732),
            'status' => 'Aktif',
            'idempotency_key' => 'retention-access-1',
        ]);
        $attempt = StudentAttempt::query()->create([
            'student_account_id' => $student->id,
            'student_access_id' => $access->id,
            'test_package_id' => $package->id,
            'idempotency_key' => 'retention-attempt-1',
            'status' => 'Submitted',
            'started_at' => now()->subDays(732),
            'submitted_at' => now()->subDays(732),
            'score_points' => 1,
            'total_points' => 1,
            'score_percent' => 100,
        ]);
        $proofPath = 'topups/'.$student->id.'/proof.pdf';
        Storage::disk('local')->put($proofPath, 'private transfer proof');
        $topup = TopUpRequest::query()->create([
            'student_account_id' => $student->id,
            'bundle_name' => '25 Poin',
            'transfer_amount' => 2500,
            'points' => 25,
            'payment_method_name' => 'Bank manual',
            'payment_reference' => 'PRIVATE-TRANSFER-REFERENCE',
            'proof_path' => $proofPath,
            'idempotency_key' => 'retention-topup-1',
            'status' => 'Confirmed',
        ]);
        $transaction = PointTransaction::query()->create([
            'student_account_id' => $student->id,
            'type' => 'topup_credit',
            'points_delta' => 25,
            'balance_after' => 25,
            'idempotency_key' => 'retention-ledger-1',
            'reason' => 'Private account transfer note',
        ]);
        AuditLog::query()->create([
            'student_actor_id' => $student->id,
            'action' => 'student.updated',
            'subject_type' => 'StudentAccount',
            'subject_id' => (string) $student->id,
            'before' => ['name' => $student->name, 'email' => $student->email, 'status' => 'Aktif'],
            'after' => ['name' => $student->name, 'email' => $student->email, 'status' => 'Ditutup'],
            'reason' => 'Closed for private-student@example.test',
            'ip_address' => '192.0.2.20',
        ]);
        DB::table('student_password_reset_tokens')->insert([
            'email' => $student->email,
            'token' => 'private-reset-token',
            'created_at' => now(),
        ]);

        $this->assertSame(0, Artisan::call('students:anonymize-closed'));

        $this->assertSame('Ditutup', $student->fresh()->status);
        $this->assertSame('Murid anonim '.$student->id, $student->fresh()->name);
        $this->assertSame('anonim-murid-'.$student->id.'@example.invalid', $student->fresh()->email);
        $this->assertNotNull($student->fresh()->anonymized_at);
        $this->assertSame(25, (int) $student->fresh()->point_balance);
        $this->assertDatabaseHas('student_attempts', ['id' => $attempt->id, 'student_account_id' => $student->id]);
        $this->assertDatabaseHas('top_up_requests', ['id' => $topup->id, 'status' => 'Confirmed', 'payment_reference' => null, 'proof_path' => null]);
        $this->assertDatabaseHas('point_transactions', ['id' => $transaction->id, 'points_delta' => 25, 'balance_after' => 25]);
        $this->assertSame('Transaksi dipertahankan setelah anonimisasi murid.', $transaction->fresh()->reason);
        $this->assertDatabaseMissing('student_password_reset_tokens', ['email' => 'private-student@example.test']);
        $this->assertFalse(Storage::disk('local')->exists($proofPath));

        $audit = AuditLog::query()->where('action', 'student.updated')->firstOrFail();
        $this->assertArrayNotHasKey('email', $audit->before);
        $this->assertArrayNotHasKey('name', $audit->after);
        $this->assertNull($audit->ip_address);
        $this->assertStringNotContainsString('private-student@example.test', $audit->reason);
        $this->assertDatabaseHas('audit_logs', ['action' => 'student.anonymized', 'subject_id' => (string) $student->id]);
        $this->assertSame('recent-closed-student@example.test', $recentlyClosedStudent->fresh()->email);
        $this->assertNull($recentlyClosedStudent->fresh()->anonymized_at);
        $this->postJson('/api/v1/student/login', [
            'email' => 'private-student@example.test',
            'password' => 'student-password',
        ])->assertUnprocessable();

        Artisan::call('students:anonymize-closed');
        $this->assertSame(1, AuditLog::query()->where('action', 'student.anonymized')->count());
    }
}
