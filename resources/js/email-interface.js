// Email Interface JavaScript
// Set up CSRF token for AJAX requests
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

function toggleReadStatus(emailId, currentReadStatus, event) {
    // Prevent the email row click event
    event.stopPropagation();
    
    const button = event.target.closest('.read-status-btn');
    const emailRow = document.querySelector(`[data-email-id="${emailId}"]`);
    
    // Show loading state
    button.innerHTML = '<div class="w-4 h-4 border-2 border-slate-300 border-t-blue-500 rounded-full animate-spin"></div>';
    button.disabled = true;
    
    fetch(`/emails/${emailId}/toggle-read`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            is_read: currentReadStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the UI
            const newReadStatus = data.new_status;
            
            // Update button
            if (newReadStatus) {
                button.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                button.className = 'read-status-btn read';
                button.title = 'Mark as unread';
            } else {
                button.innerHTML = '<div class="w-3 h-3 bg-blue-500 rounded-full"></div>';
                button.className = 'read-status-btn unread';
                button.title = 'Mark as read';
            }
            
            // Update row styling
            if (newReadStatus) {
                emailRow.classList.remove('unread');
                
                // Update subject
                const subject = emailRow.querySelector('.email-subject');
                subject.classList.remove('unread');
                subject.classList.add('read');
                
                // Update meta
                const meta = emailRow.querySelector('.email-meta');
                meta.classList.remove('unread');
                meta.classList.add('read');
                
                // Update preview
                const preview = emailRow.querySelector('.email-preview');
                if (preview) {
                    preview.classList.remove('unread');
                    preview.classList.add('read');
                }
            } else {
                emailRow.classList.add('unread');
                
                // Update subject
                const subject = emailRow.querySelector('.email-subject');
                subject.classList.remove('read');
                subject.classList.add('unread');
                
                // Update meta
                const meta = emailRow.querySelector('.email-meta');
                meta.classList.remove('read');
                meta.classList.add('unread');
                
                // Update preview
                const preview = emailRow.querySelector('.email-preview');
                if (preview) {
                    preview.classList.remove('read');
                    preview.classList.add('unread');
                }
            }
            
            // Update data attribute
            emailRow.setAttribute('data-is-read', newReadStatus ? 'true' : 'false');
            
        } else {
            alert('Error: ' + data.error);
            
            // Restore original button state
            if (currentReadStatus) {
                button.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                button.className = 'read-status-btn read';
            } else {
                button.innerHTML = '<div class="w-3 h-3 bg-blue-500 rounded-full"></div>';
                button.className = 'read-status-btn unread';
            }
        }
        
        button.disabled = false;
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update email status');
        
        // Restore original button state
        if (currentReadStatus) {
            button.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
            button.className = 'read-status-btn read';
        } else {
            button.innerHTML = '<div class="w-3 h-3 bg-blue-500 rounded-full"></div>';
            button.className = 'read-status-btn unread';
        }
        
        button.disabled = false;
    });
}

function syncEmails() {
    const syncBtn = document.getElementById('sync-btn');
    const originalContent = syncBtn.innerHTML;
    
    // Show loading state
    syncBtn.innerHTML = '<div class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></div><span>Syncing...</span>';
    syncBtn.disabled = true;
    
    fetch('/emails/sync', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            syncBtn.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg><span>Synced!</span>';
            
            // Reload page after 2 seconds to show new emails
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            alert('Sync failed: ' + data.error);
            syncBtn.innerHTML = originalContent;
            syncBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Sync failed');
        syncBtn.innerHTML = originalContent;
        syncBtn.disabled = false;
    });
}

let autoRefreshInterval = null;
let autoRefreshEnabled = false;

function toggleAutoRefresh() {
    const autoRefreshBtn = document.getElementById('auto-refresh-btn');
    const autoRefreshText = document.getElementById('auto-refresh-text');
    
    if (autoRefreshEnabled) {
        // Stop auto-refresh
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
        autoRefreshEnabled = false;
        
        // Change back to purple
        autoRefreshBtn.className = 'action-btn purple';
        autoRefreshText.textContent = 'Auto';
        autoRefreshBtn.title = 'Start auto-refresh every 10 seconds';
    } else {
        // Start auto-refresh
        autoRefreshInterval = setInterval(() => {
            console.log('Auto-refreshing page...');
            window.location.reload();
        }, 10000); // 10 seconds
        
        autoRefreshEnabled = true;
        
        // Change to success (green) color
        autoRefreshBtn.className = 'action-btn success';
        autoRefreshText.textContent = 'Stop';
        autoRefreshBtn.title = 'Stop auto-refresh';
    }
}

function filterEmails(filter) {
    // Update active filter button
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
        btn.classList.add('bg-white/80', 'dark:bg-slate-800/80', 'text-slate-700', 'dark:text-slate-200');
        btn.classList.remove('bg-gradient-to-r', 'from-blue-600', 'to-blue-700', 'text-white', 'border-blue-600', 'shadow-lg', 'shadow-blue-600/25', 'transform', '-translate-y-0.5');
    });

    // Find and activate the correct button
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(btn => {
        const btnText = btn.textContent.trim();
        let shouldActivate = false;
        
        switch (filter) {
            case 'all':
                shouldActivate = btnText.includes('All Emails');
                break;
            case 'ai-processed':
                shouldActivate = btnText.includes('AI Analyzed');
                break;
            case 'unread':
                shouldActivate = btnText.includes('Unread');
                break;
            case 'pending-ai':
                shouldActivate = btnText.includes('Queued for AI');
                break;
            case 'has-attachments':
                shouldActivate = btnText.includes('Attachments');
                break;
        }
        
        if (shouldActivate) {
            btn.classList.add('active');
            btn.classList.remove('bg-white/80', 'dark:bg-slate-800/80', 'text-slate-700', 'dark:text-slate-200');
            btn.classList.add('bg-gradient-to-r', 'from-blue-600', 'to-blue-700', 'text-white', 'border-blue-600', 'shadow-lg', 'shadow-blue-600/25', 'transform', '-translate-y-0.5');
        }
    });

    const emailRows = document.querySelectorAll('.email-row-card');
    let visibleCount = 0;

    emailRows.forEach(row => {
        let shouldShow = false;

        switch (filter) {
            case 'all':
                shouldShow = true;
                break;
            
            case 'ai-processed':
                // Show emails with AI analysis (completed status and has AI response content)
                const hasAIContent = row.classList.contains('ai-processed');
                const hasCompletedBadge = row.querySelector('.ai-badge.completed') !== null;
                shouldShow = hasAIContent || hasCompletedBadge;
                break;
            
            case 'unread':
                shouldShow = row.getAttribute('data-is-read') === 'false';
                break;
            
            case 'pending-ai':
                const pendingBadge = row.querySelector('.ai-badge.pending, .ai-badge.processing');
                shouldShow = pendingBadge !== null;
                break;
            
            case 'has-attachments':
                shouldShow = row.innerHTML.includes('stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586');
                break;
        }

        if (shouldShow) {
            row.style.display = 'block';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    // Update email count in header
    const emailListHeader = document.querySelector('.email-list-header');
    if (emailListHeader) {
        const filterNames = {
            'all': 'All Emails',
            'ai-processed': 'AI Analyzed Emails',
            'unread': 'Unread Emails',
            'pending-ai': 'Queued for AI',
            'has-attachments': 'Emails with Attachments'
        };
        emailListHeader.textContent = `${filterNames[filter] || 'Filtered Emails'} (${visibleCount})`;
    }

    // Show/hide empty state if no emails match filter
    const emailContainer = document.querySelector('.email-container');
    let emptyState = document.querySelector('.empty-state');
    
    if (visibleCount === 0 && filter !== 'all') {
        if (!emptyState) {
            emptyState = document.createElement('div');
            emptyState.className = 'empty-state email-card text-center py-16 mt-8';
            emptyState.innerHTML = `
                <div class="w-20 h-20 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-slate-400 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-slate-900 dark:text-white mb-2">No emails match this filter</h3>
                <p class="text-slate-600 dark:text-slate-400 mb-6">Try a different filter or sync more emails.</p>
                <button onclick="filterEmails('all')" class="action-btn secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14-4H3m16 8H1"></path>
                    </svg>
                    <span>Show All Emails</span>
                </button>
            `;
            emailContainer.parentNode.appendChild(emptyState);
        }
        emptyState.style.display = 'block';
    } else if (emptyState) {
        emptyState.style.display = 'none';
    }

    console.log(`Filter: ${filter}, Visible emails: ${visibleCount}`);
}

// Initialize theme based on system preference or saved setting
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
        document.documentElement.classList.add('dark');
    }
});

// Listen for system theme changes
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    const savedTheme = localStorage.getItem('theme');
    if (!savedTheme || savedTheme === 'auto') {
        if (e.matches) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
});

// Make functions available globally for onclick handlers
window.toggleReadStatus = toggleReadStatus;
window.syncEmails = syncEmails;
window.toggleAutoRefresh = toggleAutoRefresh;
window.filterEmails = filterEmails; 