<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function authHeader(User $user): array
    {
        return ['Authorization' => 'Bearer '.auth('api')->login($user)];
    }

    public function test_check_in_before_threshold_marks_present(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 30));
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson('/api/attendance/check-in');

        $response->assertStatus(201);
        $response->assertJsonPath('attendance.status', 'present');
    }

    public function test_check_in_after_threshold_marks_late(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 30));
        $user = User::factory()->create();

        $response = $this->withHeaders($this->authHeader($user))
            ->postJson('/api/attendance/check-in');

        $response->assertStatus(201);
        $response->assertJsonPath('attendance.status', 'late');
    }

    public function test_cannot_check_in_twice_same_day(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(8, 0));
        $user = User::factory()->create();
        $headers = $this->authHeader($user);

        $this->withHeaders($headers)->postJson('/api/attendance/check-in')->assertStatus(201);
        $this->withHeaders($headers)->postJson('/api/attendance/check-in')->assertStatus(409);
    }

    public function test_cannot_check_out_without_checking_in(): void
    {
        $user = User::factory()->create();

        $this->withHeaders($this->authHeader($user))
            ->postJson('/api/attendance/check-out')
            ->assertStatus(422);
    }

    public function test_check_out_after_check_in_succeeds(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 0));
        $user = User::factory()->create();
        $headers = $this->authHeader($user);

        $this->withHeaders($headers)->postJson('/api/attendance/check-in')->assertStatus(201);

        // Stay within the JWT's TTL window — jumping past it would expire
        // the token issued at check-in and fail with 401, not 200.
        Carbon::setTestNow(Carbon::today()->setTime(9, 30));
        $this->withHeaders($headers)->postJson('/api/attendance/check-out')->assertStatus(200);
    }

    public function test_cannot_check_out_twice(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(9, 0));
        $user = User::factory()->create();
        $headers = $this->authHeader($user);

        $this->withHeaders($headers)->postJson('/api/attendance/check-in')->assertStatus(201);
        $this->withHeaders($headers)->postJson('/api/attendance/check-out')->assertStatus(200);
        $this->withHeaders($headers)->postJson('/api/attendance/check-out')->assertStatus(409);
    }
}
