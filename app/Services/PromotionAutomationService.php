<?php

namespace App\Services;

use App\Models\PromotionSubmission;
use App\Models\User;
use Illuminate\Support\Str;

class PromotionAutomationService
{
    public function __construct(
        private readonly PlaywrightAutomationRunner $playwrightRunner
    ) {}

    public function missingProfileFields(User $user): array
    {
        $profile = $user->profile;
        $missing = [];

        foreach ($this->requiredProfileMap() as $field => $label) {
            $value = $field === 'email' ? $user->email : $profile?->{$field};

            if ($this->isMissing($value)) {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    public function processSubmission(PromotionSubmission $submission): void
    {
        $submission->update([
            'status' => 'processing',
            'started_at' => now(),
            'finished_at' => null,
            'result_message' => null,
            'missing_fields' => null,
        ]);

        $submission->logs()->create([
            'level' => 'info',
            'message' => 'Iniciando automacao do cadastro no link informado.',
            'context' => ['url' => $submission->promotion_url],
        ]);

        $result = $this->runDriver($submission);

        foreach ($result['logs'] as $log) {
            $submission->logs()->create([
                'level' => $log['level'],
                'message' => $log['message'],
                'context' => $log['context'] ?? null,
            ]);
        }

        $submission->update([
            'status' => $result['status'],
            'missing_fields' => $result['missing_fields'],
            'result_message' => $result['message'],
            'metadata' => $result['metadata'],
            'finished_at' => now(),
        ]);
    }

    private function runDriver(PromotionSubmission $submission): array
    {
        $driver = config('automation.driver', 'playwright');

        return $driver === 'simulated'
            ? $this->runSimulated($submission)
            : $this->playwrightRunner->run($submission);
    }

    private function runSimulated(PromotionSubmission $submission): array
    {
        $user = $submission->user;
        $profile = $user->profile;
        $preferences = $user->preference;

        $logs = [];

        if (($preferences?->auto_reject_cookies) !== false) {
            $logs[] = [
                'level' => 'info',
                'message' => 'Tentativa de rejeicao automatica de cookies executada.',
                'context' => null,
            ];
        }

        $runtimeMissingFields = [];

        if (Str::contains(Str::lower($submission->promotion_url), ['instagram', 'insta']) && $this->isMissing($profile?->instagram)) {
            $runtimeMissingFields[] = 'Instagram';
        }

        if (Str::contains(Str::lower($submission->promotion_url), ['rg', 'documento']) && $this->isMissing($profile?->rg)) {
            $runtimeMissingFields[] = 'RG';
        }

        if ($runtimeMissingFields !== []) {
            $logs[] = [
                'level' => 'warning',
                'message' => 'Campos obrigatorios ausentes durante a automacao.',
                'context' => ['missing_fields' => $runtimeMissingFields],
            ];

            return [
                'status' => 'needs_info',
                'message' => 'O site exigiu campos obrigatorios que ainda nao estao no seu cadastro.',
                'missing_fields' => $runtimeMissingFields,
                'metadata' => ['driver' => 'simulated'],
                'logs' => $logs,
            ];
        }

        if ($this->requiresCaptcha($submission->promotion_url)) {
            $logs[] = [
                'level' => 'warning',
                'message' => 'Captcha detectado. Necessaria intervencao do usuario.',
                'context' => null,
            ];

            return [
                'status' => 'captcha_required',
                'message' => 'Captcha identificado. Complete manualmente para finalizar.',
                'missing_fields' => [],
                'metadata' => ['driver' => 'simulated'],
                'logs' => $logs,
            ];
        }

        $logs[] = [
            'level' => 'info',
            'message' => 'Campos preenchidos automaticamente com dados do perfil.',
            'context' => null,
        ];

        return [
            'status' => 'completed',
            'message' => 'Cadastro processado com sucesso no modo simulacao.',
            'missing_fields' => [],
            'metadata' => ['driver' => 'simulated'],
            'logs' => $logs,
        ];
    }

    private function requiredProfileMap(): array
    {
        return [
            'full_name' => 'Nome completo',
            'email' => 'E-mail',
            'cpf' => 'CPF',
            'birth_date' => 'Data de nascimento',
            'phone' => 'Telefone',
            'zip_code' => 'CEP',
            'street' => 'Rua',
            'number' => 'Numero',
            'city' => 'Cidade',
            'state' => 'UF',
        ];
    }

    private function requiresCaptcha(string $url): bool
    {
        return Str::contains(Str::lower($url), ['captcha', 'recaptcha', 'hcaptcha']);
    }

    private function isMissing(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value) && trim($value) === '') {
            return true;
        }

        return false;
    }
}
