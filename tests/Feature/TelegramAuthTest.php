<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class TelegramAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_telegram_api_creates_user_and_returns_token()
    {
        // Prepare payload
        $bot = env('TELEGRAM_BOT_TOKEN', 'test_bot_token_ABC123');
        $data = [
            'id' => '888888',
            'auth_date' => (string)time(),
            'first_name' => 'CIUser',
            'username' => 'ci_user',
            'photo_url' => 'https://t.me/i/userpic/320/ci_user.jpg',
        ];

        ksort($data);
        $arr = [];
        foreach ($data as $k => $v) {
            $arr[] = $k.'='.$v;
        }
        $dataCheck = implode("\n", $arr);
        $secret = hash('sha256', $bot, true);
        $hash = hash_hmac('sha256', $dataCheck, $secret);
        $data['hash'] = $hash;

        $response = $this->postJson('/api/auth/telegram-auth', $data);

        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'token', 'user']);

        $this->assertDatabaseHas('users', ['telegram_id' => '888888']);
    }
}
