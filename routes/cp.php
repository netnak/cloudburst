<?php

use Illuminate\Support\Facades\Route;
use Netnak\CloudBurst\Controllers\CloudBurstController;

Route::post('/cloudburst/purge', [CloudBurstController::class, 'purge'])
	->name('cloudburst.purge')
	->middleware('statamic.cp.authenticated');

Route::post('cloudburst/find-zone-id', [CloudburstController::class, 'findZoneId'])
	->name('cloudburst.find_zone_id')
	->middleware('statamic.cp.authenticated');
