<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function redirectToMicrosoft(): RedirectResponse
    {
        $clientId    = env('MICROSOFT_GRAPH_CLIENT_ID');
        $redirectUri = route('auth.callback');
        $scopes      = 'https://graph.microsoft.com/Mail.Read https://graph.microsoft.com/Mail.ReadWrite offline_access';

        if (! $clientId) {
            return redirect()->back()->with('error', 'Microsoft Graph Client ID not configured');
        }

        $authUrl = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?' . http_build_query([
            'client_id'     => $clientId,
            'response_type' => 'code',
            'redirect_uri'  => $redirectUri,
            'scope'         => $scopes,
            'response_mode' => 'query',
            'state'         => csrf_token(),
        ]);

        Log::info('Redirecting to Microsoft OAuth', ['url' => $authUrl]);

        return redirect($authUrl);
    }

    public function handleCallback(Request $request): RedirectResponse
    {
        $code  = $request->get('code');
        $state = $request->get('state');
        $error = $request->get('error');

        // Check for OAuth errors
        if ($error) {
            Log::error('OAuth error: ' . $error);

            return redirect()->route('emails.index')->with('error', 'Authentication failed: ' . $error);
        }

        // Verify CSRF token
        if ($state !== csrf_token()) {
            Log::error('CSRF token mismatch in OAuth callback');

            return redirect()->route('emails.index')->with('error', 'Invalid authentication state');
        }

        if (! $code) {
            Log::error('No authorization code received');

            return redirect()->route('emails.index')->with('error', 'No authorization code received');
        }

        // Exchange authorization code for access token
        try {
            $client   = new Client;
            $response = $client->post('https://login.microsoftonline.com/common/oauth2/v2.0/token', [
                'form_params' => [
                    'client_id'     => env('MICROSOFT_GRAPH_CLIENT_ID'),
                    'client_secret' => env('MICROSOFT_GRAPH_CLIENT_SECRET'),
                    'code'          => $code,
                    'grant_type'    => 'authorization_code',
                    'redirect_uri'  => route('auth.callback'),
                    'scope'         => 'https://graph.microsoft.com/Mail.Read https://graph.microsoft.com/Mail.ReadWrite offline_access',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data['access_token'])) {
                // Save token data
                $tokenData = [
                    'access_token'  => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? null,
                    'expires'       => time() + ($data['expires_in'] ?? 3600),
                    'created_at'    => time(),
                ];

                // Ensure storage directory exists
                $storageDir = storage_path('app');
                if (! is_dir($storageDir)) {
                    mkdir($storageDir, 0755, true);
                }

                file_put_contents(storage_path('app/access_token.json'), json_encode($tokenData, JSON_PRETTY_PRINT));

                Log::info('Microsoft Graph authentication successful');

                return redirect()->route('emails.index')->with('success', 'Successfully authenticated with Microsoft Graph!');
            } else {
                Log::error('No access token in response', $data);

                return redirect()->route('emails.index')->with('error', 'Failed to obtain access token');
            }

        } catch (\Exception $e) {
            Log::error('OAuth token exchange failed: ' . $e->getMessage());

            return redirect()->route('emails.index')->with('error', 'Authentication failed: ' . $e->getMessage());
        }
    }

    public function logout(): RedirectResponse
    {
        $tokenPath = storage_path('app/access_token.json');

        if (file_exists($tokenPath)) {
            unlink($tokenPath);
            Log::info('Microsoft Graph tokens cleared');
        }

        return redirect()->route('emails.index')->with('success', 'Logged out successfully');
    }
}
