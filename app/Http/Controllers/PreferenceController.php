<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserPreferenceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PreferenceController extends Controller
{
    public function edit(Request $request): View
    {
        $preference = $request->user()->preference()->firstOrCreate([], [
            'accept_terms' => false,
            'allow_marketing_emails' => false,
            'allow_marketing_sms' => false,
            'allow_third_party_share' => false,
            'receive_newsletter' => true,
            'preferred_contact_channel' => 'email',
            'auto_reject_cookies' => true,
            'pause_on_captcha' => true,
        ]);

        return view('preferences.edit', [
            'preference' => $preference,
        ]);
    }

    public function update(UpdateUserPreferenceRequest $request): RedirectResponse
    {
        $request->user()->preference()->updateOrCreate([], $request->validated());

        return redirect()
            ->route('preferences.edit')
            ->with('status', 'Configuracoes salvas com sucesso.');
    }
}
