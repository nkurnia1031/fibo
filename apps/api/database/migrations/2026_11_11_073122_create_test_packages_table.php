<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('test_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_series_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->string('name', 255);
            $table->string('education_level', 40)->default('SMP/MTs');
            $table->string('subject', 80)->default('Matematika');
            $table->string('framework_version', 40)->default('2025.1');
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->string('status', 32)->default('Draf')->index();
            $table->foreignId('author_id')->constrained('users');
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->unique(['package_series_id', 'version']);
            $table->index(['status', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_packages');
    }
};
