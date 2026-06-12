<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone'    => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'phone'    => $request->phone,
            'position' => $request->position,
            'role'     => 'employee',
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'Registration successful.',
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (! $token = JWTAuth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid email or password.'], 401);
        }

        return response()->json([
            'message' => 'Login successful.',
            'user' => auth('api')->user(),
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ]);
    }

    public function logout(): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => 'Successfully logged out.']);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to log out.'], 500);
        }
    }

    public function refresh(): JsonResponse
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
            return response()->json([
                'token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Could not refresh token.'], 401);
        }
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()]);
    }

    /**
     * Handle Telegram Login Widget callback (GET from widget).
     * Verifies the data per Telegram docs, finds or creates a user, returns an HTML page
     * that stores the JWT in localStorage and redirects to the frontend dashboard.
     */
    public function telegramLogin(Request $request)
    {
        $data = $request->all();

        $required = ['id', 'auth_date', 'hash'];
        foreach ($required as $k) {
            if (! isset($data[$k])) {
                return response('Missing telegram data', 400);
            }
        }

        $botToken = env('TELEGRAM_BOT_TOKEN');
        if (! $botToken) {
            return response('Telegram bot token not configured.', 500);
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
            return response('Invalid Telegram login data.', 403);
        }

        // Optional: check auth_date freshness (24h)
        if (time() - intval($data['auth_date']) > 86400) {
            return response('Telegram login expired.', 403);
        }

        $telegramId = $data['id'];
        $username = $data['username'] ?? null;
        $firstName = $data['first_name'] ?? null;
        $lastName = $data['last_name'] ?? null;
        $photo = $data['photo_url'] ?? null;

        $name = trim(($firstName ?? '').' '.($lastName ?? '')) ?: ($username ?? 'Telegram User');

        $user = User::where('telegram_id', $telegramId)->first();

        if (! $user) {
            // Ensure unique email placeholder
            $email = $username ? ($username.'@telegram.local') : ('telegram_'.$telegramId.'@telegram.local');

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => bcrypt(str()->random(16)),
                'role' => 'employee',
                'telegram_id' => $telegramId,
                'telegram_username' => $username,
                'telegram_photo' => $photo,
            ]);
        } else {
            $user->update([
                'telegram_username' => $username,
                'telegram_photo' => $photo,
            ]);
        }

        $token = JWTAuth::fromUser($user);

        $frontend = env('FRONTEND_URL', config('app.url'));

        $userPayload = json_encode($user->toArray());

        // Return a small HTML page that sets localStorage and redirects back to the SPA
        $html = <<<HTML
<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <title>Logging in...</title>
  </head>
  <body>
    <script>
      try {
        localStorage.setItem('token', '{$token}');
        localStorage.setItem('user', JSON.stringify({$userPayload}));
        window.location.href = '{$frontend}/dashboard';
      } catch (e) {
        document.body.innerText = 'Login completed. Please close this window and return to the app.';
      }
    </script>
  </body>
</html>
HTML;

        return response($html, 200)->header('Content-Type', 'text/html');
    }
}
