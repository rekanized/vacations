<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_redirects_to_setup_when_no_users_exist(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('setup.show'));
    }

    public function test_application_responses_include_security_headers(): void
    {
        $response = $this->get(route('setup.show'));

        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Permissions-Policy', 'camera=(), geolocation=(), microphone=()');
    }

    public function test_secure_requests_include_hsts_header(): void
    {
        $response = $this->withServerVariables(['HTTPS' => 'on'])->get(route('setup.show'));

        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
