<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenancy;
use Tests\TestCase;

class HistorySecurityTest extends TestCase
{
    use InteractsWithTenancy, RefreshDatabase;

    public function test_authenticated_pages_encrypt_the_history_state(): void
    {
        $tenant = $this->createTenant('Secure Co');
        $this->actingAsMember($tenant, 'owner');

        $this->get('https://localhost/app')
            ->assertOk()
            ->assertSee('"encryptHistory":true', false);
    }

    public function test_guest_pages_do_not_encrypt_the_history_state(): void
    {
        $this->get('https://localhost/login')
            ->assertOk()
            ->assertDontSee('"encryptHistory":true', false);
    }

    public function test_logging_out_clears_the_encrypted_history(): void
    {
        $tenant = $this->createTenant('Secure Co');
        $this->actingAsMember($tenant, 'owner');

        $this->post('/logout')->assertRedirect('/');

        $this->get('https://localhost/')
            ->assertOk()
            ->assertSee('"clearHistory":true', false);
    }
}
