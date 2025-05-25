<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class MicrosoftGraphService
{
    private $client;

    private $accessToken;

    public function __construct()
    {
        $this->loadAccessToken();

        if ($this->accessToken) {
            $this->client = new Client([
                'base_uri' => 'https://graph.microsoft.com/',
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
        $tokenPath = storage_path('app/access_token.json');

        if (file_exists($tokenPath)) {
            $tokenData = json_decode(file_get_contents($tokenPath), true);

            // Check if token is expired
            if (time() < $tokenData['expires']) {
                $this->accessToken = $tokenData['access_token'];

                return true;
            } else {
                Log::info('Microsoft Graph access token has expired, attempting to refresh...');

                // Try to refresh the token if we have a refresh token
                if (isset($tokenData['refresh_token'])) {
                    return $this->refreshToken($tokenData['refresh_token']);
                } else {
                    Log::warning('No refresh token available');
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

            $response = $httpClient->post('https://login.microsoftonline.com/common/oauth2/v2.0/token', [
                'form_params' => [
                    'client_id'     => env('MICROSOFT_GRAPH_CLIENT_ID'),
                    'client_secret' => env('MICROSOFT_GRAPH_CLIENT_SECRET'),
                    'refresh_token' => $refreshToken,
                    'grant_type'    => 'refresh_token',
                    'scope'         => 'https://graph.microsoft.com/Mail.Read https://graph.microsoft.com/Mail.ReadWrite offline_access',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data['access_token'])) {
                // Save the new token data
                $tokenData = [
                    'access_token'  => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? $refreshToken, // Use new refresh token if provided
                    'expires'       => time() + ($data['expires_in'] ?? 3600),
                    'created_at'    => time(),
                ];

                file_put_contents(storage_path('app/access_token.json'), json_encode($tokenData, JSON_PRETTY_PRINT));

                $this->accessToken = $data['access_token'];

                // Recreate the HTTP client with the new token
                $this->client = new Client([
                    'base_uri' => 'https://graph.microsoft.com/',
                    'headers'  => [
                        'Authorization' => "Bearer {$this->accessToken}",
                        'Content-Type'  => 'application/json',
                    ],
                ]);

                Log::info('Microsoft Graph access token refreshed successfully');

                return true;
            }

        } catch (\Exception $e) {
            Log::error('Failed to refresh Microsoft Graph token: ' . $e->getMessage());
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
     * Get recent emails from inbox
     */
    public function getRecentEmails(int $count = 10): array
    {
        if (! $this->isAuthenticated()) {
            return [
                'success' => false,
                'error'   => 'Not authenticated. Please run OAuth flow.',
                'emails'  => [],
            ];
        }

        try {
            $response = $this->client->get('v1.0/me/messages', [
                'query' => [
                    '$top'     => $count,
                    '$select'  => 'id,subject,from,receivedDateTime,isRead,hasAttachments,bodyPreview',
                    '$orderby' => 'receivedDateTime desc',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if (! isset($data['value'])) {
                return [
                    'success' => false,
                    'error'   => 'No data received from Microsoft Graph',
                    'emails'  => [],
                ];
            }

            // Format emails for display
            $emails = collect($data['value'])->map(function ($message) {
                return [
                    'id'              => $message['id'],
                    'subject'         => $message['subject'] ?? 'No Subject',
                    'from_name'       => $message['from']['emailAddress']['name'] ?? 'Unknown',
                    'from_email'      => $message['from']['emailAddress']['address'] ?? 'Unknown',
                    'received_at'     => $message['receivedDateTime'],
                    'is_read'         => $message['isRead'],
                    'has_attachments' => $message['hasAttachments'],
                    'preview'         => $message['bodyPreview'] ?? '',
                ];
            })->toArray();

            return [
                'success' => true,
                'emails'  => $emails,
                'count'   => count($emails),
            ];

        } catch (RequestException $e) {
            Log::error('Microsoft Graph API error: ' . $e->getMessage());

            return [
                'success' => false,
                'error'   => 'API request failed: ' . $e->getMessage(),
                'emails'  => [],
            ];
        }
    }

    /**
     * Get a specific email by ID
     */
    public function getEmail(string $emailId): array
    {
        if (! $this->isAuthenticated()) {
            return [
                'success' => false,
                'error'   => 'Not authenticated',
            ];
        }

        try {
            $response = $this->client->get("v1.0/me/messages/{$emailId}", [
                'query' => [
                    '$select' => 'id,subject,from,receivedDateTime,isRead,hasAttachments,body,toRecipients',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'email'   => $data,
            ];

        } catch (RequestException $e) {
            Log::error('Microsoft Graph API error getting email: ' . $e->getMessage());

            return [
                'success' => false,
                'error'   => 'Failed to get email: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Search emails
     */
    public function searchEmails(string $query, int $count = 10): array
    {
        if (! $this->isAuthenticated()) {
            return [
                'success' => false,
                'error'   => 'Not authenticated',
                'emails'  => [],
            ];
        }

        try {
            $response = $this->client->get('v1.0/me/messages', [
                'query' => [
                    '$search' => $query,
                    '$top'    => $count,
                    '$select' => 'id,subject,from,receivedDateTime,isRead,hasAttachments,bodyPreview',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            $emails = collect($data['value'] ?? [])->map(function ($message) {
                return [
                    'id'              => $message['id'],
                    'subject'         => $message['subject'] ?? 'No Subject',
                    'from_name'       => $message['from']['emailAddress']['name'] ?? 'Unknown',
                    'from_email'      => $message['from']['emailAddress']['address'] ?? 'Unknown',
                    'received_at'     => $message['receivedDateTime'],
                    'is_read'         => $message['isRead'],
                    'has_attachments' => $message['hasAttachments'],
                    'preview'         => $message['bodyPreview'] ?? '',
                ];
            })
            // Sort manually by received date (newest first) since API doesn't support $orderby with $search
                ->sortByDesc('received_at')
                ->values()
                ->toArray();

            return [
                'success' => true,
                'emails'  => $emails,
                'count'   => count($emails),
            ];

        } catch (RequestException $e) {
            Log::error('Microsoft Graph search error: ' . $e->getMessage());

            return [
                'success' => false,
                'error'   => 'Search failed: ' . $e->getMessage(),
                'emails'  => [],
            ];
        }
    }

    /**
     * Mark an email as read
     */
    public function markAsRead(string $emailId): array
    {
        if (! $this->isAuthenticated()) {
            return [
                'success' => false,
                'error'   => 'Not authenticated',
            ];
        }

        try {
            $response = $this->client->patch("v1.0/me/messages/{$emailId}", [
                'json' => [
                    'isRead' => true,
                ],
            ]);

            Log::info("Email {$emailId} marked as read");

            return [
                'success' => true,
                'message' => 'Email marked as read',
            ];

        } catch (RequestException $e) {
            Log::error('Microsoft Graph API error marking email as read: ' . $e->getMessage());

            return [
                'success' => false,
                'error'   => 'Failed to mark email as read: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Mark an email as unread
     */
    public function markAsUnread(string $emailId): array
    {
        if (! $this->isAuthenticated()) {
            return [
                'success' => false,
                'error'   => 'Not authenticated',
            ];
        }

        try {
            $response = $this->client->patch("v1.0/me/messages/{$emailId}", [
                'json' => [
                    'isRead' => false,
                ],
            ]);

            Log::info("Email {$emailId} marked as unread");

            return [
                'success' => true,
                'message' => 'Email marked as unread',
            ];

        } catch (RequestException $e) {
            Log::error('Microsoft Graph API error marking email as unread: ' . $e->getMessage());

            return [
                'success' => false,
                'error'   => 'Failed to mark email as unread: ' . $e->getMessage(),
            ];
        }
    }

    public function toggleReadStatus(string $emailId, bool $currentReadStatus): array
    {
        return $currentReadStatus ? $this->markAsUnread($emailId) : $this->markAsRead($emailId);
    }
}
