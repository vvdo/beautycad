<?php

namespace App\Services;

use App\Models\PromotionSubmission;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class PlaywrightAutomationRunner
{
    public function run(PromotionSubmission $submission): array
    {
        $scriptPath = base_path(config('automation.playwright_script'));

        if (! is_file($scriptPath)) {
            return [
                'status' => 'failed',
                'message' => 'Script Playwright nao encontrado na aplicacao.',
                'missing_fields' => [],
                'metadata' => ['driver' => 'playwright', 'script_path' => $scriptPath],
                'logs' => [[
                    'level' => 'error',
                    'message' => 'Nao foi possivel localizar o script de automacao Node.',
                    'context' => ['path' => $scriptPath],
                ]],
            ];
        }

        $payload = $this->buildPayload($submission);

        $process = new Process(
            [config('automation.node_binary', 'node'), $scriptPath],
            base_path(),
            null,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            config('automation.timeout_seconds', 180)
        );

        $process->run();

        if (! $process->isSuccessful() && trim($process->getOutput()) === '') {
            Log::error('Playwright process failed without JSON output', [
                'submission_id' => $submission->id,
                'exit_code' => $process->getExitCode(),
                'error_output' => $process->getErrorOutput(),
            ]);

            throw new ProcessFailedException($process);
        }

        return $this->normalizeResult(
            $this->decodeOutput($process->getOutput(), $process->getErrorOutput()),
            $process
        );
    }

    private function buildPayload(PromotionSubmission $submission): array
    {
        $user = $submission->user;
        $profile = $user->profile;
        $preference = $user->preference;

        return [
            'submission_id' => $submission->id,
            'url' => $submission->promotion_url,
            'profile' => [
                'full_name' => $profile?->full_name,
                'email' => $user->email,
                'cpf' => $profile?->cpf,
                'rg' => $profile?->rg,
                'birth_date' => optional($profile?->birth_date)->format('Y-m-d'),
                'phone' => $profile?->phone,
                'whatsapp' => $profile?->whatsapp,
                'instagram' => $profile?->instagram,
                'zip_code' => $profile?->zip_code,
                'street' => $profile?->street,
                'number' => $profile?->number,
                'complement' => $profile?->complement,
                'neighborhood' => $profile?->neighborhood,
                'city' => $profile?->city,
                'state' => $profile?->state,
                'country' => $profile?->country,
            ],
            'preferences' => [
                'accept_terms' => (bool) ($preference?->accept_terms ?? false),
                'allow_marketing_emails' => (bool) ($preference?->allow_marketing_emails ?? false),
                'allow_marketing_sms' => (bool) ($preference?->allow_marketing_sms ?? false),
                'allow_third_party_share' => (bool) ($preference?->allow_third_party_share ?? false),
                'receive_newsletter' => (bool) ($preference?->receive_newsletter ?? false),
                'auto_reject_cookies' => (bool) ($preference?->auto_reject_cookies ?? true),
                'pause_on_captcha' => (bool) ($preference?->pause_on_captcha ?? true),
            ],
            'options' => [
                'headless' => filter_var(config('automation.headless', true), FILTER_VALIDATE_BOOL),
                'navigation_timeout_ms' => (int) config('automation.navigation_timeout_ms', 45000),
                'action_timeout_ms' => (int) config('automation.action_timeout_ms', 6000),
            ],
        ];
    }

    private function decodeOutput(string $stdout, string $stderr): array
    {
        $decoded = $this->decodeSingleJsonString(trim($stdout));

        if ($decoded !== null) {
            return $decoded;
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($stdout)) ?: [];

        foreach (array_reverse($lines) as $line) {
            $lineDecoded = $this->decodeSingleJsonString(trim($line));

            if ($lineDecoded !== null) {
                return $lineDecoded;
            }
        }

        return [
            'status' => 'failed',
            'message' => 'Resposta invalida do worker Playwright.',
            'missing_fields' => [],
            'metadata' => [
                'raw_output' => $stdout,
                'raw_error' => $stderr,
            ],
            'logs' => [[
                'level' => 'error',
                'message' => 'Nao foi possivel interpretar a saida JSON do worker Node.',
                'context' => ['stderr' => $stderr],
            ]],
        ];
    }

    private function decodeSingleJsonString(string $json): ?array
    {
        if ($json === '') {
            return null;
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function normalizeResult(array $result, Process $process): array
    {
        $allowedStatuses = ['completed', 'failed', 'captcha_required', 'needs_info'];
        $status = (string) Arr::get($result, 'status', 'failed');

        if (! in_array($status, $allowedStatuses, true)) {
            $status = 'failed';
        }

        $logs = collect(Arr::get($result, 'logs', []))
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item) {
                $level = Arr::get($item, 'level', 'info');
                if (! in_array($level, ['info', 'warning', 'error'], true)) {
                    $level = 'info';
                }

                return [
                    'level' => $level,
                    'message' => (string) Arr::get($item, 'message', 'Evento de automacao.'),
                    'context' => Arr::get($item, 'context'),
                ];
            })
            ->values()
            ->all();

        return [
            'status' => $status,
            'message' => (string) Arr::get($result, 'message', 'Automacao finalizada.'),
            'missing_fields' => array_values(array_unique(array_filter(
                Arr::get($result, 'missing_fields', []),
                fn ($field) => is_string($field) && trim($field) !== ''
            ))),
            'metadata' => array_merge(
                ['driver' => 'playwright'],
                is_array(Arr::get($result, 'metadata')) ? Arr::get($result, 'metadata') : [],
                [
                    'exit_code' => $process->getExitCode(),
                    'stderr' => trim($process->getErrorOutput()) ?: null,
                ]
            ),
            'logs' => $logs,
        ];
    }
}
