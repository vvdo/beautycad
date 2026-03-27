<?php

namespace Tests\Feature;

use App\Jobs\ProcessPromotionSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PromotionSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_promotion_submission_requires_personal_data(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('promotion-submissions.store'), [
                'promotion_url' => 'https://example.com/promo',
            ]);

        $response
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionHasErrors('promotion_url')
            ->assertSessionHas('missing_profile_fields');

        $this->assertDatabaseCount('promotion_submissions', 0);
    }

    public function test_promotion_submission_dispatches_job_when_profile_is_ready(): void
    {
        Queue::fake();

        $user = $this->createReadyUser();

        $response = $this
            ->actingAs($user)
            ->post(route('promotion-submissions.store'), [
                'promotion_url' => 'https://example.com/promo',
            ]);

        $response
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('promotion_submissions', [
            'user_id' => $user->id,
            'promotion_url' => 'https://example.com/promo',
            'status' => 'pending',
        ]);

        Queue::assertPushed(ProcessPromotionSubmission::class);
    }

    public function test_user_can_view_own_submission_details_with_logs(): void
    {
        $user = $this->createReadyUser();
        $submission = $user->promotionSubmissions()->create([
            'promotion_url' => 'https://example.com/promo',
            'status' => 'processing',
            'result_message' => 'Iniciando automacao.',
            'metadata' => ['driver' => 'playwright'],
        ]);
        $submission->logs()->create([
            'level' => 'info',
            'message' => 'Pagina carregada com sucesso.',
            'context' => ['url' => 'https://example.com/promo'],
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('promotion-submissions.show', $submission));

        $response
            ->assertOk()
            ->assertSee('Detalhes da promocao')
            ->assertSee('Pagina carregada com sucesso.')
            ->assertSee('https://example.com/promo');
    }

    public function test_user_cannot_view_submission_from_another_account(): void
    {
        $owner = $this->createReadyUser();
        $intruder = $this->createReadyUser();

        $submission = $owner->promotionSubmissions()->create([
            'promotion_url' => 'https://example.com/owner',
            'status' => 'completed',
        ]);

        $response = $this
            ->actingAs($intruder)
            ->get(route('promotion-submissions.show', $submission));

        $response->assertNotFound();
    }

    private function createReadyUser(): User
    {
        $user = User::factory()->create();
        $user->profile()->create([
            'full_name' => 'Teste da Silva',
            'cpf' => '123.456.789-10',
            'birth_date' => '1990-01-01',
            'phone' => '(11) 90000-0000',
            'zip_code' => '01000-000',
            'street' => 'Rua Teste',
            'number' => '100',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'country' => 'Brasil',
        ]);
        $user->preference()->create([
            'accept_terms' => true,
            'allow_marketing_emails' => false,
            'allow_marketing_sms' => false,
            'allow_third_party_share' => false,
            'receive_newsletter' => true,
            'preferred_contact_channel' => 'email',
            'auto_reject_cookies' => true,
            'pause_on_captcha' => true,
        ]);

        return $user;
    }
}
