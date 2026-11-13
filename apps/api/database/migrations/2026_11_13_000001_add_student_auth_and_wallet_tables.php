<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_accounts', function (Blueprint $table): void {
            $table->string('password')->nullable()->after('email');
            $table->timestamp('email_verified_at')->nullable()->after('password');
            $table->rememberToken();
            $table->unsignedBigInteger('point_balance')->default(0)->after('attempts_used');
            $table->timestamp('anonymized_at')->nullable()->after('closed_at');
        });

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->foreignId('student_actor_id')->nullable()->after('actor_id')->constrained('student_accounts')->nullOnDelete();
        });

        Schema::create('student_password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('wallet_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 40)->default('default')->unique();
            $table->unsignedBigInteger('signup_bonus_points')->default(100);
            $table->timestamps();
        });

        Schema::create('point_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_account_id')->constrained()->restrictOnDelete();
            $table->string('type', 40);
            $table->bigInteger('points_delta');
            $table->unsignedBigInteger('balance_after');
            $table->string('source_type', 80)->nullable();
            $table->string('source_id', 100)->nullable();
            $table->string('idempotency_key', 120)->nullable()->unique();
            $table->string('reason', 500);
            $table->json('metadata')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['student_account_id', 'created_at']);
        });

        Schema::create('point_bundles', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 24)->unique();
            $table->string('name', 120);
            $table->unsignedBigInteger('transfer_amount');
            $table->unsignedBigInteger('points');
            $table->unsignedBigInteger('bonus_points')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('payment_methods', function (Blueprint $table): void {
            $table->string('account_name', 160)->nullable();
            $table->string('account_number', 100)->nullable();
            $table->text('instructions')->nullable();
        });

        DB::table('payment_methods')->where('provider', 'Midtrans')->update([
            'provider' => 'Manual',
            'fee_info' => 'Dikelola manual',
            'is_active' => false,
        ]);

        Schema::create('top_up_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_account_id')->constrained()->restrictOnDelete();
            $table->foreignId('point_bundle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('bundle_name', 120);
            $table->unsignedBigInteger('transfer_amount');
            $table->unsignedBigInteger('points');
            $table->unsignedBigInteger('bonus_points')->default(0);
            $table->string('payment_method_name', 120);
            $table->json('payment_details')->nullable();
            $table->string('payment_reference', 160)->nullable();
            $table->string('proof_path', 500)->nullable();
            $table->string('idempotency_key', 120)->unique();
            $table->string('status', 24)->default('Pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('review_reason', 500)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['student_account_id', 'created_at']);
        });

        Schema::create('access_offers', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 24)->unique();
            $table->string('product_type', 24)->default('package');
            $table->foreignId('test_package_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('material_code', 80)->nullable();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price_points');
            $table->unsignedInteger('validity_days')->nullable();
            $table->unsignedInteger('attempt_quota')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['product_type', 'is_active']);
        });

        Schema::create('student_accesses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_account_id')->constrained()->restrictOnDelete();
            $table->foreignId('access_offer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('test_package_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('material_code', 80)->nullable();
            $table->string('offer_title', 180);
            $table->unsignedBigInteger('price_points');
            $table->unsignedInteger('attempt_quota')->nullable();
            $table->unsignedInteger('attempts_used')->default(0);
            $table->timestamp('starts_at');
            $table->timestamp('expires_at')->nullable();
            $table->string('status', 24)->default('Aktif')->index();
            $table->string('idempotency_key', 120)->unique();
            $table->timestamps();
            $table->index(['student_account_id', 'status', 'expires_at']);
        });

        Schema::create('student_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_account_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_access_id')->constrained()->restrictOnDelete();
            $table->foreignId('test_package_id')->constrained()->restrictOnDelete();
            $table->string('idempotency_key', 120)->unique();
            $table->string('status', 24)->default('In progress')->index();
            $table->timestamp('started_at');
            $table->timestamp('deadline_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedSmallInteger('score_points')->default(0);
            $table->unsignedSmallInteger('total_points')->default(0);
            $table->decimal('score_percent', 5, 2)->default(0);
            $table->timestamps();
            $table->index(['student_account_id', 'status', 'started_at']);
        });

        Schema::create('attempt_responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->restrictOnDelete();
            $table->json('response')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->unsignedTinyInteger('score_points')->default(0);
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
            $table->unique(['student_attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_responses');
        Schema::dropIfExists('student_attempts');
        Schema::dropIfExists('student_accesses');
        Schema::dropIfExists('access_offers');
        Schema::dropIfExists('top_up_requests');
        Schema::table('payment_methods', function (Blueprint $table): void {
            $table->dropColumn(['account_name', 'account_number', 'instructions']);
        });
        Schema::dropIfExists('point_bundles');
        Schema::dropIfExists('point_transactions');
        Schema::dropIfExists('wallet_settings');
        Schema::dropIfExists('student_password_reset_tokens');
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('student_actor_id');
        });
        Schema::table('student_accounts', function (Blueprint $table): void {
            $table->dropRememberToken();
            $table->dropColumn(['password', 'email_verified_at', 'point_balance', 'anonymized_at']);
        });
    }
};
