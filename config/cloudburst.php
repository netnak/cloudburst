<?php

return [
	'show_widget' => true, // default to true to opt-in users by default
	
	'access_key' => env('CLOUDFLARE_ACCESS_KEY', ''),

    'override_domain' => env('CLOUDFLARE_DOMAIN', ''),
	

];
