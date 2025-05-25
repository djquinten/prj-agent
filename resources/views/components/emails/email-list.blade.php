<!-- Email List Component -->
@if($success && count($emails) > 0)
    <div class="email-container">
        <div class="mb-6">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white email-list-header">
                @if(isset($search_query))
                    Search Results for "{{ $search_query }}" ({{ count($emails) }} found)
                @else
                    Recent Emails ({{ count($emails) }})
                @endif
            </h2>
        </div>
        
        <div class="space-y-4">
            @foreach($emails as $email)
                @include('components.emails.email-row', ['email' => $email])
            @endforeach
        </div>
    </div>
@elseif($success && count($emails) == 0)
    <div class="email-card text-center py-16">
        <div class="w-20 h-20 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-slate-400 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
            </svg>
        </div>
        <h3 class="text-xl font-semibold text-slate-900 dark:text-white mb-2">
            @if(isset($search_query))
                No emails found
            @else
                No emails in inbox
            @endif
        </h3>
        <p class="text-slate-600 dark:text-slate-400 mb-6">
            @if(isset($search_query))
                Try a different search term.
            @else
                Your inbox is empty or emails haven't been synced yet.
            @endif
        </p>
        @if($authenticated && !isset($search_query))
            <button 
                onclick="syncEmails()"
                class="action-btn primary"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span>Sync Emails Now</span>
            </button>
        @endif
    </div>
@endif 