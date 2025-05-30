<?php

namespace App\Providers;

use App\Contracts\EmailRepositoryInterface;
use App\Repositories\EmailRepository;
use App\Services\EmailService;
use App\Services\MicrosoftGraphService;
use Illuminate\Support\ServiceProvider;

class EmailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EmailRepositoryInterface::class, EmailRepository::class);

        $this->app->singleton(EmailService::class, function ($app) {
            return new EmailService(
                $app->make(EmailRepositoryInterface::class),
                $app->make(MicrosoftGraphService::class)
            );
        });

        // Register Google Calendar Service
        $this->app->singleton(GoogleCalendarService::class, function ($app) {
            return new GoogleCalendarService;
        });
    }
}
