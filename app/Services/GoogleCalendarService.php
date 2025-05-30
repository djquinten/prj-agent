<?php

namespace App\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    private $client;

    private $accessToken;

    public function __construct()
    {
        $this->loadAccessToken();

        if ($this->accessToken) {
            $this->client = new Client([
                'base_uri' => 'https://www.googleapis.com/calendar/v3/',
                'headers'  => [
                    'Authorization' => "Bearer {$this->accessToken}",
                    'Content-Type'  => 'application/json',
                ],
            ]);
        }
    }

    /**
     * Load access token from storage
     */
    private function loadAccessToken(): bool
    {
        $tokenPath = storage_path('app/google_calendar_token.json');

        if (file_exists($tokenPath)) {
            $tokenData = json_decode(file_get_contents($tokenPath), true);

            // Check if token is expired
            if (time() < $tokenData['expires']) {
                $this->accessToken = $tokenData['access_token'];

                return true;
            } else {
                Log::info('Google Calendar access token has expired, attempting to refresh...');

                // Try to refresh the token if we have a refresh token
                if (isset($tokenData['refresh_token'])) {
                    return $this->refreshToken($tokenData['refresh_token']);
                } else {
                    Log::warning('No refresh token available for Google Calendar');
                }
            }
        }

        return false;
    }

    /**
     * Refresh the access token using the refresh token
     */
    private function refreshToken(string $refreshToken): bool
    {
        try {
            $httpClient = new Client;

            $response = $httpClient->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'client_id'     => env('GOOGLE_CALENDAR_CLIENT_ID'),
                    'client_secret' => env('GOOGLE_CALENDAR_CLIENT_SECRET'),
                    'refresh_token' => $refreshToken,
                    'grant_type'    => 'refresh_token',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data['access_token'])) {
                // Save the new token data
                $tokenData = [
                    'access_token'  => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? $refreshToken,
                    'expires'       => time() + ($data['expires_in'] ?? 3600),
                    'created_at'    => time(),
                ];

                file_put_contents(storage_path('app/google_calendar_token.json'), json_encode($tokenData, JSON_PRETTY_PRINT));

                $this->accessToken = $data['access_token'];

                // Recreate the HTTP client with the new token
                $this->client = new Client([
                    'base_uri' => 'https://www.googleapis.com/calendar/v3/',
                    'headers'  => [
                        'Authorization' => "Bearer {$this->accessToken}",
                        'Content-Type'  => 'application/json',
                    ],
                ]);

                Log::info('Google Calendar access token refreshed successfully');

                return true;
            }

        } catch (\Exception $e) {
            Log::error('Failed to refresh Google Calendar token: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Check if the service is properly authenticated
     */
    public function isAuthenticated(): bool
    {
        return ! empty($this->accessToken);
    }

    /**
     * Create a calendar event
     */
    public function createEvent(array $eventData): array
    {
        if (! $this->isAuthenticated()) {
            return [
                'success' => false,
                'error'   => 'Not authenticated with Google Calendar',
            ];
        }

        try {
            $calendarId = $eventData['calendar_id'] ?? 'primary';

            $event = [
                'summary'     => $eventData['title'],
                'description' => $eventData['description'] ?? '',
                'start'       => [
                    'dateTime' => $eventData['start_time'],
                    'timeZone' => $eventData['timezone'] ?? config('app.timezone', 'UTC'),
                ],
                'end' => [
                    'dateTime' => $eventData['end_time'],
                    'timeZone' => $eventData['timezone'] ?? config('app.timezone', 'UTC'),
                ],
            ];

            // Add location if provided
            if (! empty($eventData['location'])) {
                $event['location'] = $eventData['location'];
            }

            // Add attendees if provided
            if (! empty($eventData['attendees'])) {
                $event['attendees'] = collect($eventData['attendees'])
                    ->map(fn ($email) => ['email' => $email])
                    ->toArray();
            }

            // Add reminders
            if (! empty($eventData['reminders'])) {
                $event['reminders'] = [
                    'useDefault' => false,
                    'overrides'  => $eventData['reminders'],
                ];
            } else {
                $event['reminders'] = ['useDefault' => true];
            }

            $response = $this->client->post("calendars/{$calendarId}/events", [
                'json' => $event,
            ]);

            $data = json_decode($response->getBody(), true);

            Log::info('📅 Google Calendar event created', [
                'event_id'   => $data['id'],
                'title'      => $eventData['title'],
                'start_time' => $eventData['start_time'],
            ]);

            return [
                'success'   => true,
                'event_id'  => $data['id'],
                'event_url' => $data['htmlLink'],
                'message'   => 'Calendar event created successfully',
            ];

        } catch (RequestException $e) {
            Log::error('Google Calendar API error: ' . $e->getMessage());

            return [
                'success' => false,
                'error'   => 'Failed to create calendar event: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get calendar list
     */
    public function getCalendars(): array
    {
        if (! $this->isAuthenticated()) {
            return [
                'success'   => false,
                'error'     => 'Not authenticated with Google Calendar',
                'calendars' => [],
            ];
        }

        try {
            $response = $this->client->get('users/me/calendarList');
            $data     = json_decode($response->getBody(), true);

            $calendars = collect($data['items'] ?? [])
                ->map(fn ($calendar) => [
                    'id'          => $calendar['id'],
                    'name'        => $calendar['summary'],
                    'primary'     => $calendar['primary'] ?? false,
                    'access_role' => $calendar['accessRole'] ?? 'reader',
                ])
                ->toArray();

            return [
                'success'   => true,
                'calendars' => $calendars,
            ];

        } catch (RequestException $e) {
            Log::error('Google Calendar API error getting calendars: ' . $e->getMessage());

            return [
                'success'   => false,
                'error'     => 'Failed to get calendars: ' . $e->getMessage(),
                'calendars' => [],
            ];
        }
    }

    /**
     * Parse meeting information from email content
     */
    public function parseMeetingFromEmail(string $subject, string $content): ?array
    {
        // Common meeting patterns
        $meetingPatterns = [
            '/meeting/i',
            '/call/i',
            '/conference/i',
            '/appointment/i',
            '/interview/i',
            '/demo/i',
            '/presentation/i',
            '/sync/i',
            '/standup/i',
            '/review/i',
        ];

        $hasMeetingKeyword = false;
        foreach ($meetingPatterns as $pattern) {
            if (preg_match($pattern, $subject . ' ' . $content)) {
                $hasMeetingKeyword = true;
                break;
            }
        }

        if (! $hasMeetingKeyword) {
            return null;
        }

        // Try to extract date/time information
        $dateTimeInfo = $this->extractDateTime($content);

        if (! $dateTimeInfo) {
            return null;
        }

        // Extract location if present
        $location = $this->extractLocation($content);

        return [
            'title'       => $this->cleanSubject($subject),
            'start_time'  => $dateTimeInfo['start'],
            'end_time'    => $dateTimeInfo['end'],
            'location'    => $location,
            'description' => $this->extractDescription($content),
        ];
    }

    /**
     * Extract date/time from email content
     */
    private function extractDateTime(string $content): ?array
    {
        // Common date patterns
        $patterns = [
            // "January 15, 2024 at 2:00 PM"
            '/(\w+\s+\d{1,2},\s+\d{4})\s+at\s+(\d{1,2}:\d{2}\s*(?:AM|PM))/i',
            // "15/01/2024 14:00"
            '/(\d{1,2}\/\d{1,2}\/\d{4})\s+(\d{1,2}:\d{2})/i',
            // "2024-01-15 14:00"
            '/(\d{4}-\d{1,2}-\d{1,2})\s+(\d{1,2}:\d{2})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                try {
                    $dateStr = $matches[1];
                    $timeStr = $matches[2];

                    $startTime = Carbon::parse("$dateStr $timeStr");
                    $endTime   = $startTime->copy()->addHour(); // Default 1 hour duration

                    return [
                        'start' => $startTime->toISOString(),
                        'end'   => $endTime->toISOString(),
                    ];
                } catch (\Exception $e) {
                    Log::warning('Failed to parse date/time from email', [
                        'date'  => $dateStr ?? '',
                        'time'  => $timeStr ?? '',
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return null;
    }

    /**
     * Extract location from email content
     */
    private function extractLocation(string $content): ?string
    {
        $locationPatterns = [
            '/location:\s*([^\n\r]+)/i',
            '/where:\s*([^\n\r]+)/i',
            '/venue:\s*([^\n\r]+)/i',
            '/address:\s*([^\n\r]+)/i',
        ];

        foreach ($locationPatterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    /**
     * Clean subject line for calendar title
     */
    private function cleanSubject(string $subject): string
    {
        // Remove common email prefixes
        $subject = preg_replace('/^(RE:|FW:|FWD:)\s*/i', '', $subject);

        return trim($subject);
    }

    /**
     * Extract description from email content
     */
    private function extractDescription(string $content): string
    {
        // Limit description length and clean up
        $description = strip_tags($content);
        $description = preg_replace('/\s+/', ' ', $description);

        if (strlen($description) > 500) {
            $description = substr($description, 0, 500) . '...';
        }

        return trim($description);
    }
}
