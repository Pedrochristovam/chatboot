<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Application\Services\Conversation\AgentPresenceService;
use Domain\Shared\Enums\AgentStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->clearRateLimiter();
            $request->session()->regenerate();

            $request->user()->update([
                'status' => AgentStatus::Online,
                'last_seen_at' => now(),
            ]);

            return redirect()->intended(route('dashboard'));
        }

        $request->hitRateLimiter();

        return back()
            ->withErrors(['email' => 'Credenciais inválidas.'])
            ->onlyInput('email');
    }

    public function logout(Request $request, AgentPresenceService $presence): RedirectResponse
    {
        if ($request->user()) {
            $presence->markOffline($request->user(), 'logout');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
