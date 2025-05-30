<?php

namespace App\Tools;

use App\Contracts\ToolCallInterface;
use App\Models\Email;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\Log;

class GoogleCalendarTool implements ToolCallInterface
{
    private GoogleCalendarService $calendarService;

    public function __construct(GoogleCalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    public function getName(): string
    {
        return 'create_calendar_event';
    }

    public function getDescription(): string
    {
        return 'Create a Google Calendar event when a meeting is detected in an email. This tool can parse meeting information from email content and create calendar events automatically.';
    }

    public function getParametersSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'title' => [
                    'type'        => 'string',
                    'description' => 'The title/subject of the meeting event (can also use "summary")',
                ],
                'summary' => [
                    'type'        => 'string',
                    'description' => 'Alternative to title - the summary/subject of the meeting event',
                ],
                'start_time' => [
                    'type'        => 'string',
                    'description' => 'Start time in ISO 8601 format (e.g., 2024-01-15T14:00:00Z). Convert relative dates like "tomorrow at 6 PM" to absolute ISO format.',
                ],
                'end_time' => [
                    'type'        => 'string',
                    'description' => 'End time in ISO 8601 format (e.g., 2024-01-15T15:00:00Z). Convert relative dates like "tomorrow at 7 PM" to absolute ISO format.',
                ],
                'location' => [
                    'type'        => 'string',
                    'description' => 'Meeting location (optional)',
                ],
                'description' => [
                    'type'        => 'string',
                    'description' => 'Meeting description (optional)',
                ],
                'attendees' => [
                    'type'  => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                    'description' => 'List of attendee email addresses (optional)',
                ],
                'calendar_id' => [
                    'type'        => 'string',
                    'description' => 'Calendar ID to create the event in (defaults to primary)',
                ],
            ],
            'required' => ['start_time', 'end_time'],
        ];
    }

    public function execute(Email $email, array $parameters): array
    {
        try {
            // Handle both 'title' and 'summary' parameter names (AI might use either)
            $title = $parameters['title'] ?? $parameters['summary'] ?? null;

            // Validate required parameters
            if (empty($title) || empty($parameters['start_time']) || empty($parameters['end_time'])) {
                return [
                    'success' => false,
                    'error'   => 'Missing required parameters: title (or summary), start_time, and end_time are required. Received: ' . json_encode(array_keys($parameters)),
                ];
            }

            // Parse and validate date/time formats
            $startTime = $this->parseDateTime($parameters['start_time']);
            $endTime   = $this->parseDateTime($parameters['end_time']);

            if (! $startTime || ! $endTime) {
                return [
                    'success' => false,
                    'error'   => 'Invalid date/time format. Please use ISO 8601 format (e.g., 2024-01-15T14:00:00Z)',
                ];
            }

            // Prepare event data
            $eventData = [
                'title'       => $title,
                'start_time'  => $startTime->toISOString(),
                'end_time'    => $endTime->toISOString(),
                'description' => $parameters['description'] ?? "Meeting created from email: {$email->subject}",
                'location'    => $parameters['location'] ?? null,
                'calendar_id' => $parameters['calendar_id'] ?? 'primary',
            ];

            // Add attendees if provided
            if (! empty($parameters['attendees'])) {
                $eventData['attendees'] = $parameters['attendees'];
            } else {
                // Include the email sender as an attendee
                $eventData['attendees'] = [$email->from_email];
            }

            // Create the calendar event
            $result = $this->calendarService->createEvent($eventData);

            if ($result['success']) {
                Log::info('📅 Calendar event created via AI tool', [
                    'email_id'   => $email->id,
                    'event_id'   => $result['event_id'],
                    'title'      => $title,
                    'start_time' => $startTime->toISOString(),
                ]);

                return [
                    'success'   => true,
                    'message'   => 'Calendar event created successfully',
                    'event_id'  => $result['event_id'],
                    'event_url' => $result['event_url'],
                    'details'   => [
                        'title'      => $title,
                        'start_time' => $startTime->toISOString(),
                        'end_time'   => $endTime->toISOString(),
                        'location'   => $parameters['location'],
                    ],
                ];
            } else {
                return [
                    'success' => false,
                    'error'   => $result['error'],
                ];
            }

        } catch (\Exception $e) {
            Log::error('❌ Google Calendar tool execution failed', [
                'email_id'   => $email->id,
                'error'      => $e->getMessage(),
                'parameters' => $parameters,
            ]);

            return [
                'success' => false,
                'error'   => 'Failed to create calendar event: ' . $e->getMessage(),
            ];
        }
    }

    public function isAvailable(Email $email): bool
    {
        // Check if Google Calendar service is authenticated
        $isAuthenticated = $this->calendarService->isAuthenticated();

        if (! $isAuthenticated) {
            Log::debug('🔧 GoogleCalendarTool not available: Google Calendar not authenticated', [
                'email_id' => $email->id ?? 'unknown',
                'subject'  => $email->subject ?? 'unknown',
            ]);

            return false;
        }

        // Check if the email contains meeting-related content
        $subject = strtolower($email->subject ?? '');
        $content = strtolower($email->body_content ?? '');

        $meetingKeywords = [
            'meeting', 'call', 'conference', 'appointment', 'interview',
            'demo', 'presentation', 'sync', 'standup', 'review',
            'zoom', 'teams', 'webex', 'hangout', 'skype',
        ];

        $hasMeetingKeyword = false;
        $foundKeyword      = null;

        foreach ($meetingKeywords as $keyword) {
            if (str_contains($subject, $keyword) || str_contains($content, $keyword)) {
                $hasMeetingKeyword = true;
                $foundKeyword      = $keyword;
                break;
            }
        }

        if (! $hasMeetingKeyword) {
            Log::debug('🔧 GoogleCalendarTool not available: No meeting keywords found', [
                'email_id'         => $email->id ?? 'unknown',
                'subject'          => $email->subject ?? 'unknown',
                'content_preview'  => substr($content, 0, 100),
                'keywords_checked' => $meetingKeywords,
            ]);

            return false;
        }

        Log::info('🔧 GoogleCalendarTool available for email', [
            'email_id'      => $email->id ?? 'unknown',
            'subject'       => $email->subject ?? 'unknown',
            'found_keyword' => $foundKeyword,
            'authenticated' => $isAuthenticated,
        ]);

        return true;
    }

    /**
     * Parse date/time string with support for relative dates
     */
    private function parseDateTime(string $dateTimeString): ?\Carbon\Carbon
    {
        try {
            // First try to parse as ISO 8601 or standard format
            return \Carbon\Carbon::parse($dateTimeString);
        } catch (\Exception $e) {
            // If that fails, try to handle relative dates
            $dateTimeString = strtolower(trim($dateTimeString));

            // Handle relative dates
            if (str_contains($dateTimeString, 'tomorrow')) {
                $time = $this->extractTimeFromString($dateTimeString);

                return \Carbon\Carbon::tomorrow()->setTimeFromTimeString($time ?: '09:00');
            }

            if (str_contains($dateTimeString, 'today')) {
                $time = $this->extractTimeFromString($dateTimeString);

                return \Carbon\Carbon::today()->setTimeFromTimeString($time ?: '09:00');
            }

            if (str_contains($dateTimeString, 'next week')) {
                $time = $this->extractTimeFromString($dateTimeString);

                return \Carbon\Carbon::now()->addWeek()->setTimeFromTimeString($time ?: '09:00');
            }

            // Handle "next [day]" patterns
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            foreach ($days as $day) {
                if (str_contains($dateTimeString, "next $day")) {
                    $time = $this->extractTimeFromString($dateTimeString);

                    return \Carbon\Carbon::parse("next $day")->setTimeFromTimeString($time ?: '09:00');
                }
            }

            Log::warning('Failed to parse date/time string', [
                'input' => $dateTimeString,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Extract time from a string (e.g., "6 PM", "18:00", "2:30 PM")
     */
    private function extractTimeFromString(string $string): ?string
    {
        // Match patterns like "6 PM", "6:30 PM", "18:00", "2:30"
        if (preg_match('/(\d{1,2}):?(\d{2})?\s*(am|pm)/i', $string, $matches)) {
            $hour   = (int) $matches[1];
            $minute = isset($matches[2]) ? (int) $matches[2] : 0;
            $ampm   = strtolower($matches[3]);

            if ($ampm === 'pm' && $hour !== 12) {
                $hour += 12;
            } elseif ($ampm === 'am' && $hour === 12) {
                $hour = 0;
            }

            return sprintf('%02d:%02d', $hour, $minute);
        }

        // Match 24-hour format
        if (preg_match('/(\d{1,2}):(\d{2})/', $string, $matches)) {
            return sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
        }

        // Match just hour with AM/PM
        if (preg_match('/(\d{1,2})\s*(am|pm)/i', $string, $matches)) {
            $hour = (int) $matches[1];
            $ampm = strtolower($matches[2]);

            if ($ampm === 'pm' && $hour !== 12) {
                $hour += 12;
            } elseif ($ampm === 'am' && $hour === 12) {
                $hour = 0;
            }

            return sprintf('%02d:00', $hour);
        }

        return null;
    }

    /**
     * Auto-detect meeting information from email content
     */
    public function autoDetectMeeting(Email $email): ?array
    {
        $meetingInfo = $this->calendarService->parseMeetingFromEmail(
            $email->subject ?? '',
            $email->body_content ?? ''
        );

        if ($meetingInfo) {
            Log::info('🔍 Meeting auto-detected in email', [
                'email_id'     => $email->id,
                'subject'      => $email->subject,
                'meeting_info' => $meetingInfo,
            ]);
        }

        return $meetingInfo;
    }
}
