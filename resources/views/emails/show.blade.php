<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>📧 {{ $email['subject'] ?? 'Email' }} - AI Email Automation</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=sf-pro-display:300,400,500,600,700|sf-pro-text:300,400,500,600" rel="stylesheet" />
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <div class="bg-wrapper bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 text-slate-900 dark:text-slate-100">
        <div class="max-w-5xl mx-auto px-6 py-8">
            
            <!-- Header with Back Button -->
            <div class="glass-card mb-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('emails.index') }}" class="action-btn secondary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            <span>Back to Inbox</span>
                        </a>
                        
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-500/25">
                            <span class="text-white text-2xl">📧</span>
                        </div>
                        
                        <div>
                            <h1 class="text-xl font-bold text-slate-900 dark:text-white">Email Details</h1>
                            <p class="text-slate-600 dark:text-slate-300 font-medium">Full email view with AI analysis</p>
                        </div>
                    </div>
                    
                    <!-- Status Indicators -->
                    <div class="flex items-center space-x-3">
                        @if($email['isRead'])
                            <span class="ai-badge completed">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Read</span>
                            </span>
                        @else
                            <span class="ai-badge pending">
                                <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                <span>Unread</span>
                            </span>
                        @endif
                        
                        @if($email['hasAttachments'])
                            <span class="ai-badge not-eligible">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                </svg>
                                <span>Attachments</span>
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Email Content Card -->
            <div class="glass-card mb-8">
                <!-- Email Header -->
                <div class="border-b border-slate-200/60 dark:border-slate-700/60 pb-6 mb-6">
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-6 leading-tight">
                        {{ $email['subject'] ?? 'No Subject' }}
                    </h1>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- From -->
                        <div class="space-y-4">
                            <div class="flex items-start space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">From</div>
                                    <div class="text-lg font-semibold text-slate-900 dark:text-white">
                                        {{ $email['from']['emailAddress']['name'] ?? 'Unknown' }}
                                    </div>
                                    <div class="text-sm text-slate-600 dark:text-slate-400">
                                        {{ $email['from']['emailAddress']['address'] ?? 'Unknown' }}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- To Recipients -->
                            @if(isset($email['toRecipients']) && count($email['toRecipients']) > 0)
                                <div class="flex items-start space-x-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">To</div>
                                        <div class="space-y-1">
                                            @foreach($email['toRecipients'] as $recipient)
                                                <div class="text-sm text-slate-700 dark:text-slate-300">
                                                    <span class="font-medium">{{ $recipient['name'] ?? $recipient['emailAddress']['name'] ?? 'Unknown' }}</span>
                                                    <span class="text-slate-500 dark:text-slate-400 ml-2">&lt;{{ $recipient['email'] ?? $recipient['emailAddress']['address'] ?? 'Unknown' }}&gt;</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Date and AI Status -->
                        <div class="space-y-4">
                            <div class="flex items-start space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-purple-400 to-purple-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">Received</div>
                                    <div class="text-lg font-semibold text-slate-900 dark:text-white">
                                        {{ \Carbon\Carbon::parse($email['receivedDateTime'])->format('M j, Y') }}
                                    </div>
                                    <div class="text-sm text-slate-600 dark:text-slate-400">
                                        {{ \Carbon\Carbon::parse($email['receivedDateTime'])->format('g:i A') }}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- AI Status -->
                            @if(isset($email['ai_status']))
                                <div class="flex items-start space-x-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">AI Status</div>
                                        @if($email['ai_status'] === 'pending')
                                            <span class="ai-badge pending">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span>Pending Analysis</span>
                                            </span>
                                        @elseif($email['ai_status'] === 'processing')
                                            <span class="ai-badge processing">
                                                <svg class="w-3 h-3 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                                <span>Processing...</span>
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
                                                <span>Analysis Failed</span>
                                            </span>
                                        @else
                                            <span class="ai-badge not-eligible">
                                                <span>—</span>
                                                <span>Not Processed</span>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Email Body -->
                <div class="prose prose-slate max-w-none dark:prose-invert">
                    @if(isset($email['body']) && $email['body']['content'])
                        @if($email['body']['contentType'] === 'html')
                            <div class="email-content">
                                {!! $email['body']['content'] !!}
                            </div>
                        @else
                            <pre class="whitespace-pre-wrap font-sans text-slate-700 dark:text-slate-300 leading-relaxed">{{ $email['body']['content'] }}</pre>
                        @endif
                    @else
                        <div class="text-center py-16">
                            <div class="w-20 h-20 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-slate-400 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-slate-900 dark:text-white mb-2">No Content Available</h3>
                            <p class="text-slate-600 dark:text-slate-400">This email doesn't have any readable content.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- AI Analysis Section -->
            @if(isset($email['ai_status']) && $email['ai_status'] === 'completed' && $email['ai_response'])
                <div class="glass-card mb-8 bg-gradient-to-r from-emerald-50/90 to-blue-50/90 dark:from-emerald-900/30 dark:to-blue-900/30 border-emerald-200/60 dark:border-emerald-800/60">
                    <div class="flex items-start space-x-4 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-2xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">AI Analysis</h2>
                            @if($email['ai_processed_at'])
                                <p class="text-sm text-emerald-700 dark:text-emerald-300 font-medium">
                                    Processed {{ \Carbon\Carbon::parse($email['ai_processed_at'])->diffForHumans() }}
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm rounded-2xl p-6 mb-6 border border-emerald-200/60 dark:border-emerald-700/60">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Analysis Result</h3>
                        <div class="prose prose-slate dark:prose-invert max-w-none">
                            <p class="text-slate-700 dark:text-slate-300 leading-relaxed">{{ $email['ai_response'] }}</p>
                        </div>
                    </div>

                    <!-- AI Actions Taken -->
                    @if(isset($email['ai_actions']) && count($email['ai_actions']) > 0)
                        <div class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm rounded-2xl p-6 border border-emerald-200/60 dark:border-emerald-700/60">
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Actions Taken</h3>
                            <div class="space-y-4">
                                @foreach($email['ai_actions'] as $action)
                                    <div class="flex items-start space-x-4 p-4 bg-slate-50/80 dark:bg-slate-700/50 rounded-xl border border-slate-200/60 dark:border-slate-600/60">
                                        <div class="flex-shrink-0">
                                            @if($action['status'] === 'success')
                                                <div class="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </div>
                                            @elseif($action['status'] === 'failed')
                                                <div class="w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </div>
                                            @else
                                                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between mb-2">
                                                <h4 class="text-base font-semibold text-slate-900 dark:text-white capitalize">
                                                    {{ str_replace('_', ' ', $action['action']) }}
                                                </h4>
                                                <span class="text-xs font-medium px-2 py-1 rounded-full capitalize
                                                    @if($action['status'] === 'success') bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300
                                                    @elseif($action['status'] === 'failed') bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300
                                                    @else bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300
                                                    @endif
                                                ">{{ $action['status'] }}</span>
                                            </div>
                                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-2">{{ $action['reason'] }}</p>
                                            @if(isset($action['error']))
                                                <p class="text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 p-2 rounded-lg">
                                                    <strong>Error:</strong> {{ $action['error'] }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @elseif(isset($email['ai_status']) && $email['ai_status'] === 'failed' && $email['ai_error'])
                <div class="glass-card mb-8 bg-gradient-to-r from-red-50/90 to-pink-50/90 dark:from-red-900/30 dark:to-pink-900/30 border-red-200/60 dark:border-red-800/60">
                    <div class="flex items-start space-x-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-red-400 to-red-600 rounded-2xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">AI Processing Failed</h2>
                            <div class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm rounded-2xl p-4 border border-red-200/60 dark:border-red-700/60">
                                <p class="text-red-700 dark:text-red-300">{{ $email['ai_error'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @elseif(isset($email['ai_status']) && in_array($email['ai_status'], ['pending', 'processing']))
                <div class="glass-card mb-8 bg-gradient-to-r from-amber-50/90 to-yellow-50/90 dark:from-amber-900/30 dark:to-yellow-900/30 border-amber-200/60 dark:border-amber-800/60">
                    <div class="flex items-start space-x-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-amber-400 to-amber-600 rounded-2xl flex items-center justify-center flex-shrink-0">
                            @if($email['ai_status'] === 'processing')
                                <svg class="w-6 h-6 text-white animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            @else
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            @endif
                        </div>
                        <div class="flex-1">
                            <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">
                                @if($email['ai_status'] === 'processing')
                                    AI Processing in Progress
                                @else
                                    AI Processing Pending
                                @endif
                            </h2>
                            <p class="text-amber-700 dark:text-amber-300">
                                @if($email['ai_status'] === 'processing')
                                    This email is currently being analyzed by our AI system.
                                @else
                                    This email is queued for AI analysis and will be processed soon.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Action Buttons -->
            <div class="glass-card mb-8">
                <div class="flex flex-wrap gap-4">
                    <button class="action-btn primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                        </svg>
                        <span>Reply</span>
                    </button>
                    
                    <button class="action-btn secondary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                        <span>Forward</span>
                    </button>
                    
                    <button class="action-btn purple">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8l6 6m0 0l6-6m-6 6V3"></path>
                        </svg>
                        <span>Archive</span>
                    </button>
                    
                    <button class="action-btn danger">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        <span>Delete</span>
                    </button>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    <span class="font-semibold">📧 Email ID:</span>
                    <code class="bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded text-xs font-mono ml-2">{{ $email['id'] }}</code>
                </p>
            </div>
        </div>
    </div>
</body>
</html> 