<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (! $user) {
                return response()->json(['message' => 'User not found.'], 404);
            }
        } catch (TokenExpiredException $e) {
            return response()->json(['message' => 'Token has expired.'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['message' => 'Token is invalid.'], 401);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Authorization token not found.'], 401);
        }

        return $next($request);
    }
}
