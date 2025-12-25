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
    try {
        $providerUser = Socialite::driver($provider)->stateless()->user();

        // Extract token from state (URL decoded)
        $token = urldecode($request->state) ?? null;
        
        if (!$token) {
            abort(401, 'Missing state token');
        }

        // Find user by token (Sanctum)
        $user = \App\Models\User::whereHas('tokens', function($query) use ($token) {
            $tokenParts = explode('|', $token);
            if (count($tokenParts) === 2) {
                $tokenId = $tokenParts[0];
                $query->where('id', $tokenId);
            } else {
                $query->where('token', hash('sha256', $token));
            }
        })->first();
        
        if (!$user) {
            \Log::error('User not found for token', ['token' => $token]);
            abort(401, 'Invalid token');
        }

        // Store or update the email provider
        \App\Models\EmailProvider::updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $provider,
            ],
            [
                'access_token' => $providerUser->token,
                'refresh_token' => $providerUser->refreshToken,
                'expires_at' => now()->addSeconds($providerUser->expiresIn),
                'provider_user_id' => $providerUser->getId(),
                'provider_email' => $providerUser->getEmail(),
                'connected' => true,
            ]
        );

        // HARDCODE the correct frontend URL
        $frontendUrl = 'http://localhost:5173';
        
        return redirect($frontendUrl . '/integrations?connected=success&provider=' . $provider);
        
    } catch (\Exception $e) {
        \Log::error('OAuth callback error: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        
        // HARDCODE the correct frontend URL
        $frontendUrl = 'http://localhost:5173';
        return redirect($frontendUrl . '/integrations?connected=error&message=' . urlencode($e->getMessage()));
    }
}
}
