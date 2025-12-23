<?php

namespace App\Http\Controllers;

use App\Models\EmailProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class EmailProviderController extends Controller
{
    /**
     * Redirect the user to the provider's authentication page.
     *
     * @param string $provider
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect(string $provider)
    {
        return Socialite::driver($provider)->scopes(['https://www.googleapis.com/auth/gmail.send'])->redirect();
    }

    /**
     * Obtain the user information from the provider.
     *
     * @param string $provider
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(string $provider)
    {
        $providerUser = Socialite::driver($provider)->user();

        EmailProvider::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'provider' => $provider,
            ],
            [
                'access_token' => $providerUser->token,
                'refresh_token' => $providerUser->refreshToken,
                'expires_at' => now()->addSeconds($providerUser->expiresIn),
            ]
        );

        return redirect('/dashboard');
    }
}
