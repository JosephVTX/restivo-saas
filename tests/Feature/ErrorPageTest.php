<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    public function test_unknown_pages_render_the_themed_inertia_error_page(): void
    {
        $this->get('/esta-ruta-no-existe')
            ->assertNotFound()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('ErrorPage')
                ->where('status', 404));
    }

    public function test_api_errors_still_return_json_instead_of_the_error_page(): void
    {
        $response = $this->getJson('/api/v1/ruta-que-no-existe');

        $response->assertNotFound();
        $this->assertStringContainsString(
            'application/json',
            (string) $response->headers->get('content-type'),
        );
    }
}
