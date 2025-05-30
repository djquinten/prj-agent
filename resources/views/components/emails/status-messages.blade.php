<!-- Status Messages Component -->

<!-- Authentication Required -->
@if(!$authenticated)
    <div class="glass-card mb-8 border-l-4 border-amber-400">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/20 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Authentication Required</h3>
                    <p class="text-slate-600 dark:text-slate-400">You need to authenticate with Microsoft Graph API to view emails.</p>
                </div>
            </div>
            <a 
                href="{{ route('auth.login') }}"
                class="action-btn primary"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1721 9z"></path>
                </svg>
                <span>Authenticate Now</span>
            </a>
        </div>
    </div>
@endif

<!-- Google Calendar Tool Notification -->
@php
    $googleCalendarService = app(\App\Services\GoogleCalendarService::class);
    $googleCalendarAuthenticated = $googleCalendarService->isAuthenticated();
@endphp

@if($authenticated && !$googleCalendarAuthenticated)
    <div class="glass-card mb-8 border-l-4 border-blue-400">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/20 rounded-xl flex items-center justify-center">
                    <span class="text-2xl">📅</span>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">🚀 New Feature: AI Calendar Integration</h3>
                    <p class="text-slate-600 dark:text-slate-400">Connect Google Calendar to automatically create events when AI detects meetings in your emails!</p>
                    <p class="text-xs text-slate-500 dark:text-slate-500 mt-1">
                        ✨ AI will detect meeting keywords, parse dates/times, and create calendar events automatically
                    </p>
                </div>
            </div>
            <a 
                href="{{ route('google-calendar.login') }}"
                class="action-btn warning"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0V6a2 2 0 012-2h4a2 2 0 012 2v1m-6 0h6m-6 0l-1 1v8a2 2 0 002 2h4a2 2 0 002-2V8l-1-1"></path>
                </svg>
                <span>Connect Calendar</span>
            </a>
        </div>
    </div>
@endif

@if($authenticated && $googleCalendarAuthenticated)
    <div class="glass-card mb-8 border-l-4 border-emerald-400">
        <div class="flex items-center space-x-4">
            <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/20 rounded-xl flex items-center justify-center">
                <span class="text-2xl">🤖📅</span>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">AI Calendar Tools Active</h3>
                <p class="text-slate-600 dark:text-slate-400">
                    AI will automatically detect meetings in emails and create Google Calendar events. 
                    <span class="font-medium text-emerald-600 dark:text-emerald-400">Tool calls enabled!</span>
                </p>
            </div>
        </div>
    </div>
@endif

<!-- Success Messages -->
@if(session('success'))
    <div class="glass-card mb-8 border-l-4 border-emerald-400">
        <div class="flex items-center space-x-4">
            <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/20 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Success</h3>
                <p class="text-slate-600 dark:text-slate-400">{{ session('success') }}</p>
            </div>
        </div>
    </div>
@endif

<!-- Error Messages -->
@if(!$success && $error)
    <div class="glass-card mb-8 border-l-4 border-red-400">
        <div class="flex items-center space-x-4">
            <div class="w-10 h-10 bg-red-100 dark:bg-red-900/20 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Error</h3>
                <p class="text-slate-600 dark:text-slate-400">{{ $error }}</p>
            </div>
        </div>
    </div>
@endif

@if(session('error'))
    <div class="glass-card mb-8 border-l-4 border-red-400">
        <div class="flex items-center space-x-4">
            <div class="w-10 h-10 bg-red-100 dark:bg-red-900/20 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Error</h3>
                <p class="text-slate-600 dark:text-slate-400">{{ session('error') }}</p>
            </div>
        </div>
    </div>
@endif 