<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleCalendarAuthController extends Controller
{
    public function redirectToGoogle(): RedirectResponse
    {
        $clientId    = env('GOOGLE_CALENDAR_CLIENT_ID');
        $redirectUri = route('google-calendar.callback');
        $scopes      = 'https://www.googleapis.com/auth/calendar';

        if (! $clientId) {
            return redirect()->back()->with('error', 'Google Calendar Client ID not configured');
        }

        // Generate a secure state parameter and store it in session
        $state = bin2hex(random_bytes(16));
        session(['google_oauth_state' => $state]);

        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id'     => $clientId,
            'response_type' => 'code',
            'redirect_uri'  => $redirectUri,
            'scope'         => $scopes,
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => $state,
        ]);

        // dd($authUrl);

        Log::info('Redirecting to Google Calendar OAuth', ['url' => $authUrl]);

        return redirect($authUrl);
    }

    public function handleCallback(Request $request): RedirectResponse
    {
        $code  = $request->get('code');
        $state = $request->get('state');
        $error = $request->get('error');

        if ($error) {
            Log::error('Google Calendar OAuth error', ['error' => $error]);

            return redirect()->route('emails.index')->with('error', 'Google Calendar authentication failed: ' . $error);
        }

        if (! $code) {
            Log::error('No authorization code received from Google Calendar');

            return redirect()->route('emails.index')->with('error', 'No authorization code received');
        }

        // Verify state parameter
        $expectedState = session('google_oauth_state');
        if ($state !== $expectedState) {
            Log::error('Invalid state parameter in Google Calendar OAuth callback', [
                'received' => $state,
                'expected' => $expectedState,
            ]);

            return redirect()->route('emails.index')->with('error', 'Invalid state parameter');
        }

        // Clear the state from session
        session()->forget('google_oauth_state');

        try {
            $client   = new Client;
            $response = $client->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'client_id'     => env('GOOGLE_CALENDAR_CLIENT_ID'),
                    'client_secret' => env('GOOGLE_CALENDAR_CLIENT_SECRET'),
                    'code'          => $code,
                    'grant_type'    => 'authorization_code',
                    'redirect_uri'  => route('google-calendar.callback'),
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

                file_put_contents(storage_path('app/google_calendar_token.json'), json_encode($tokenData, JSON_PRETTY_PRINT));

                Log::info('Google Calendar authentication successful');

                return redirect()->route('emails.index')->with('success', 'Successfully authenticated with Google Calendar!');
            } else {
                Log::error('No access token in Google Calendar response', $data);

                return redirect()->route('emails.index')->with('error', 'Failed to obtain access token');
            }

        } catch (\Exception $e) {
            Log::error('Google Calendar OAuth token exchange failed: ' . $e->getMessage());

            return redirect()->route('emails.index')->with('error', 'Authentication failed: ' . $e->getMessage());
        }
    }

    public function logout(): RedirectResponse
    {
        $tokenPath = storage_path('app/google_calendar_token.json');

        if (file_exists($tokenPath)) {
            unlink($tokenPath);
            Log::info('Google Calendar tokens cleared');
        }

        return redirect()->route('emails.index')->with('success', 'Google Calendar logged out successfully');
    }
}
