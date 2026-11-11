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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_series_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('revision');
            $table->string('title', 255);
            $table->string('format', 80);
            $table->string('education_level', 40)->default('SMP/MTs');
            $table->string('subject', 80)->default('Matematika');
            $table->string('framework_version', 40)->default('2025.1');
            $table->string('topic', 120);
            $table->string('subelement', 160);
            $table->string('cognitive_level', 80);
            $table->longText('prompt');
            $table->json('options')->nullable();
            $table->json('answer_data')->nullable();
            $table->longText('explanation');
            $table->text('misconception')->nullable();
            $table->json('tags')->nullable();
            $table->string('image_path')->nullable();
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
            $table->unique(['question_series_id', 'revision']);
            $table->index(['status', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
