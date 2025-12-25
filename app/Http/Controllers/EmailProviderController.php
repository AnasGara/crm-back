<?php

namespace App\Http\Controllers;

use App\Models\EmailProvider;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class EmailProviderController extends Controller
{
    /**
     * Redirect the user to the provider's authentication page.
     *
     * @param string $provider
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function redirect(string $provider, Request $request)
    {
        // The frontend will pass the Sanctum token in headers
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // We encode the token in "state" to restore the user in the callback
        $state = urlencode($token);
        $url = Socialite::driver($provider)
            ->scopes(['https://www.googleapis.com/auth/gmail.send'])
            ->stateless() // SPA cannot rely on session
            ->with(['state' => $state])
            ->redirect()
            ->getTargetUrl();

        return response()->json(['url' => $url]);
    }

    /**
     * Handle the OAuth callback from Google.
     *
     * @param string $provider
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(string $provider, Request $request)
    {
        $providerUser = Socialite::driver($provider)->stateless()->user();

        // Extract token from state
        $token = $request->state ?? null;
        if (!$token) {
            abort(401, 'Missing state token');
        }

        // Find user by token (Sanctum)
        $user = User::whereHas('tokens', fn($q) => $q->where('id', $token))->first();
        if (!$user) {
            abort(401, 'Invalid token');
        }

        Auth::login($user);

        EmailProvider::updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $provider,
            ],
            [
                'access_token' => $providerUser->token,
                'refresh_token' => $providerUser->refreshToken,
                'expires_at' => now()->addSeconds($providerUser->expiresIn),
            ]
        );

        return redirect(config('app.frontend_url', '/dashboard'));
    }
}
