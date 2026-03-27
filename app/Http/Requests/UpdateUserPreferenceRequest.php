<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserPreferenceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'accept_terms' => ['required', 'boolean'],
            'allow_marketing_emails' => ['required', 'boolean'],
            'allow_marketing_sms' => ['required', 'boolean'],
            'allow_third_party_share' => ['required', 'boolean'],
            'receive_newsletter' => ['required', 'boolean'],
            'preferred_contact_channel' => ['required', 'in:email,whatsapp,sms'],
            'auto_reject_cookies' => ['required', 'boolean'],
            'pause_on_captcha' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'accept_terms' => $this->boolean('accept_terms'),
            'allow_marketing_emails' => $this->boolean('allow_marketing_emails'),
            'allow_marketing_sms' => $this->boolean('allow_marketing_sms'),
            'allow_third_party_share' => $this->boolean('allow_third_party_share'),
            'receive_newsletter' => $this->boolean('receive_newsletter'),
            'auto_reject_cookies' => $this->boolean('auto_reject_cookies'),
            'pause_on_captcha' => $this->boolean('pause_on_captcha'),
        ]);
    }
}
