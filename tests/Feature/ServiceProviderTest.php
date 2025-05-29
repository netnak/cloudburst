<?php

namespace Netnak\CloudBurst\Tests\Unit;

use Mockery;
use Statamic\Widgets\Widget;
use Netnak\CloudBurst\ServiceProvider;
use Orchestra\Testbench\TestCase;

class ServiceProviderTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testWidgetRegistration()
    {
        // Mock the Widget class (static class)
        $widgetMock = Mockery::mock('alias:' . Widget::class);
        
        // Expect register to be called with your widget handle and class
        $widgetMock->shouldReceive('register')
            ->once()
            ->with('cloud_burst', \Netnak\CloudBurst\Widgets\CloudBurstWidget::class);

        // Instantiate your service provider
        $provider = $this->app->getProvider(ServiceProvider::class);

        // Call the bootAddon method which should call Widget::register()
        $provider->bootAddon();

        // Verify expectations
        Mockery::close();
    }

    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }
}
