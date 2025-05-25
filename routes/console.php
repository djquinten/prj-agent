<?php

use App\Jobs\SyncEmailsFromGraphApi;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new SyncEmailsFromGraphApi(25, true))
    ->everyFiveMinutes()
    ->name('sync-emails-from-graph')
    ->description('Sync emails from Microsoft Graph API and queue for AI processing')
    ->withoutOverlapping(10);
