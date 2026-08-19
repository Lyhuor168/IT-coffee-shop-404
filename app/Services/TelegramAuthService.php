<?php

namespace App\Services;

use App\Exceptions\TelegramAuthException;
use App\Models\User;

class TelegramAuthService
{
    /**
     * Verify a Telegram Login Widget payload and return the matching
     * (or newly created) User.
     *
     * @throws TelegramAuthException
     */
    public function resolveUser(array $data): User
    {
        foreach (['id', 'auth_date', 'hash'] as $key) {
            if (! isset($data[$key])) {
                throw new TelegramAuthException('Missing telegram data.', 400);
            }
        }

        $botToken = config('services.telegram.bot_token');
        if (! $botToken) {
            throw new TelegramAuthException('Telegram bot token not configured.', 500);
        }

        $hash = $data['hash'];
        unset($data['hash']);

        ksort($data);
        $dataCheckArr = [];
        foreach ($data as $key => $value) {
            $dataCheckArr[] = $key.'='.$value;
        }
        $dataCheckString = implode("\n", $dataCheckArr);

        $secretKey = hash('sha256', $botToken, true);
        $calculated = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (! hash_equals($calculated, $hash)) {
            throw new TelegramAuthException('Invalid Telegram login data.', 403);
        }

        if (time() - intval($data['auth_date']) > 86400) {
            throw new TelegramAuthException('Telegram login expired.', 403);
        }

        return $this->findOrCreateUser($data);
    }

    private function findOrCreateUser(array $data): User
    {
        $telegramId = $data['id'];
        $username = $data['username'] ?? null;
        $firstName = $data['first_name'] ?? null;
        $lastName = $data['last_name'] ?? null;
        $photo = $data['photo_url'] ?? null;

        $name = trim(($firstName ?? '').' '.($lastName ?? '')) ?: ($username ?? 'Telegram User');

        $user = User::where('telegram_id', $telegramId)->first();

        if (! $user) {
            $email = $username ? ($username.'@telegram.local') : ('telegram_'.$telegramId.'@telegram.local');

            return User::create([
                'name' => $name,
                'email' => $email,
                'password' => bcrypt(str()->random(16)),
                'role' => 'employee',
                'telegram_id' => $telegramId,
                'telegram_username' => $username,
                'telegram_photo' => $photo,
            ]);
        }

        $user->update([
            'telegram_username' => $username,
            'telegram_photo' => $photo,
        ]);

        return $user;
    }
}
