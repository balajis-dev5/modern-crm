<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\ApiResponse;
use App\Support\Jwt;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $claims = Jwt::verify($request->bearerToken() ?? '');

        $user = $claims ? User::find($claims['sub']) : null;

        if (! $user) {
            return ApiResponse::error('Unauthenticated.', 'UNAUTHENTICATED', 401);
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
