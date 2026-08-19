<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveTest extends TestCase
{
    use RefreshDatabase;

    private function authHeader(User $user): array
    {
        return ['Authorization' => 'Bearer '.auth('api')->login($user)];
    }

    public function test_store_creates_pending_leave_request(): void
    {
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson('/api/leaves', [
                'start_date' => now()->addDay()->toDateString(),
                'end_date' => now()->addDays(2)->toDateString(),
                'type' => 'annual',
                'reason' => 'Family trip',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('leave.status', 'pending');
        $this->assertDatabaseHas('leave_requests', ['user_id' => $user->id, 'status' => 'pending']);
    }

    public function test_store_rejects_end_date_before_start_date(): void
    {
        $user = User::factory()->create();

        $this->withHeaders($this->authHeader($user))
            ->postJson('/api/leaves', [
                'start_date' => now()->addDays(2)->toDateString(),
                'end_date' => now()->addDay()->toDateString(),
                'type' => 'annual',
                'reason' => 'Bad dates',
            ])
            ->assertStatus(422);
    }

    public function test_index_only_returns_own_leaves(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        LeaveRequest::factory()->count(2)->create(['user_id' => $user->id]);
        LeaveRequest::factory()->create(['user_id' => $other->id]);

        $response = $this->withHeaders($this->authHeader($user))->getJson('/api/leaves');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('leaves.data'));
    }

    public function test_user_cannot_view_another_users_leave(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $leave = LeaveRequest::factory()->create(['user_id' => $other->id]);

        $this->withHeaders($this->authHeader($user))
            ->getJson("/api/leaves/{$leave->id}")
            ->assertStatus(403);
    }

    public function test_cancel_only_allowed_while_pending(): void
    {
        $user = User::factory()->create();
        $leave = LeaveRequest::factory()->create(['user_id' => $user->id, 'status' => 'approved']);

        $this->withHeaders($this->authHeader($user))
            ->deleteJson("/api/leaves/{$leave->id}")
            ->assertStatus(422);
    }

    public function test_admin_can_review_pending_leave(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create();
        $leave = LeaveRequest::factory()->create(['user_id' => $employee->id, 'status' => 'pending']);

        $response = $this->withHeaders($this->authHeader($admin))
            ->patchJson("/api/admin/leaves/{$leave->id}/review", ['status' => 'approved']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leave->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_cannot_review_already_reviewed_leave(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create();
        $leave = LeaveRequest::factory()->create(['user_id' => $employee->id, 'status' => 'approved']);

        $this->withHeaders($this->authHeader($admin))
            ->patchJson("/api/admin/leaves/{$leave->id}/review", ['status' => 'rejected'])
            ->assertStatus(422);
    }
}
