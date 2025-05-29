<?php

namespace Netnak\CloudBurst\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Netnak\CloudBurst\ServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('cloudburst.access_key', 'DxJFTOE8SqZCAjB6Mj1JiiIsSWUM7DOfTFMj3TVT');
        $app['config']->set('cloudburst.domain', 'testweb.co.uk');
        $app['config']->set('cloudburst.show_widget', true);
    }

    protected function defineRoutes($router)
    {
        require __DIR__ . '/../routes/cp.php';
    }
}
