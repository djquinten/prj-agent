<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmailSearchRequest;
use App\Services\EmailService;
use App\Services\MicrosoftGraphService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmailController extends Controller
{
    public function __construct(
        private EmailService $emailService,
        private MicrosoftGraphService $graphService
    ) {}

    public function index(): View
    {
        $emails        = $this->emailService->getRecentEmails();
        $stats         = $this->emailService->getStats();
        $authenticated = $this->graphService->isAuthenticated();

        return view('emails.index', [
            'emails'        => $emails,
            'success'       => true,
            'error'         => null,
            'authenticated' => $authenticated,
            'stats'         => $stats->toArray(),
        ]);
    }

    public function show(string $graphId): RedirectResponse|View
    {
        $email = $this->emailService->getEmailDetail($graphId);

        if (! $email) {
            return back()->with('error', 'Email not found in local database');
        }

        return view('emails.show', [
            'email'   => $email,
            'success' => true,
        ]);
    }

    public function search(EmailSearchRequest $request): RedirectResponse|View
    {
        if (! $request->hasValidQuery()) {
            return redirect()->route('emails.index');
        }

        $query         = $request->getSearchQuery();
        $emails        = $this->emailService->searchEmails($query);
        $stats         = $this->emailService->getSearchStats(collect($emails));
        $authenticated = $this->graphService->isAuthenticated();

        return view('emails.index', [
            'emails'        => $emails,
            'success'       => true,
            'error'         => null,
            'authenticated' => $authenticated,
            'search_query'  => $query,
            'stats'         => $stats->toArray(),
        ]);
    }
}
