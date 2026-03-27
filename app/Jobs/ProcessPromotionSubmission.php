<?php

namespace App\Jobs;

use App\Models\PromotionSubmission;
use App\Services\PromotionAutomationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessPromotionSubmission implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(public int $submissionId) {}

    public function handle(PromotionAutomationService $automationService): void
    {
        $submission = PromotionSubmission::query()
            ->with(['user.profile', 'user.preference', 'logs'])
            ->find($this->submissionId);

        if (! $submission) {
            return;
        }

        try {
            $automationService->processSubmission($submission);
        } catch (Throwable $exception) {
            $submission->update([
                'status' => 'failed',
                'result_message' => 'Falha inesperada ao processar o cadastro.',
                'finished_at' => now(),
            ]);

            $submission->logs()->create([
                'level' => 'error',
                'message' => 'Erro durante o processamento do cadastro.',
                'context' => ['error' => $exception->getMessage()],
            ]);

            Log::error('Promotion submission failed', [
                'submission_id' => $submission->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
