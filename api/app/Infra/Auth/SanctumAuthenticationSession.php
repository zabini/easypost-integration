<?php

namespace App\Infra\Auth;

use App\Core\Domain\Contracts\Auth\AuthenticationSession;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Http\Request;

final readonly class SanctumAuthenticationSession implements AuthenticationSession
{
    public function __construct(
        private AuthFactory $auth,
        private Request $request,
    ) {
    }

    public function login(int $userId): void
    {
        $user = User::query()->findOrFail($userId);

        $this->auth->guard('web')->login($user);
        $this->request->session()->regenerate();
    }

    public function logout(): void
    {
        $user = $this->request->user('sanctum');
        $currentAccessToken = $user?->currentAccessToken();

        if ($currentAccessToken !== null && method_exists($currentAccessToken, 'delete')) {
            $currentAccessToken->delete();
        }

        $this->auth->guard('web')->logout();
        $this->request->session()->invalidate();
        $this->request->session()->regenerateToken();

        Cookie::queue(Cookie::forget(config('session.cookie')));
        Cookie::queue(Cookie::forget('XSRF-TOKEN'));

        Auth::forgetGuards();
        $this->request->setUserResolver(static fn () => null);
    }

    public function currentUserId(): ?int
    {
        return $this->request->user('sanctum')?->getAuthIdentifier()
            ?? $this->auth->guard('web')->id();
    }
}
