<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Auth\SupabaseUser;
use App\Support\Supabase\Contracts\AuthenticatesWithSupabase;
use App\Support\Supabase\Contracts\ReadsProfiles;
use Tests\Fakes\FakeProfiles;
use Tests\Fakes\FakeSupabaseAuth;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    /**
     * @param  array<string,string>      $valid
     * @param  array<string,mixed>|null  $profile
     */
    private function fakeSupabase(array $valid, ?array $profile, string $email = 'owner@example.com'): void
    {
        $this->app->instance(AuthenticatesWithSupabase::class, new FakeSupabaseAuth($valid, [
            'access_token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'expires_at' => 0,
            'user' => ['id' => 'uid-1', 'email' => $email],
        ]));

        $this->app->instance(ReadsProfiles::class, new FakeProfiles($profile));
    }

    /**
     * @return array<string,mixed>
     */
    private function ownerProfile(): array
    {
        return [
            'id' => 'profile-1',
            'role' => 'bespokelms_owner',
            'full_name' => 'Marcus Reed',
            'theme_preference' => 'light',
            'organization_id' => 'org-1',
            'organizations' => ['name' => 'BespokeLMS', 'slug' => 'bespokelms', 'type' => 'platform'],
        ];
    }

    public function test_valid_credentials_start_a_session(): void
    {
        $this->fakeSupabase(['owner@example.com' => 'secret-password'], $this->ownerProfile());

        $this->post(route('login'), [
            'email' => 'owner@example.com',
            'password' => 'secret-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $this->fakeSupabase(['owner@example.com' => 'secret-password'], $this->ownerProfile());

        $this->from(route('login'))->post(route('login'), [
            'email' => 'owner@example.com',
            'password' => 'wrong-password',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_email_and_password_are_required(): void
    {
        $this->from(route('login'))->post(route('login'), [])
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    }

    public function test_a_user_without_an_application_profile_is_refused(): void
    {
        $this->fakeSupabase(['ghost@example.com' => 'secret-password'], null, 'ghost@example.com');

        $this->from(route('login'))->post(route('login'), [
            'email' => 'ghost@example.com',
            'password' => 'secret-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_the_dashboard_is_gated_and_carries_the_real_user(): void
    {
        // Guests cannot see the dashboard.
        $this->get(route('dashboard'))->assertRedirect(route('login'));

        // Signed-in users get the dashboard with their real identity injected.
        $user = new SupabaseUser(
            id: 'uid-1',
            email: 'owner@example.com',
            name: 'Marcus Reed',
            role: 'bespokelms_owner',
            profileId: 'profile-1',
            organizationId: 'org-1',
            organizationName: 'BespokeLMS',
            organizationSlug: 'bespokelms',
            organizationType: 'platform',
        );

        $this->actingAs($user, 'web')->get(route('dashboard'))
            ->assertOk()
            ->assertSee('owner@example.com')     // injected real user payload
            ->assertSee('Platform Overview');    // the dashboard content itself
    }

    public function test_an_authenticated_user_can_sign_out(): void
    {
        $user = new SupabaseUser(
            id: 'uid-1',
            email: 'owner@example.com',
            name: 'Marcus Reed',
            role: 'bespokelms_owner',
        );

        $this->actingAs($user, 'web')->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
