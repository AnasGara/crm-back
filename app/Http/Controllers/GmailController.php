<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google_Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GmailController extends Controller
{
    public function connect()
{
    $client = new Google_Client();
    $client->setClientId(config('services.google.client_id'));
    $client->setClientSecret(config('services.google.client_secret'));
    $client->setRedirectUri(route('gmail.callback'));
    $client->addScope([
        'https://www.googleapis.com/auth/gmail.readonly',
        'https://www.googleapis.com/auth/gmail.send',
    ]);
    $client->setAccessType('offline'); 
    $client->setPrompt('consent');

    $user = Auth::user();
    $state = encrypt($user->id);  // Encrypt user ID
    $client->setState($state);

    return redirect($client->createAuthUrl());
}

  public function callback(Request $request)
{
    $code = $request->query('code');
    $state = $request->query('state');

    if (!$code || !$state) {
        return redirect('http://localhost:5173/integrations?connected=error');
    }

    $userId = decrypt($state);
    $user = User::find($userId);
    if (!$user) {
        return redirect('http://localhost:5173/integrations?connected=error');
    }

    $client = new Google_Client();
    $client->setClientId(config('services.google.client_id'));
    $client->setClientSecret(config('services.google.client_secret'));
    $client->setRedirectUri(route('gmail.callback'));

    $token = $client->fetchAccessTokenWithAuthCode($code);

    if (isset($token['error'])) {
        return redirect('http://localhost:5173/integrations?connected=error');
    }

    $user->gmail_access_token = $token['access_token'];
    $user->gmail_refresh_token = $token['refresh_token'] ?? $user->gmail_refresh_token;
    $user->gmail_token_expires_at = now()->addSeconds($token['expires_in'] ?? 3600);
    $user->save();

    return redirect('http://localhost:5173/integrations?connected=success');
}

}
