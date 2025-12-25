<?php

namespace App\Http\Controllers;

use Google\Client;
use Google\Service\Gmail;
use Illuminate\Http\Request;
use App\Models\EmailProvider;
use Illuminate\Support\Facades\Auth;

class GoogleMailController extends Controller
{
    private function createGoogleClient()
    {
        $client = new Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->addScope(Gmail::GMAIL_SEND);

        return $client;
    }

    public function connect()
    {
        $client = $this->createGoogleClient();
        $authUrl = $client->createAuthUrl();

        return redirect()->away($authUrl);
    }

    public function callback(Request $request)
    {
        if (!$request->has('code')) {
            return response()->json(['error' => 'Authorization failed'], 400);
        }

        $client = $this->createGoogleClient();
        $token = $client->fetchAccessTokenWithAuthCode($request->code);

        if (isset($token['error'])) {
            return response()->json(['error' => 'Token exchange failed'], 400);
        }

        $googleUser = $client->verifyIdToken($token['id_token']);

        // Save to email_providers
        EmailProvider::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'provider' => 'google',
            ],
            [
                'access_token' => $token['access_token'],
                'refresh_token' => $token['refresh_token'] ?? null,
                'expires_at' => now()->addSeconds($token['expires_in']),
            ]
        );

        return redirect(config('app.frontend_url') . '/emails/connected');
    }

    private function getAuthorizedClient($provider)
    {
        $client = $this->createGoogleClient();

        $client->setAccessToken([
            'access_token' => $provider->access_token,
            'refresh_token' => $provider->refresh_token,
            'expires_in'   => $provider->expires_at?->timestamp ?? 0,
            'created'      => time()
        ]);

        // Refresh if expired
        if ($client->isAccessTokenExpired() && $provider->refresh_token) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($provider->refresh_token);

            $provider->update([
                'access_token' => $newToken['access_token'],
                'expires_at'   => now()->addSeconds($newToken['expires_in'])
            ]);

            $client->setAccessToken($newToken);
        }

        return new Gmail($client);
    }

    public function sendEmail(Request $request)
    {
        $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string',
            'body' => 'required|string'
        ]);

        $provider = EmailProvider::where('user_id', Auth::id())
            ->where('provider', 'google')
            ->first();

        if (!$provider) {
            return response()->json(['message' => 'Google account not connected'], 400);
        }

        $gmail = $this->getAuthorizedClient($provider);

        $rawMessage = "To: {$request->to}\r\n";
        $rawMessage .= "Subject: {$request->subject}\r\n";
        $rawMessage .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
        $rawMessage .= $request->body;

        $encodedMessage = rtrim(strtr(base64_encode($rawMessage), '+/', '-_'), '=');

        $message = new Gmail\Message();
        $message->setRaw($encodedMessage);

        $gmail->users_messages->send('me', $message);

        return response()->json(['message' => 'Email sent successfully']);
    }
}
