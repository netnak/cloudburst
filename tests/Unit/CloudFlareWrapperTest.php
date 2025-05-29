<?php

namespace Netnak\CloudBurst\Tests\Unit;

use Netnak\CloudBurst\Services\CloudFlareWrapper;
use Orchestra\Testbench\TestCase;

class CloudFlareWrapperTest extends TestCase
{
    public function test_it_initializes_correctly()
    {
        $wrapper = new CloudFlareWrapper('dummy-key');
        $this->assertInstanceOf(CloudFlareWrapper::class, $wrapper);
    }

    public function test_methods_exist()
    {
        $wrapper = new CloudFlareWrapper('dummy-key');
        $this->assertTrue(method_exists($wrapper, 'get'));
        $this->assertTrue(method_exists($wrapper, 'post'));
    }
}
