<!-- Filter Buttons Component -->
<div class="mb-8">
    <div class="flex flex-wrap gap-3">
        <button onclick="filterEmails('all')" class="filter-btn active group">
            <div class="flex items-center space-x-2">
                <div class="p-1.5 bg-blue-100 dark:bg-blue-900/30 rounded-lg group-hover:bg-blue-200 dark:group-hover:bg-blue-800/40 transition-colors">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14-4H3m16 8H1"></path>
                    </svg>
                </div>
                <span class="font-medium">All Emails</span>
            </div>
        </button>
        
        <button onclick="filterEmails('ai-processed')" class="filter-btn group">
            <div class="flex items-center space-x-2">
                <div class="p-1.5 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg group-hover:bg-emerald-200 dark:group-hover:bg-emerald-800/40 transition-colors">
                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <span class="font-medium">AI Analyzed</span>
            </div>
        </button>
        
        <button onclick="filterEmails('unread')" class="filter-btn group">
            <div class="flex items-center space-x-2">
                <div class="p-1.5 bg-amber-100 dark:bg-amber-900/30 rounded-lg group-hover:bg-amber-200 dark:group-hover:bg-amber-800/40 transition-colors">
                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                </div>
                <span class="font-medium">Unread</span>
            </div>
        </button>
        
        <button onclick="filterEmails('pending-ai')" class="filter-btn group">
            <div class="flex items-center space-x-2">
                <div class="p-1.5 bg-purple-100 dark:bg-purple-900/30 rounded-lg group-hover:bg-purple-200 dark:group-hover:bg-purple-800/40 transition-colors">
                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <span class="font-medium">Queued for AI</span>
            </div>
        </button>
        
        <button onclick="filterEmails('has-attachments')" class="filter-btn group">
            <div class="flex items-center space-x-2">
                <div class="p-1.5 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg group-hover:bg-indigo-200 dark:group-hover:bg-indigo-800/40 transition-colors">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                    </svg>
                </div>
                <span class="font-medium">Attachments</span>
            </div>
        </button>
    </div>
</div>

<style>
/* Enhanced filter button styles */
.filter-btn {
    @apply px-4 py-3 text-sm font-medium rounded-xl transition-all duration-300 ease-out border border-slate-200/60 dark:border-slate-700/60 hover:border-slate-300 dark:hover:border-slate-600 hover:shadow-lg hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:ring-offset-2 focus:ring-offset-transparent;
}

.filter-btn.active {
    @apply bg-gradient-to-r from-blue-600 to-blue-700 text-white border-blue-600 shadow-lg shadow-blue-600/25 transform -translate-y-0.5;
}

.filter-btn.active .p-1\.5 {
    @apply bg-white/20 text-white;
}

.filter-btn:not(.active) {
    @apply bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm text-slate-700 dark:text-slate-200 hover:bg-white/95 dark:hover:bg-slate-800/95;
}

.filter-btn:not(.active):hover {
    @apply shadow-xl shadow-slate-900/10 dark:shadow-slate-900/30;
}
</style> 