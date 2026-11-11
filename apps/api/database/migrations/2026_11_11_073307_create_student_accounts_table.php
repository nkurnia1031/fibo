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
        Schema::create('student_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('external_id', 160)->unique();
            $table->string('name', 160);
            $table->string('email', 190)->unique();
            $table->string('school', 190)->nullable();
            $table->string('status', 32)->default('Undangan')->index();
            $table->unsignedInteger('attempt_quota')->default(0);
            $table->unsignedInteger('attempts_used')->default(0);
            $table->timestamp('access_expires_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_accounts');
    }
};
