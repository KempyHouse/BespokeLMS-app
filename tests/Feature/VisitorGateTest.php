<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class VisitorGateTest extends TestCase
{
    public function test_unauthenticated_visitors_are_redirected_to_sign_in(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_the_sign_in_screen_renders_for_guests(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Sign in');
    }

    public function test_the_forgot_password_screen_renders_for_guests(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Reset your password');
    }
}
