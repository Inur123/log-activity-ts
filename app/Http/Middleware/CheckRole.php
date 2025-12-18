<?php
// app/Http/Middleware/CheckRole.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Jika user belum login
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Dapatkan role user
        $userRole = Auth::user()->role;

        // Jika role user tidak ada dalam role yang diizinkan
        if (!in_array($userRole, $roles)) {

            // Jika request adalah API (expects JSON)
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Required role: ' . implode(', ', $roles)
                ], 403);
            }

            // Jika bukan JSON, tampilkan error 403
            abort(403, 'Unauthorized access');
        }

        return $next($request);
    }
}
