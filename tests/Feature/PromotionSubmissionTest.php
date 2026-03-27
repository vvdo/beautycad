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
}
