<?php

namespace Tests\Unit\Policies;

use App\Models\StudentAccount;
use App\Models\User;
use App\Policies\StudentAccountPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StudentAccountPolicyTest extends TestCase
{
    #[DataProvider('studentManagerRoles')]
    public function test_owner_admin_and_support_can_update_student_accounts(string $role): void
    {
        $user = User::factory()->make(['role' => $role, 'is_active' => true]);
        $student = StudentAccount::factory()->make();

        $this->assertTrue((new StudentAccountPolicy)->update($user, $student));
    }

    public function test_inactive_staff_cannot_update_student_accounts(): void
    {
        $user = User::factory()->make(['role' => 'Owner', 'is_active' => false]);
        $student = StudentAccount::factory()->make();

        $this->assertFalse((new StudentAccountPolicy)->update($user, $student));
    }

    public static function studentManagerRoles(): array
    {
        return [
            'owner' => ['Owner'],
            'admin' => ['Admin'],
            'support' => ['Support'],
        ];
    }
}
