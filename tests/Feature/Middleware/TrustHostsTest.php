<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\TrustHosts;
use Tests\TestCase;

class TrustHostsTest extends TestCase
{
    // ✅ Memastikan hosts() mengembalikan array dengan subdomain aplikasi
    // Method ini memanggil allSubdomainsOfApplicationUrl() dari parent class
    public function test_hosts_returns_application_subdomain_pattern(): void
    {
        $middleware = new TrustHosts($this->app);
        $hosts      = $middleware->hosts();

        // Harus mengembalikan array
        $this->assertIsArray($hosts);

        // Harus ada minimal satu entry (subdomain pattern dari APP_URL)
        $this->assertNotEmpty($hosts);
    }
}