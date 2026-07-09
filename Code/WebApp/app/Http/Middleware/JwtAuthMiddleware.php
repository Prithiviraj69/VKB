<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\JwtService;
use App\Models\User;

class JwtAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: Missing token'], 401);
        }

        $token = substr($authHeader, 7);
        $decoded = JwtService::decodeToken($token);

        if (!$decoded || !isset($decoded['sub'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: Invalid or expired token'], 401);
        }

        $user = User::find($decoded['sub']);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: User not found'], 401);
        }

        if (isset($user->is_active) && !$user->is_active) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: User account is deactivated'], 401);
        }

        // Set authenticated user on request
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }
}
