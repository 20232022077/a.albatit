<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 60;

    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->safe()->only('email', 'password');
        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->logAttempt('auth.login.throttled', $request, ['email' => $credentials['email']]);

            return back()->withInput($request->except('password'))->withErrors([
                'email' => "تم تجاوز عدد المحاولات. يرجى المحاولة بعد {$seconds} ثانية.",
            ]);
        }

        if (! Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);
            $this->logAttempt('auth.login.failed', $request, ['email' => $credentials['email']]);

            return back()->withInput($request->except('password'))->withErrors([
                'email' => 'بيانات الدخول غير صحيحة.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();
        $this->logAttempt('auth.login.succeeded', $request, [], Auth::id());

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->logAttempt('auth.logout', $request, [], Auth::id());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip());
    }

    private function logAttempt(string $event, Request $request, array $properties = [], ?int $userId = null): void
    {
        DB::table('activity_logs')->insert([
            'user_id' => $userId,
            'event' => $event,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 65535, ''),
            'properties' => json_encode($properties, JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
    }
}
