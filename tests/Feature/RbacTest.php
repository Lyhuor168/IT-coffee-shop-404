<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return auth('api')->login($user);
    }

    public function test_employee_cannot_access_admin_routes(): void
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $token = $this->tokenFor($employee);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/users')
            ->assertStatus(403);
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $this->tokenFor($admin);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/users')
            ->assertStatus(200);
    }

    public function test_admin_cannot_change_own_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $this->tokenFor($admin);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/admin/users/{$admin->id}/role", ['role' => 'employee'])
            ->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin']);
    }

    public function test_admin_can_change_another_users_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);
        $token = $this->tokenFor($admin);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/admin/users/{$employee->id}/role", ['role' => 'admin'])
            ->assertStatus(200);

        $this->assertDatabaseHas('users', ['id' => $employee->id, 'role' => 'admin']);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $this->tokenFor($admin);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/admin/users/{$admin->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
