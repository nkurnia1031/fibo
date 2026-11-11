<?php

namespace Tests\Feature\Api\V1;

use App\Models\StudentAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateStudentAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_api_request_returns_json_401_without_accept_header(): void
    {
        $this->get('/api/v1/students', ['Accept' => '*/*'])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_owner_can_edit_a_student_without_changing_unique_fields(): void
    {
        $owner = User::factory()->create([
            'role' => 'Owner',
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $student = StudentAccount::query()->create([
            'external_id' => 'qa-student-001',
            'name' => 'Alya Demo',
            'email' => 'alya@example.test',
            'school' => 'SMP Contoh',
            'status' => 'Aktif',
            'attempt_quota' => 15,
            'attempts_used' => 4,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->putJson('/api/v1/students/M-'.str_pad((string) $student->id, 4, '0', STR_PAD_LEFT), [
                'name' => 'Alya Demo Diperbarui',
                'email' => 'alya@example.test',
                'school' => 'SMP Contoh',
                'externalId' => 'qa-student-001',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Alya Demo Diperbarui')
            ->assertJsonPath('data.email', 'alya@example.test')
            ->assertJsonPath('data.externalId', 'qa-student-001');

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $owner->id,
            'action' => 'student.updated',
            'subject_type' => 'StudentAccount',
            'subject_id' => (string) $student->id,
        ]);
    }
}
