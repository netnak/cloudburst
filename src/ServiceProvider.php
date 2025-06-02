<?php

namespace Netnak\CloudBurst;

use Statamic\Providers\AddonServiceProvider;
use Statamic\Widgets\Widget;
use Netnak\CloudBurst\Widgets\CloudBurstWidget;
use Netnak\CloudBurst\Services\CloudFlareWrapper as CloudFlare;

class ServiceProvider extends AddonServiceProvider
{
	public function register()
	{
	   /* $this->app->singleton(CloudFlare::class, function ($app) {
			return new CloudFlare(config('cloudburst.access_key'));
		});*/

		$this->app->singleton(CloudFlare::class, function ($app) {
			return new CloudFlare($app['config']->get('cloudburst.access_key'));
		});
	}

	public function bootAddon()
	{
		$this->loadViewsFrom(__DIR__ . '/../resources/views', 'cloudburst');
		
		// php artisan vendor:publish --tag=cloudburst-config --force  
		$this->publishes([
			__DIR__ . '/../config/cloudburst.php' => config_path('cloudburst.php'),
		], 'cloudburst-config');

		if (config('cloudburst.show_widget', true)) {
			Widget::register('cloud_burst', CloudBurstWidget::class);
			$this->ensureWidgetIsOnDashboard();
		}
	}

	protected function ensureWidgetIsOnDashboard()
	{
		config([
			'statamic.cp.widgets' => collect(config('statamic.cp.widgets', []))
				->push('cloud_burst')
				->unique()
				->toArray()
		]);
	}
}
