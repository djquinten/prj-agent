<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emails', static function (Blueprint $table): void {
            $table->id();
            $table->string('graph_id')->unique();
            $table->string('email_id')->unique();

            // Microsoft Graph API fields
            $table->string('subject')->nullable();
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->text('to_recipients')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('has_attachments')->default(false);
            $table->text('body_preview')->nullable();
            $table->longText('body_content')->nullable();
            $table->string('body_content_type')->default('html');
            $table->string('importance')->nullable();
            $table->text('categories')->nullable();
            $table->text('attachments')->nullable()->after('categories');

            // AI Processing fields with updated enum values
            $table->enum('ai_status', [
                'pending',
                'screening',
                'processing',
                'completed',
                'failed',
                'skipped',
                'not_eligible',
                'screened_only',
            ])->default('pending')->index()->after('received_at');

            $table->boolean('ai_eligible')->default(false);
            $table->text('ai_response')->nullable();
            $table->json('ai_actions')->nullable();
            $table->text('ai_error')->nullable();
            $table->timestamp('ai_processed_at')->nullable();

            // AI screening specific columns
            $table->json('ai_screening_result')->nullable()->after('ai_processed_at');
            $table->timestamp('ai_screening_completed_at')->nullable()->after('ai_screening_result');

            // Sync tracking (is_synced field)
            $table->boolean('is_synced')->default(true)->after('ai_screening_completed_at');

            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            // Additional indexes for performance
            $table->index(['received_at', 'ai_status']);
            $table->index(['is_read', 'ai_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};
