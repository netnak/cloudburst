<?php

namespace Netnak\CloudBurst\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;

class CloudBurstControllerTest extends TestCase
{
   protected function setUp(): void
{
    parent::setUp();

    // Load your routes file manually
    require __DIR__ . '/../../routes/cp.php';

    $this->withoutMiddleware();

    
}

    public function test_purge_success()
    {
        
        $this->app->make(\Netnak\CloudBurst\Services\CloudFlareWrapper::class);
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $response = $this->post('/cloudburst/purge');

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_purge_failure()
    {
        
         
        $this->app->make(\Netnak\CloudBurst\Services\CloudFlareWrapper::class);
        Http::fake([
            '*' => Http::response(['success' => false], 500),
        ]);

        $response = $this->post('/cloudburst/purge');

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }
}
