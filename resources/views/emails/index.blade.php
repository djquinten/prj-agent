<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>📧 AI Email Automation</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=sf-pro-display:300,400,500,600,700|sf-pro-text:300,400,500,600" rel="stylesheet" />
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <div class="bg-wrapper bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 text-slate-900 dark:text-slate-100">
        <div class="max-w-7xl mx-auto px-6 py-8">
        
            <!-- Header Component -->
            @include('components.emails.header', [
                'authenticated' => $authenticated,
                'search_query' => $search_query ?? null,
                'emails' => $emails
            ])

            <!-- Stats Dashboard Component -->
            @include('components.emails.stats-dashboard', ['stats' => $stats ?? []])

            <!-- Filter Buttons Component -->
            @include('components.emails.filter-buttons')

            <!-- Status Messages Component -->
            @include('components.emails.status-messages', [
                'authenticated' => $authenticated,
                'success' => $success,
                'error' => $error ?? null
            ])

            <!-- Email List Component -->
            @include('components.emails.email-list', [
                'success' => $success,
                'emails' => $emails,
                'authenticated' => $authenticated,
                'search_query' => $search_query ?? null
            ])

            <!-- Footer -->
            <div class="mt-12 text-center">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    <span class="font-semibold">🤖 Powered by Microsoft Graph API + Local AI</span>
                    <span class="mx-2">•</span>
                    <span>Automated Email Processing</span>
                </p>
            </div>
        </div>
    </div>
</body>
</html> 