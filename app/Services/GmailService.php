<?php

namespace App\Services;

use Google_Client;
use Illuminate\Support\Facades\Auth;

class GmailService
{
    public static function getClient(): Google_Client
    {
        $user = Auth::user();
        if (!$user) throw new \Exception('User not authenticated');

        $client = new Google_Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setAccessToken([
            'access_token' => $user->gmail_access_token,
            'refresh_token' => $user->gmail_refresh_token,
            'expires_in' => $user->gmail_token_expires_at?->diffInSeconds(now()),
        ]);

        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($user->gmail_refresh_token);
            $newToken = $client->getAccessToken();
            $user->gmail_access_token = $newToken['access_token'];
            $user->gmail_token_expires_at = now()->addSeconds($newToken['expires_in'] ?? 3600);
            $user->save();
        }

        return $client;
    }
}
