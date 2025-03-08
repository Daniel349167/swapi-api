<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role)
    {
        if ($request->user()->role->name !== $role) {
            return response()->json(['error' => 'No tienes los permisos necesarios.'], 403);
        }

        return $next($request);
    }
}
