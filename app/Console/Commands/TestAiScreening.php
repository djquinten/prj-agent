<?php

namespace App\Console\Commands;

use App\Jobs\ScreenEmailWithAi;
use App\Models\Email;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TestAiScreening extends Command
{
    protected $signature = 'emails:test-screening';

    protected $description = 'Test the AI screening workflow with a sample email';

    public function handle(): int
    {
        $this->info('🧪 Testing AI screening workflow...');

        $email = Email::create([
            'graph_id'      => 'test-' . uniqid(),
            'email_id'      => 'test-' . uniqid(),
            'subject'       => 'Urgent: Meeting Request for Next Week',
            'from_name'     => 'John Doe',
            'from_email'    => 'john.doe@company.com',
            'to_recipients' => [
                [
                    'name'  => 'Test User',
                    'email' => 'test@example.com',
                ],
            ],
            'received_at'       => Carbon::now(),
            'is_read'           => false,
            'has_attachments'   => false,
            'body_preview'      => 'Hi there, I need to schedule an urgent meeting with you next week to discuss the project timeline. Can you please let me know your availability?',
            'body_content'      => 'Hi there,\n\nI need to schedule an urgent meeting with you next week to discuss the project timeline. This is quite important as we have some deadlines approaching.\n\nCan you please let me know your availability for Tuesday or Wednesday afternoon?\n\nThanks!\nJohn',
            'body_content_type' => 'text',
            'ai_status'         => Email::AI_STATUS_PENDING,
            'ai_eligible'       => true,
            'is_synced'         => true,
        ]);

        $this->info("📧 Created test email with ID: {$email->id}");
        $this->info("📄 Subject: {$email->subject}");
        $this->info("👤 From: {$email->from_name} <{$email->from_email}>");

        ScreenEmailWithAi::dispatch($email->id);

        $this->info('🔍 Dispatched AI screening job for email');
        $this->info('💡 Monitor the logs to see the screening results');

        $this->newLine();
        $this->info('📊 Current screening statistics:');

        $this->table(
            ['Status', 'Count'],
            [
                ['Pending Screening', Email::where('ai_status', Email::AI_STATUS_PENDING)->count()],
                ['Currently Screening', Email::where('ai_status', Email::AI_STATUS_SCREENING)->count()],
                ['Screened Only', Email::where('ai_status', Email::AI_STATUS_SCREENED_ONLY)->count()],
                ['Full Processing', Email::where('ai_status', Email::AI_STATUS_PROCESSING)->count()],
                ['Completed', Email::where('ai_status', Email::AI_STATUS_COMPLETED)->count()],
            ]
        );

        return self::SUCCESS;
    }
}
