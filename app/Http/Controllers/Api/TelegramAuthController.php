<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class TelegramAuthController extends Controller
{
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

      /**
       * API flow: verify telegram payload and return JSON with JWT and user
       */
      public function telegramApi(Request $request)
      {
        $data = $request->all();

        $required = ['id', 'auth_date', 'hash'];
        foreach ($required as $k) {
          if (! isset($data[$k])) {
            return response()->json(['message' => 'Missing telegram data.'], 400);
          }
        }

        $botToken = env('TELEGRAM_BOT_TOKEN');
        if (! $botToken) {
          return response()->json(['message' => 'Telegram bot token not configured.'], 500);
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
          return response()->json(['message' => 'Invalid Telegram login data.'], 403);
        }

        if (time() - intval($data['auth_date']) > 86400) {
          return response()->json(['message' => 'Telegram login expired.'], 403);
        }

        $telegramId = $data['id'];
        $username = $data['username'] ?? null;
        $firstName = $data['first_name'] ?? null;
        $lastName = $data['last_name'] ?? null;
        $photo = $data['photo_url'] ?? null;

        $name = trim(($firstName ?? '').' '.($lastName ?? '')) ?: ($username ?? 'Telegram User');

        $user = User::where('telegram_id', $telegramId)->first();

        if (! $user) {
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

        return response()->json([
          'message' => 'Login successful.',
          'token' => $token,
          'user' => $user,
        ]);
      }
}
