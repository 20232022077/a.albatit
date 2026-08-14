<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deactivating a user (UserController::update, is_active => false) must
 * take effect immediately, not just block their next login — otherwise an
 * already-authenticated session stays fully usable until it naturally
 * expires. Checked on every request rather than only at login time.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'تم إيقاف هذا الحساب. يرجى التواصل مع الإدارة.',
            ]);
        }

        return $next($request);
    }
}
