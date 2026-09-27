<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_the_login_page_at_the_root(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('auth/Login'));
    }

    public function test_redirects_authenticated_users_from_the_root_to_the_app(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('app.dashboard'));
    }

    public function test_redirects_guests_away_from_the_tenant_app(): void
    {
        $this->get('/app')->assertRedirect(route('login'));
    }

    public function test_logs_in_an_existing_user(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();

        $this->post('/register', [
            'name' => 'Walk In',
            'email' => 'walkin@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'tenant_name' => 'Walk In Inc',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'walkin@example.com']);
    }
}
