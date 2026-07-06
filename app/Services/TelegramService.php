<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    public function sendMessage(string $message): bool
    {
        $token = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if (empty($token) || empty($chatId)) {
            Log::error('TelegramService: missing bot token or chat id', [
                'bot_token_set' => ! empty($token),
                'chat_id_set' => ! empty($chatId),
            ]);
            return false;
        }

        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        try {
            $response = Http::post($url, [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);

            if (! $response->successful()) {
                Log::error('TelegramService: telegram API returned error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('TelegramService: exception when sending message', ['message' => $e->getMessage()]);
            return false;
        }
    }
}
