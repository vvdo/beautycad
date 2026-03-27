<?php

namespace App\Http\Controllers;

use App\Services\PromotionAutomationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, PromotionAutomationService $automationService): View
    {
        $user = $request->user()->loadMissing(['profile', 'preference']);

        $submissions = $user->promotionSubmissions()
            ->latest()
            ->with(['logs' => fn ($query) => $query->latest()->limit(1)])
            ->limit(10)
            ->get();

        $attentionSubmission = $submissions->first(
            fn ($submission) => in_array($submission->status, ['needs_info', 'captcha_required'], true)
        );

        return view('dashboard', [
            'submissions' => $submissions,
            'missingProfileFields' => $automationService->missingProfileFields($user),
            'attentionSubmission' => $attentionSubmission,
        ]);
    }
}
