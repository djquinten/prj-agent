<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Jobs\ProcessEmailWithAi;
use Illuminate\Support\Facades\DB;

class CreateTestMeetingEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:test-meeting-email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test email with meeting details for tomorrow 8 AM to 10 AM';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Creating Test Meeting Email ===');
        $this->newLine();

        // Create test email data using correct column names
        $testEmail = [
            'graph_id' => 'test-' . uniqid(),
            'email_id' => 'test-email-' . uniqid(),
            'subject' => 'Project Review Meeting Tomorrow',
            'from_name' => 'Sarah Johnson',
            'from_email' => 'sarah.johnson@company.com',
            'to_recipients' => json_encode([
                [
                    'name' => 'You',
                    'email' => 'you@yourcompany.com'
                ]
            ]),
            'body_preview' => 'I wanted to confirm our project review meeting scheduled for tomorrow from 8:00 AM to 10:00 AM...',
            'body_content' => "Hi there,

I hope this email finds you well. I wanted to confirm our project review meeting scheduled for tomorrow from 8:00 AM to 10:00 AM.

We'll be discussing:
- Q1 project deliverables
- Budget review
- Timeline adjustments
- Next quarter planning

The meeting will be held in Conference Room B on the 3rd floor. Please bring your project reports and any questions you might have.

Looking forward to our productive discussion!

Best regards,
Sarah Johnson
Project Manager
Company Inc.
sarah.johnson@company.com
(555) 123-4567",
            'body_content_type' => 'text',
            'received_at' => Carbon::now(),
            'sent_at' => Carbon::now()->subMinutes(5),
            'is_read' => false,
            'has_attachments' => false,
            'importance' => 'normal',
            'ai_status' => 'pending',
            'ai_eligible' => true,
            'is_synced' => true,
            'last_synced_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        try {
            // Insert the test email
            $emailId = DB::table('emails')->insertGetId($testEmail);
            
            $this->info("✅ Test email created successfully!");
            $this->info("Email ID: {$emailId}");
            $this->info("From: {$testEmail['from_name']} <{$testEmail['from_email']}>");
            $this->info("Subject: {$testEmail['subject']}");
            $this->info("Content: Meeting tomorrow from 8:00 AM to 10:00 AM");
            $this->newLine();
            
            $this->info("=== Email Content ===");
            $this->line($testEmail['body_content']);
            $this->newLine();
            
            $this->info("=== Next Steps ===");
            $this->line("1. This email should trigger the AI processing");
            $this->line("2. The AI should detect the meeting keywords");
            $this->line("3. It should convert 'tomorrow from 8:00 AM to 10:00 AM' to proper ISO dates");
            $this->line("4. It should call the Google Calendar tool to create the event");
            $this->newLine();
            
            $this->info("To process this email manually, run:");
            $this->line("php artisan process:email {$emailId}");
            $this->newLine();
            
            $this->line("Or check if it gets processed automatically by the system.");
            
        } catch (\Exception $e) {
            $this->error("❌ Error creating test email: " . $e->getMessage());
            $this->error("Make sure your database is set up and the emails table exists.");
            return 1;
        }

        $this->newLine();
        $this->info("=== Expected AI Behavior ===");
        $this->line("The AI should:");
        $this->line("1. Detect meeting-related keywords: 'meeting', 'tomorrow', '8:00 AM to 10:00 AM'");
        $this->line("2. Convert relative time to absolute ISO format:");
        
        // Calculate expected times
        $tomorrowStart = Carbon::tomorrow()->setTime(8, 0);
        $tomorrowEnd = Carbon::tomorrow()->setTime(10, 0);

        ProcessEmailWithAi::dispatch($emailId);
        
        $this->line("   - 'tomorrow 8:00 AM' → " . $tomorrowStart->toISOString());
        $this->line("   - 'tomorrow 10:00 AM' → " . $tomorrowEnd->toISOString());
        $this->line("3. Generate tool call with parameters:");
        $this->line("   - title: 'Project Review Meeting'");
        $this->line("   - start_time: '" . $tomorrowStart->toISOString() . "'");
        $this->line("   - end_time: '" . $tomorrowEnd->toISOString() . "'");
        $this->line("   - location: 'Conference Room B, 3rd floor'");
        $this->line("   - description: Meeting details from email");
        
        return 0;
    }
}
