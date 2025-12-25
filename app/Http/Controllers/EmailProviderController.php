<?php

namespace App\Http\Controllers;

use App\Models\EmailProvider;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Services\GoogleService;


class EmailProviderController extends Controller
{



// Add this method to test the connection
public function testConnection(Request $request)
{
    $user = $request->user();
    
    if (!$user) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }
    
    $googleService = new GoogleService();
    $result = $googleService->checkConnection($user->id);
    
    return response()->json([
        'success' => $result['connected'],
        'data' => $result
    ]);
}

// Add this method to send a test email
public function sendTestEmail(Request $request)
{
    $user = $request->user();
    
    if (!$user) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }
    
    $request->validate([
        'to_email' => 'nullable|email'
    ]);
    
    $googleService = new GoogleService();
    $result = $googleService->sendTestEmail($user->id, $request->to_email);
    
    return response()->json($result);
}

// Add this method to refresh token manually
public function refreshToken(Request $request)
{
    $user = $request->user();
    
    if (!$user) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }
    
    $googleService = new GoogleService();
    $success = $googleService->refreshTokenIfNeeded($user->id);
    
    return response()->json([
        'success' => $success,
        'message' => $success ? 'Token refreshed successfully' : 'Failed to refresh token'
    ]);
}


    /**
     * Redirect the user to the provider's authentication page.
     *
     * @param string $provider
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
public function redirect(string $provider, Request $request)
{
    $token = $request->bearerToken();

    if (!$token) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    // Create state with token
    $state = urlencode($token);
    
    // Get the Google driver
    $driver = Socialite::driver($provider);
    
    // IMPORTANT: Add ALL required scopes
    $url = $driver
        ->scopes([
            'https://www.googleapis.com/auth/gmail.send',
            'https://www.googleapis.com/auth/gmail.readonly', // ADD THIS
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile',
            'openid',
        ])
        ->stateless()
        ->with([
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state
        ])
        ->redirect()
        ->getTargetUrl();

    \Log::info('Google OAuth redirect URL generated', [
        'has_access_type_offline' => strpos($url, 'access_type=offline') !== false,
        'has_prompt_consent' => strpos($url, 'prompt=consent') !== false,
        'scopes_in_url' => true,
        'url_preview' => substr($url, 0, 200) . '...',
    ]);

    return response()->json(['url' => $url]);
}

    /**
     * Handle the OAuth callback from Google.
     *
     * @param string $provider
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */

    public function callback(string $provider, Request $request){
    try {
        // Log incoming request parameters
        \Log::info('=== GOOGLE OAUTH CALLBACK STARTED ===');
        \Log::info('Callback URL: ' . $request->fullUrl());
        \Log::info('Provider: ' . $provider);
        \Log::info('Callback Parameters:', [
            'state' => $request->state,
            'state_decoded' => urldecode($request->state ?? ''),
            'code' => $request->code ? 'Present (' . strlen($request->code) . ' chars)' : 'Missing',
            'scope' => $request->scope,
            'authuser' => $request->authuser,
            'prompt' => $request->prompt,
            'all_params' => $request->all(),
        ]);

        // Get user from Google
        \Log::info('Attempting to get user from Socialite...');
        $providerUser = Socialite::driver($provider)->stateless()->user();
        
        // Log Socialite response
        \Log::info('=== SOCIALITE RESPONSE ===');
        \Log::info('Google User Data:', [
            'has_token' => !empty($providerUser->token) ? 'Yes (' . strlen($providerUser->token) . ' chars)' : 'No',
            'has_refresh_token' => !empty($providerUser->refreshToken) ? 'Yes (' . strlen($providerUser->refreshToken) . ' chars)' : 'No',
            'expires_in' => $providerUser->expiresIn,
            'expires_at_calculated' => now()->addSeconds($providerUser->expiresIn)->toDateTimeString(),
            'token_type' => $providerUser->token['token_type'] ?? 'N/A',
            'user_id' => $providerUser->getId(),
            'user_id_type' => gettype($providerUser->getId()),
            'email' => $providerUser->getEmail(),
            'name' => $providerUser->getName(),
            'nickname' => $providerUser->getNickname(),
            'avatar' => $providerUser->getAvatar(),
            'raw' => $providerUser->getRaw(), // Be careful with this as it might contain sensitive data
        ]);

        // Extract token from state
        $token = urldecode($request->state) ?? null;
        \Log::info('=== TOKEN PARSING ===');
        \Log::info('State token: ' . ($token ? substr($token, 0, 50) . '...' : 'NULL'));
        
        if (!$token) {
            \Log::error('Missing state token');
            abort(401, 'Missing state token');
        }

        // Parse token
        $tokenParts = explode('|', $token);
        \Log::info('Token parsed into ' . count($tokenParts) . ' parts');
        if (count($tokenParts) === 2) {
            \Log::info('Token ID: ' . $tokenParts[0]);
            \Log::info('Token plain text (first 20 chars): ' . substr($tokenParts[1], 0, 20) . '...');
        }

        // Find user by token
        \Log::info('=== FINDING USER BY TOKEN ===');
        $user = \App\Models\User::whereHas('tokens', function($query) use ($token, $tokenParts) {
            if (count($tokenParts) === 2) {
                $tokenId = $tokenParts[0];
                \Log::info('Searching by token ID: ' . $tokenId);
                $query->where('id', $tokenId);
            } else {
                \Log::info('Searching by hashed token');
                $query->where('token', hash('sha256', $token));
            }
        })->first();
        
        if (!$user) {
            \Log::error('User not found for token', [
                'token_preview' => substr($token, 0, 100),
                'token_parts_count' => count($tokenParts),
                'all_tokens_count' => \App\Models\PersonalAccessToken::count(),
            ]);
            
            // Debug: List all tokens
            $allTokens = \App\Models\PersonalAccessToken::select('id', 'tokenable_type', 'tokenable_id', 'name', 'created_at')
                ->limit(10)
                ->get();
            \Log::error('First 10 tokens in database:', $allTokens->toArray());
            
            abort(401, 'Invalid token');
        }
        
        \Log::info('User found:', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_name' => $user->name,
        ]);

        // Check if email provider already exists
        \Log::info('=== CHECKING EXISTING EMAIL PROVIDER ===');
        $existingProvider = \App\Models\EmailProvider::where('user_id', $user->id)
            ->where('provider', $provider)
            ->first();
            
        if ($existingProvider) {
            \Log::info('Existing provider found:', [
                'id' => $existingProvider->id,
                'current_access_token' => $existingProvider->access_token ? 'Set (' . substr($existingProvider->access_token, 0, 20) . '...)' : 'NULL',
                'current_refresh_token' => $existingProvider->refresh_token ? 'Set (' . substr($existingProvider->refresh_token, 0, 20) . '...)' : 'NULL',
                'current_expires_at' => $existingProvider->expires_at,
            ]);
        } else {
            \Log::info('No existing provider found - creating new one');
        }

 // Store or update the email provider
\Log::info('=== STORING/UPDATING EMAIL PROVIDER ===');

// Get the email provider first
// Store or update the email provider
\Log::info('=== STORING/UPDATING EMAIL PROVIDER ===');

// Log what we're trying to save
$dataToSave = [
    'user_id' => $user->id,
    'provider' => $provider,
    'access_token' => $providerUser->token,
    'refresh_token' => $providerUser->refreshToken,
    'expires_at' => now()->addSeconds($providerUser->expiresIn),
    'provider_user_id' => $providerUser->getId(),
    'provider_email' => $providerUser->getEmail(),
    'connected' => true,
];

\Log::info('Data to save:', [
    'provider_email_in_data' => $dataToSave['provider_email'],
    'connected_in_data' => $dataToSave['connected'],
    'full_data_keys' => array_keys($dataToSave),
]);

// Get the email provider first
$emailProvider = \App\Models\EmailProvider::where('user_id', $user->id)
    ->where('provider', $provider)
    ->first();

if ($emailProvider) {
    // Update existing record
    $emailProvider->fill($dataToSave);
    $saved = $emailProvider->save();
    
    \Log::info('Updated existing provider', [
        'saved' => $saved,
        'changes' => $emailProvider->getChanges(),
    ]);
} else {
    // Create new record
    $emailProvider = \App\Models\EmailProvider::create($dataToSave);
    
    \Log::info('Created new provider', [
        'id' => $emailProvider->id,
        'was_saved' => !is_null($emailProvider->id),
    ]);
}
        
        \Log::info('Email provider saved:', [
            'provider_id' => $emailProvider->id,
            'user_id' => $emailProvider->user_id,
            'provider' => $emailProvider->provider,
            'provider_email' => $emailProvider->provider_email,
            'access_token_set' => !empty($emailProvider->access_token) ? 'Yes' : 'No',
            'refresh_token_set' => !empty($emailProvider->refresh_token) ? 'Yes' : 'No',
            'expires_at' => $emailProvider->expires_at,
            'connected' => $emailProvider->connected,
        ]);

        // Verify the record was saved
        $verifyProvider = \App\Models\EmailProvider::find($emailProvider->id);
        \Log::info('Database verification:', [
            'record_exists' => $verifyProvider ? 'Yes' : 'No',
            'access_token_match' => $verifyProvider && $verifyProvider->access_token === $providerUser->token ? 'Yes' : 'No',
            'refresh_token_match' => $verifyProvider && $verifyProvider->refresh_token === $providerUser->refreshToken ? 'Yes' : 'No',
        ]);

        // Redirect to your React frontend
        $frontendUrl = 'http://localhost:5173';
        $redirectUrl = $frontendUrl . '/integrations?connected=success&provider=' . $provider;
        
        \Log::info('=== REDIRECTING TO FRONTEND ===');
        \Log::info('Redirect URL: ' . $redirectUrl);
        \Log::info('=== GOOGLE OAUTH CALLBACK COMPLETED SUCCESSFULLY ===');
        
        return \Illuminate\Support\Facades\Redirect::away($redirectUrl);
        
    } catch (\Exception $e) {
        \Log::error('=== GOOGLE OAUTH CALLBACK FAILED ===');
        \Log::error('Error Message: ' . $e->getMessage());
        \Log::error('Error Code: ' . $e->getCode());
        \Log::error('File: ' . $e->getFile());
        \Log::error('Line: ' . $e->getLine());
        \Log::error('Trace:', ['trace' => $e->getTraceAsString()]);
        
        // Log request details for debugging
        \Log::error('Request details on error:', [
            'full_url' => $request->fullUrl(),
            'state' => $request->state,
            'code_present' => !empty($request->code),
            'provider' => $provider,
        ]);
        
        // Redirect to frontend with error
        $frontendUrl = 'http://localhost:5173';
        $errorMessage = urlencode($e->getMessage());
        
        \Log::info('Redirecting to frontend with error: ' . $errorMessage);
        return \Illuminate\Support\Facades\Redirect::away($frontendUrl . '/integrations?connected=error&message=' . $errorMessage);
    }
}
}
