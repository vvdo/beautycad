<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PersonalDataController extends Controller
{
    public function edit(Request $request): View
    {
        $profile = $request->user()->profile()->firstOrCreate([], [
            'country' => 'Brasil',
        ]);

        return view('personal-data.edit', [
            'profile' => $profile,
        ]);
    }

    public function update(UpdateUserProfileRequest $request): RedirectResponse
    {
        $payload = $request->validated();

        if (blank($payload['country'] ?? null)) {
            $payload['country'] = 'Brasil';
        }

        $request->user()->profile()->updateOrCreate([], $payload);

        return redirect()
            ->route('personal-data.edit')
            ->with('status', 'Dados pessoais atualizados com sucesso.');
    }
}
