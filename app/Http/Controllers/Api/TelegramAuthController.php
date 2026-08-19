<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\TelegramAuthException;
use App\Http\Controllers\Controller;
use App\Services\TelegramAuthService;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class TelegramAuthController extends Controller
{
    public function __construct(private readonly TelegramAuthService $telegramAuth)
    {
    }

    /**
     * Verifies the Telegram Login Widget payload and returns a JWT + user.
     * This is the flow the frontend's TelegramCallback page actually calls.
     */
    public function telegramApi(Request $request)
    {
        try {
            $user = $this->telegramAuth->resolveUser($request->all());
        } catch (TelegramAuthException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status);
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $user,
        ]);
    }
}
