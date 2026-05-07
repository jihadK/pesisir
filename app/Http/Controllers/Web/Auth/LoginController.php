<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginInput = $credentials['login'];
        $password   = $credentials['password'];
        $ip         = $request->ip();
        $userAgent  = substr((string) $request->userAgent(), 0, 255);

        // Cari user by username atau email
        $user = User::where('username', $loginInput)
            ->orWhere('email', $loginInput)
            ->first();

        if (! $user) {
            $this->logAttempt(null, $loginInput, $ip, $userAgent, false, 'user_not_found');
            throw ValidationException::withMessages(['login' => 'Username atau password salah.']);
        }

        if ($user->isLocked()) {
            $this->logAttempt($user->id, $loginInput, $ip, $userAgent, false, 'account_locked');
            throw ValidationException::withMessages([
                'login' => 'Akun terkunci sampai ' . $user->locked_until->format('H:i, d M Y') . '.',
            ]);
        }

        if (! $user->isActive()) {
            $this->logAttempt($user->id, $loginInput, $ip, $userAgent, false, 'account_inactive');
            throw ValidationException::withMessages([
                'login' => 'Akun tidak aktif (status: ' . $user->registration_status . ').',
            ]);
        }

        if (! Hash::check($password, $user->password_hash)) {
            $user->increment('failed_login_attempts');
            if ($user->failed_login_attempts + 1 >= 5) {
                $user->update(['locked_until' => now()->addMinutes(30)]);
            }
            $this->logAttempt($user->id, $loginInput, $ip, $userAgent, false, 'invalid_password');
            throw ValidationException::withMessages(['login' => 'Username atau password salah.']);
        }

        // Sukses
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until'          => null,
            'last_login_at'         => now(),
        ]);
        $this->logAttempt($user->id, $loginInput, $ip, $userAgent, true, null);

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function logAttempt(?int $userId, string $email, ?string $ip, ?string $ua, bool $success, ?string $reason): void
    {
        DB::table('tbh_login_attempts')->insert([
            'user_id'        => $userId,
            'email'          => $email,
            'ip_address'     => $ip,
            'user_agent'     => $ua,
            'success'        => $success,
            'failure_reason' => $reason,
            'attempted_at'   => now(),
        ]);
    }
}
