<?php

namespace Tests\Feature;

use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    public function test_trusted_proxy_preserves_https_in_login_redirects(): void
    {
        config(['trustedproxy.proxies' => ['172.16.0.0/12']]);

        $this->withServerVariables(['REMOTE_ADDR' => '172.22.0.1'])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('http://laundry.bits.my.id/')
            ->assertRedirect('https://laundry.bits.my.id/login');
    }

    public function test_untrusted_client_cannot_override_the_request_scheme(): void
    {
        config(['trustedproxy.proxies' => ['172.16.0.0/12']]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('http://laundry.bits.my.id/')
            ->assertRedirect('http://laundry.bits.my.id/login');
    }

    public function test_forwarded_host_is_ignored_even_from_a_trusted_proxy(): void
    {
        config(['trustedproxy.proxies' => ['172.16.0.0/12']]);

        $this->withServerVariables(['REMOTE_ADDR' => '172.22.0.1'])
            ->withHeaders([
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-Host' => 'untrusted.example',
            ])
            ->get('http://laundry.bits.my.id/')
            ->assertRedirect('https://laundry.bits.my.id/login');
    }
}
