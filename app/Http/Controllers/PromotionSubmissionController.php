<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePromotionSubmissionRequest;
use App\Jobs\ProcessPromotionSubmission;
use App\Services\PromotionAutomationService;
use Illuminate\Http\RedirectResponse;

class PromotionSubmissionController extends Controller
{
    public function store(
        StorePromotionSubmissionRequest $request,
        PromotionAutomationService $automationService
    ): RedirectResponse {
        $user = $request->user()->loadMissing(['profile', 'preference']);
        $missingProfileFields = $automationService->missingProfileFields($user);

        if ($missingProfileFields !== []) {
            return redirect()
                ->route('dashboard')
                ->withErrors([
                    'promotion_url' => 'Preencha seus dados pessoais antes de cadastrar uma promocao.',
                ])
                ->with('missing_profile_fields', $missingProfileFields);
        }

        $preference = $user->preference()->firstOrCreate([], [
            'accept_terms' => false,
            'allow_marketing_emails' => false,
            'allow_marketing_sms' => false,
            'allow_third_party_share' => false,
            'receive_newsletter' => true,
            'preferred_contact_channel' => 'email',
            'auto_reject_cookies' => true,
            'pause_on_captcha' => true,
        ]);

        if (! $preference->accept_terms) {
            return redirect()
                ->route('preferences.edit')
                ->withErrors([
                    'accept_terms' => 'Aceite os termos nas configuracoes para habilitar a automacao.',
                ]);
        }

        $submission = $user->promotionSubmissions()->create([
            'promotion_url' => $request->validated('promotion_url'),
            'status' => 'pending',
        ]);

        $submission->logs()->create([
            'level' => 'info',
            'message' => 'Solicitacao de cadastro recebida e enviada para fila.',
        ]);

        ProcessPromotionSubmission::dispatch($submission->id);

        return redirect()
            ->route('dashboard')
            ->with('status', 'Promocao enviada para processamento.');
    }
}
