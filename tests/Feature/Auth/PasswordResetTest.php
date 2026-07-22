<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Support\Supabase\Contracts\AuthenticatesWithSupabase;
use Tests\Fakes\FakeSupabaseAuth;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    public function test_a_reset_link_is_requested_via_supabase(): void
    {
        $fake = new FakeSupabaseAuth();
        $this->app->instance(AuthenticatesWithSupabase::class, $fake);

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'owner@example.com'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status');

        $this->assertContains('owner@example.com', $fake->recoveredEmails);
    }

    public function test_the_email_address_is_validated(): void
    {
        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'not-an-email'])
            ->assertSessionHasErrors('email');
    }
}
