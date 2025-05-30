<!-- Email Interface Header Component -->
<div class="glass-card mb-8">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-500/25">
                <span class="text-white text-2xl">🤖</span>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">AI Email Automation</h1>
                <p class="text-slate-600 dark:text-slate-300 font-medium">Microsoft Graph API + Local AI Processing + Google Calendar</p>
            </div>
            <div class="flex items-center space-x-2 ml-6">
                <div class="live-indicator animate-pulse">
                    <div class="w-2 h-2 bg-emerald-500 rounded-full"></div>
                </div>
                <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Live</span>
            </div>
        </div>
        
        <!-- Search and Actions -->
        <div class="flex items-center space-x-3">
            <!-- Search -->
            <form method="GET" action="{{ route('emails.search') }}" class="flex">
                <input 
                    type="text" 
                    name="q" 
                    value="{{ $search_query ?? '' }}"
                    placeholder="Search emails..." 
                    class="search-input rounded-r-none w-64"
                >
                <button 
                    type="submit"
                    class="action-btn primary rounded-l-none border-l-0"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </button>
            </form>
            
            @if(isset($search_query))
                <button 
                    onclick="window.location='{{ route('emails.index') }}'"
                    class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 transition-colors p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg"
                    title="Clear search"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            @endif

            <!-- Individual Action Buttons -->
            @if($authenticated)
                <button 
                    onclick="syncEmails()"
                    id="sync-btn"
                    class="action-btn success"
                    title="Sync emails from Microsoft Graph"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span>Sync</span>
                </button>
            @endif
            
            <button 
                onclick="toggleAutoRefresh()"
                id="auto-refresh-btn"
                class="action-btn purple"
                title="Toggle auto-refresh every 10 seconds"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span id="auto-refresh-text">Auto</span>
            </button>
            
            <a 
                href="/debug" 
                target="_blank"
                class="action-btn secondary"
                title="Open debug information in new tab"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span>Debug</span>
            </a>

            <!-- Google Calendar Authentication -->
            @php
                $googleCalendarService = app(\App\Services\GoogleCalendarService::class);
                $googleCalendarAuthenticated = $googleCalendarService->isAuthenticated();
            @endphp

            @if($googleCalendarAuthenticated)
                <form method="POST" action="{{ route('google-calendar.logout') }}" class="inline">
                    @csrf
                    <button 
                        type="submit"
                        class="action-btn warning"
                        onclick="return confirm('Are you sure you want to logout from Google Calendar?')"
                        title="Logout from Google Calendar"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0V6a2 2 0 012-2h4a2 2 0 012 2v1m-6 0h6m-6 0l-1 1v8a2 2 0 002 2h4a2 2 0 002-2V8l-1-1"></path>
                        </svg>
                        <span>📅</span>
                    </button>
                </form>
            @else
                <a 
                    href="{{ route('google-calendar.login') }}"
                    class="action-btn warning"
                    title="Connect Google Calendar for meeting detection"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0V6a2 2 0 012-2h4a2 2 0 012 2v1m-6 0h6m-6 0l-1 1v8a2 2 0 002 2h4a2 2 0 002-2V8l-1-1"></path>
                    </svg>
                    <span>📅</span>
                </a>
            @endif

            <!-- Microsoft Graph Authentication -->
            @if($authenticated)
                <form method="POST" action="{{ route('auth.logout') }}" class="inline">
                    @csrf
                    <button 
                        type="submit"
                        class="action-btn danger"
                        onclick="return confirm('Are you sure you want to logout?')"
                        title="Logout from Microsoft Graph"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013 3v1"></path>
                        </svg>
                        <span>Logout</span>
                    </button>
                </form>
            @else
                <a 
                    href="{{ route('auth.login') }}"
                    class="action-btn primary"
                    title="Login to Microsoft Graph"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1721 9z"></path>
                    </svg>
                    <span>Login</span>
                </a>
            @endif
        </div>
    </div>
    
    <!-- Status Panel -->
    <div class="mt-6 p-4 bg-slate-50/60 dark:bg-slate-800/40 rounded-xl border border-slate-200/60 dark:border-slate-700/60">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
            <div class="flex items-center space-x-2">
                <div class="w-2 h-2 rounded-full {{ $authenticated ? 'bg-emerald-500' : 'bg-red-500' }}"></div>
                <span class="font-medium text-slate-700 dark:text-slate-200">Microsoft Graph:</span>
                <span class="{{ $authenticated ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }} font-semibold">
                    {{ $authenticated ? 'Connected' : 'Not Connected' }}
                </span>
            </div>
            <div class="flex items-center space-x-2">
                <div class="w-2 h-2 rounded-full {{ $googleCalendarAuthenticated ? 'bg-emerald-500' : 'bg-amber-500' }}"></div>
                <span class="font-medium text-slate-700 dark:text-slate-200">Google Calendar:</span>
                <span class="{{ $googleCalendarAuthenticated ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }} font-semibold">
                    {{ $googleCalendarAuthenticated ? 'Connected' : 'Not Connected' }}
                </span>
            </div>
            <div class="flex items-center space-x-2">
                <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                <span class="font-medium text-slate-700 dark:text-slate-200">Database:</span>
                <span class="text-blue-600 dark:text-blue-400 font-mono text-xs">{{ basename(config('database.connections.sqlite.database')) }}</span>
            </div>
            <div class="flex items-center space-x-2">
                <div class="w-2 h-2 rounded-full bg-purple-500"></div>
                <span class="font-medium text-slate-700 dark:text-slate-200">Email Count:</span>
                <span class="text-purple-600 dark:text-purple-400 font-semibold">{{ count($emails) }} displayed</span>
            </div>
        </div>
        @if(!$authenticated || !$googleCalendarAuthenticated)
            <div class="mt-3 text-xs text-slate-600 dark:text-slate-400">
                <strong>Setup:</strong> 
                @if(!$authenticated)
                    Connect Microsoft Graph to sync emails.
                @endif
                @if(!$googleCalendarAuthenticated)
                    Connect Google Calendar to enable meeting detection and automatic calendar event creation.
                @endif
            </div>
        @endif
    </div>
</div> 