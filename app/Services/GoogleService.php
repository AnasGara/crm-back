<?php

namespace App\Services;

use App\Models\EmailProvider;
use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Illuminate\Support\Facades\Log;

class GoogleService
{
    /**
     * Refresh Google access token if expired or about to expire
     */
    public function refreshTokenIfNeeded($userId)
    {
        try {
            $provider = EmailProvider::where('user_id', $userId)
                ->where('provider', 'google')
                ->first();
            
            if (!$provider) {
                Log::warning('No Google provider found for user', ['user_id' => $userId]);
                return false;
            }
            
            if (!$provider->refresh_token) {
                Log::warning('No refresh token for Google provider', ['user_id' => $userId]);
                return false;
            }
            
            // Check if token is expired or will expire in the next 5 minutes
            if ($provider->expires_at && $provider->expires_at->subMinutes(5)->isFuture()) {
                Log::debug('Google token still valid', [
                    'user_id' => $userId,
                    'expires_at' => $provider->expires_at,
                    'minutes_remaining' => now()->diffInMinutes($provider->expires_at)
                ]);
                return true;
            }
            
            Log::info('Refreshing Google token', [
                'user_id' => $userId,
                'token_expired' => $provider->expires_at ? $provider->expires_at->isPast() : 'unknown'
            ]);
            
            $client = $this->getGoogleClient();
            $client->refreshToken($provider->refresh_token);
            
            $newToken = $client->getAccessToken();
            
            if (!isset($newToken['access_token'])) {
                Log::error('Failed to get new access token from Google', ['user_id' => $userId]);
                return false;
            }
            
            // Update the stored token
            $provider->update([
                'access_token' => $newToken['access_token'],
                'expires_at' => now()->addSeconds($newToken['expires_in']),
                'refresh_token' => $newToken['refresh_token'] ?? $provider->refresh_token,
            ]);
            
            Log::info('Google token refreshed successfully', [
                'user_id' => $userId,
                'new_expires_at' => $provider->expires_at
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Error refreshing Google token', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // If refresh token is invalid, mark as disconnected
            if (str_contains($e->getMessage(), 'invalid_grant')) {
                $provider->update(['connected' => false]);
                Log::warning('Marked Google provider as disconnected due to invalid grant', ['user_id' => $userId]);
            }
            
            return false;
        }
    }
    
    /**
     * Get authenticated Google Client for a user
     */
    public function getAuthenticatedClient($userId)
    {
        if (!$this->refreshTokenIfNeeded($userId)) {
            return null;
        }
        
        $provider = EmailProvider::where('user_id', $userId)
            ->where('provider', 'google')
            ->first();
        
        if (!$provider || !$provider->access_token) {
            return null;
        }
        
        $client = $this->getGoogleClient();
        $client->setAccessToken($provider->access_token);
        
        return $client;
    }
    
    /**
     * Send a test email via Gmail
     */
    public function sendTestEmail($userId, $toEmail = null)
    {
        try {
            $provider = EmailProvider::where('user_id', $userId)
                ->where('provider', 'google')
                ->first();
            
            if (!$provider) {
                return [
                    'success' => false,
                    'message' => 'No Google account connected'
                ];
            }
            
            $client = $this->getAuthenticatedClient($userId);
            if (!$client) {
                return [
                    'success' => false,
                    'message' => 'Failed to authenticate with Google'
                ];
            }
            
            $gmail = new Gmail($client);
            
            // Use provider's email as default recipient
            $toEmail = $toEmail ?: $provider->provider_email;
            
            // Create the message
            $message = new Message();
            
            $rawMessage = "From: {$provider->provider_email}\r\n";
            $rawMessage .= "To: {$toEmail}\r\n";
            $rawMessage .= "Subject: Test Email from Your CRM\r\n";
            $rawMessage .= "Content-Type: text/html; charset=utf-8\r\n";
            $rawMessage .= "\r\n";
            $rawMessage .= "<h1>Test Email Successful!</h1>";
            $rawMessage .= "<p>This is a test email sent from your CRM integration with Google.</p>";
            $rawMessage .= "<p>If you received this email, your Gmail integration is working correctly!</p>";
            
            $message->setRaw(base64_encode($rawMessage));
            
            // Send the email
            $result = $gmail->users_messages->send('me', $message);
            
            Log::info('Test email sent successfully', [
                'user_id' => $userId,
                'from' => $provider->provider_email,
                'to' => $toEmail,
                'message_id' => $result->getId()
            ]);
            
            return [
                'success' => true,
                'message' => 'Test email sent successfully!',
                'message_id' => $result->getId()
            ];
            
        } catch (\Exception $e) {
            Log::error('Error sending test email', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get user's Gmail profile
     */
    public function getGmailProfile($userId)
    {
        try {
            $client = $this->getAuthenticatedClient($userId);
            if (!$client) {
                return null;
            }
            
            $gmail = new Gmail($client);
            $profile = $gmail->users->getProfile('me');
            
            return [
                'email_address' => $profile->getEmailAddress(),
                'messages_total' => $profile->getMessagesTotal(),
                'threads_total' => $profile->getThreadsTotal(),
                'history_id' => $profile->getHistoryId(),
            ];
            
        } catch (\Exception $e) {
            Log::error('Error getting Gmail profile', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Check if user's Google connection is valid
     */
public function checkConnection($userId)
{
    try {
        \Log::info('=== CHECKING GOOGLE CONNECTION ===', ['user_id' => $userId]);
        
        $provider = EmailProvider::where('user_id', $userId)
            ->where('provider', 'google')
            ->first();
        
        if (!$provider) {
            \Log::warning('No Google provider found in database', ['user_id' => $userId]);
            return [
                'connected' => false,
                'message' => 'No Google account connected'
            ];
        }
        
        \Log::info('Provider found in database:', [
            'id' => $provider->id,
            'has_access_token' => !empty($provider->access_token),
            'has_refresh_token' => !empty($provider->refresh_token),
            'expires_at' => $provider->expires_at,
            'connected' => $provider->connected,
            'provider_email' => $provider->provider_email,
        ]);
        
        // First try to refresh token if needed
        $refreshResult = $this->refreshTokenIfNeeded($userId);
        \Log::info('Token refresh result:', ['success' => $refreshResult, 'user_id' => $userId]);
        
        if (!$refreshResult) {
            return [
                'connected' => false,
                'message' => 'Failed to refresh Google token'
            ];
        }
        
        // Get fresh provider data after refresh
        $provider->refresh();
        
        // SIMPLER TEST: Just check if we can get user info (uses userinfo scope)
        $userInfo = $this->getUserInfo($userId);
        
        if ($userInfo) {
            \Log::info('User info retrieved successfully:', ['user_id' => $userId, 'email' => $userInfo['email']]);
            return [
                'connected' => true,
                'message' => 'Connection valid',
                'email' => $provider->provider_email,
                'expires_at' => $provider->expires_at,
                'user_info' => $userInfo
            ];
        }
        
        \Log::warning('Failed to get user info', ['user_id' => $userId]);
        return [
            'connected' => false,
            'message' => 'Failed to verify connection'
        ];
        
    } catch (\Exception $e) {
        \Log::error('Error checking Google connection:', [
            'user_id' => $userId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return [
            'connected' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

public function getUserInfo($userId)
{
    try {
        $client = $this->getAuthenticatedClient($userId);
        if (!$client) {
            \Log::error('Failed to get authenticated client', ['user_id' => $userId]);
            return null;
        }
        
        // Use OAuth2 userinfo endpoint (requires userinfo.email scope)
        $oauth2 = new \Google\Service\Oauth2($client);
        $userInfo = $oauth2->userinfo->get();
        
        \Log::info('User info response:', [
            'email' => $userInfo->getEmail(),
            'name' => $userInfo->getName(),
            'picture' => $userInfo->getPicture(),
        ]);
        
        return [
            'email' => $userInfo->getEmail(),
            'name' => $userInfo->getName(),
            'picture' => $userInfo->getPicture(),
            'verified_email' => $userInfo->getVerifiedEmail(),
        ];
        
    } catch (\Exception $e) {
        \Log::error('Error getting user info:', [
            'user_id' => $userId,
            'error' => $e->getMessage(),
            'error_type' => get_class($e),
        ]);
        return null;
    }
}


    
    /**
     * Create Google Client instance
     */
 private function getGoogleClient()
{
    $client = new Client();
    $client->setApplicationName(config('app.name', 'CRM Application'));
    $client->setClientId(config('services.google.client_id'));
    $client->setClientSecret(config('services.google.client_secret'));
    $client->setRedirectUri(url('/email-provider/google/callback'));
    $client->setScopes([
        'https://www.googleapis.com/auth/gmail.send',
        'https://www.googleapis.com/auth/gmail.readonly',
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/userinfo.profile',
    ]);
    $client->setAccessType('offline');
    $client->setPrompt('consent');
    
    return $client;
}
}