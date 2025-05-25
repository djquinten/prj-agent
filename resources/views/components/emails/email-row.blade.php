<!-- Email Row Component -->
<div class="email-row-card 
    @if(!$email['is_read']) unread @endif
    @if(isset($email['ai_status']) && $email['ai_status'] === 'completed' && isset($email['ai_response']) && !empty($email['ai_response'])) ai-processed @endif
    " 
    data-email-id="{{ $email['id'] }}" 
    data-is-read="{{ $email['is_read'] ? 'true' : 'false' }}">
    
    <div class="p-6 flex items-start justify-between">
        <div class="flex items-start space-x-4 flex-1 min-w-0">
            <!-- Read Status Button -->
            <button 
                class="read-status-btn {{ $email['is_read'] ? 'read' : 'unread' }}"
                onclick="toggleReadStatus('{{ $email['id'] }}', {{ $email['is_read'] ? 'true' : 'false' }}, event)"
                title="{{ $email['is_read'] ? 'Mark as unread' : 'Mark as read' }}"
            >
                @if($email['is_read'])
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                @else
                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                @endif
            </button>
            
            <!-- Email Content -->
            <div class="flex-1 min-w-0 cursor-pointer" onclick="window.location='{{ route('emails.show', $email['id']) }}'">
                <div class="flex items-center space-x-3 mb-4">
                    <!-- AI Brain Icon for Processed Emails -->
                    @if(isset($email['ai_status']) && $email['ai_status'] === 'completed' && isset($email['ai_response']) && !empty($email['ai_response']))
                        <div class="w-6 h-6 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-lg flex items-center justify-center animate-pulse">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    @endif
                    
                    <!-- Attachments -->
                    @if($email['has_attachments'])
                        <div class="w-5 h-5 text-slate-500 dark:text-slate-400">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                            </svg>
                        </div>
                    @endif
                    
                    <!-- Subject -->
                    <h3 class="email-subject {{ $email['is_read'] ? 'read' : 'unread' }} flex-1">
                        {{ $email['subject'] }}
                    </h3>

                    <!-- AI Status Badge -->
                    @if(isset($email['ai_eligible']) && $email['ai_eligible'])
                        @if($email['ai_status'] === 'pending')
                            <span class="ai-badge pending">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Queued for AI</span>
                            </span>
                        @elseif($email['ai_status'] === 'processing')
                            <span class="ai-badge processing">
                                <svg class="w-3 h-3 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span>AI Processing...</span>
                            </span>
                        @elseif($email['ai_status'] === 'completed')
                            <span class="ai-badge completed">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <span>AI Analyzed</span>
                            </span>
                        @elseif($email['ai_status'] === 'failed')
                            <span class="ai-badge failed">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <span>AI Failed</span>
                            </span>
                        @else
                            <span class="ai-badge pending">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>AI Eligible</span>
                            </span>
                        @endif
                    @elseif(isset($email['ai_status']) && $email['ai_status'] === 'not_eligible')
                        <span class="ai-badge not-eligible">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
                            </svg>
                            <span>Not Eligible</span>
                        </span>
                    @else
                        <span class="ai-badge not-eligible">
                            <span>—</span>
                            <span>No AI Processing</span>
                        </span>
                    @endif
                </div>
                
                <div class="email-meta {{ $email['is_read'] ? 'read' : 'unread' }} mb-4">
                    <span class="{{ $email['is_read'] ? 'font-normal' : 'font-semibold' }}">{{ $email['from_name'] }}</span>
                    <span class="mx-2 text-slate-400 dark:text-slate-500">•</span>
                    <span class="text-slate-500 dark:text-slate-400">{{ $email['from_email'] }}</span>
                </div>
                
                @if($email['preview'])
                    <p class="email-preview {{ $email['is_read'] ? 'read' : 'unread' }} mb-4 line-clamp-2">
                        {{ Str::limit($email['preview'], 150) }}
                    </p>
                @endif

                <!-- AI Response Preview -->
                @if(isset($email['ai_response']) && !empty($email['ai_response']) && $email['ai_status'] === 'completed')
                    <div class="mt-5 p-4 bg-gradient-to-r from-emerald-50/90 to-blue-50/90 dark:from-emerald-900/30 dark:to-blue-900/30 border border-emerald-200/60 dark:border-emerald-800/60 rounded-xl">
                        <div class="flex items-start space-x-3">
                            <div class="w-6 h-6 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-xs font-semibold text-emerald-800 dark:text-emerald-200 mb-2">AI Analysis</div>
                                <div class="text-sm text-slate-700 dark:text-slate-200 leading-relaxed">
                                    @php
                                        $aiResponse = is_string($email['ai_response']) ? $email['ai_response'] : json_encode($email['ai_response']);
                                        $preview = Str::limit($aiResponse, 180);
                                    @endphp
                                    {{ $preview }}
                                </div>
                                @if(strlen($aiResponse) > 180)
                                    <div class="text-xs text-emerald-600 dark:text-emerald-400 mt-2 font-medium">Click to see full AI analysis →</div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <!-- AI Actions Summary -->
                @if(isset($email['ai_actions_summary']) && $email['ai_actions_summary'] !== 'No actions taken')
                    <div class="mt-4">
                        <span class="inline-flex items-center px-3 py-1.5 text-sm text-purple-700 dark:text-purple-200 bg-purple-100 dark:bg-purple-900/30 border border-purple-200 dark:border-purple-700 rounded-full font-medium">
                            <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            {{ $email['ai_actions_summary'] }}
                        </span>
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Date/Time Column -->
        <div class="ml-6 text-right flex-shrink-0">
            <div class="text-sm text-slate-500 dark:text-slate-400 font-medium">
                {{ \Carbon\Carbon::parse($email['received_at'])->format('M j') }}
            </div>
            <div class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                {{ \Carbon\Carbon::parse($email['received_at'])->format('H:i') }}
            </div>
            @if(isset($email['ai_processed_at']) && $email['ai_processed_at'])
                <div class="text-xs text-emerald-600 dark:text-emerald-400 mt-2 font-medium bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-700 px-2 py-1 rounded-lg">
                    🤖 {{ \Carbon\Carbon::parse($email['ai_processed_at'])->format('H:i') }}
                </div>
            @endif
        </div>
    </div>
</div>

<style>
/* Enhanced email row card styles */
.email-row-card {
    @apply relative transition-all duration-300 ease-out mb-4 last:mb-0 bg-white/95 dark:bg-slate-800/95 backdrop-blur-xl border border-slate-200/60 dark:border-slate-700/60 rounded-2xl shadow-lg shadow-slate-900/5 dark:shadow-slate-900/20 hover:shadow-xl hover:shadow-slate-900/10 dark:hover:shadow-slate-900/30 hover:-translate-y-1;
}

.email-row-card.unread {
    @apply bg-gradient-to-r from-blue-50/95 to-white/95 dark:from-blue-900/30 dark:to-slate-800/95 border-l-4 border-blue-500 shadow-blue-500/10 dark:shadow-blue-500/20;
}

.email-row-card.ai-processed {
    @apply bg-gradient-to-r from-emerald-50/95 to-white/95 dark:from-emerald-900/30 dark:to-slate-800/95 border-l-4 border-emerald-400 shadow-emerald-500/10 dark:shadow-emerald-500/20;
}

.email-row-card:hover {
    @apply border-slate-300/80 dark:border-slate-600/80;
}

.email-row-card.unread:hover {
    @apply border-blue-400 shadow-blue-500/20 dark:shadow-blue-500/30;
}

.email-row-card.ai-processed:hover {
    @apply border-emerald-400 shadow-emerald-500/20 dark:shadow-emerald-500/30;
}
</style> 