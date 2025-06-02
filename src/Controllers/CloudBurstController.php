<?php

namespace Netnak\CloudBurst\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Netnak\CloudBurst\Services\CloudFlareWrapper as CloudFlare;
use Illuminate\Support\Facades\Log;
use Statamic\Facades\YAML;
use Illuminate\Support\Facades\File;

class CloudBurstController extends Controller
{
	protected CloudFlare $cloudFlare;

	public function __construct(CloudFlare $cloudFlare)
	{
		$this->cloudFlare = $cloudFlare;
	}

	/**
	 * Purge entire Cloudflare cache for all saved zones.
	 * 
	 * @param Request $request
	 * @return JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	public function purge(Request $request)
	{
		try {
			$results = $this->purgeAll();

			$successMessage = count($results['success']) === 1 
				? "Successfully purged Cloudflare cache for: " . implode(', ', $results['success'])
				: "Successfully purged Cloudflare cache for " . count($results['success']) . " domains: " . implode(', ', $results['success']);

			if (!empty($results['errors'])) {
				$successMessage .= ". Some domains failed: " . implode(', ', $results['errors']);
			}

			// Return JSON for AJAX requests
			if ($request->expectsJson() || $request->ajax()) {
				return response()->json([
					'success' => empty($results['errors']) || !empty($results['success']), // Success if any purged or no errors
					'message' => $successMessage,
					'data' => $results
				]);
			}

			// Fallback for non-AJAX requests
			return back()->with('success', $successMessage);

		} catch (\Exception $e) {
			Log::error('Cloudflare purge failed: ' . $e->getMessage());

			if ($request->expectsJson() || $request->ajax()) {
				return response()->json([
					'success' => false,
					'message' => 'There was a problem purging Cloudflare cache. Check logs for details.'
				], 500);
			}

			return back()->with('error', 'There was a problem purging Cloudflare cache. Check logs for details.');
		}
	}

	/**
	 * Purge all cache via Cloudflare API for all zones.
	 * 
	 * @return array Results with success and error arrays
	 * @throws \Exception
	 */
	private function purgeAll(): array
	{
		$settings = $this->getSettings();
		
		// Support both old single zone format and new multiple zones format
		$zones = [];
		if (isset($settings['zones']) && is_array($settings['zones'])) {
			// New format: multiple zones
			$zones = $settings['zones'];
		} elseif (isset($settings['zone_id']) && isset($settings['domain'])) {
			// Old format: single zone - convert to new format
			$zones = [$settings['domain'] => [
				'zone_id' => $settings['zone_id'],
				'domain' => $settings['domain']
			]];
		}

		if (empty($zones)) {
			throw new \Exception('No Cloudflare zones configured. Please connect to Cloudflare first.');
		}

		$results = ['success' => [], 'errors' => []];

		foreach ($zones as $domain => $zoneData) {
			try {
				$zoneId = $zoneData['zone_id'];
				$domainName = $zoneData['domain'];

				$response = $this->cloudFlare->post("zones/{$zoneId}/purge_cache", ['purge_everything' => true]);

				if ($response) {
					$results['success'][] = $domainName;
				} else {
					$error = $this->cloudFlare->getLastResponse()['body'] ?? 'Unknown error';
					$results['errors'][] = "{$domainName}: {$error}";
				}
			} catch (\Exception $e) {
				$results['errors'][] = "{$domain}: " . $e->getMessage();
			}
		}

		// If no successes and we have errors, throw exception
		if (empty($results['success']) && !empty($results['errors'])) {
			throw new \Exception('All purge operations failed: ' . implode(', ', $results['errors']));
		}

		return $results;
	}

	/**
	 * Find zone ID for domain(s) and save them.
	 * 
	 * @param Request $request
	 * @return JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	public function findZoneId(Request $request)
	{
		// Get domains from config - can be string or comma-separated list
		$domainConfig = config('cloudburst.override_domain') ?? env('CLOUDFLARE_DOMAIN');
	   
		if (!$domainConfig) {
			// Fallback to app URL
			$host = parse_url(config('app.url'), PHP_URL_HOST);
			if (!$host) {
				$errorMessage = 'No domain found in app URL or CLOUDFLARE_DOMAIN env variable.';
				
				if ($request->expectsJson() || $request->ajax()) {
					return response()->json([
						'success' => false,
						'message' => $errorMessage
					], 400);
				}

				return back()->with('error', $errorMessage);
			}
			$domains = [$this->getRootDomain($host)];
		} else {
			// Parse domains - can be comma-separated
			$domains = array_map('trim', explode(',', $domainConfig));
			$domains = array_map([$this, 'getRootDomain'], $domains);
			$domains = array_unique(array_filter($domains)); // Remove duplicates and empty values
		}

		if (empty($domains)) {
			$errorMessage = 'No valid domains found to process.';
			
			if ($request->expectsJson() || $request->ajax()) {
				return response()->json([
					'success' => false,
					'message' => $errorMessage
				], 400);
			}

			return back()->with('error', $errorMessage);
		}

		try {
			$results = [];
			$errors = [];
			
			foreach ($domains as $domain) {
				
				try {
					$response = $this->cloudFlare->get('zones', ['name' => $domain]);
 
					if (!$this->cloudFlare->success()) {
						$errors[] = "Failed to fetch zone for {$domain}: " . $this->cloudFlare->getLastError();
						continue;
					}
	
					if (!empty($response['result'][0]['id'])) {
						$zoneId = $response['result'][0]['id'];
						$results[$domain] = [
							'zone_id' => $zoneId,
							'domain' => $domain,
							'connected_at' => now()->toISOString()
						];
					} else {
						$errors[] = "Zone ID not found for domain: {$domain}";
					}

				} catch (\Exception $e) {
					$errors[] = "Error fetching zone for {$domain}: " . $e->getMessage();
				}
			}

			
			if (empty($results)) {
				$errorMessage = 'Errors: ' . implode(', ', $errors);
				
				if ($request->expectsJson() || $request->ajax()) {
					return response()->json([
						'success' => false,
						'message' => $errorMessage
					], 404);
				}

				return back()->with('error', $errorMessage);
			}

			// Save all found zones
			$this->saveSettings(['zones' => $results]);

			$successDomains = array_keys($results);
			$successMessage = count($successDomains) === 1 
				? "Successfully connected to Cloudflare for domain: " . $successDomains[0]
				: "Successfully connected to Cloudflare for " . count($successDomains) . " domains: " . implode(', ', $successDomains);

			if (!empty($errors)) {
				$successMessage .= ". Some domains failed: " . implode(', ', $errors);
			}

			if ($request->expectsJson() || $request->ajax()) {
				return response()->json([
					'success' => true,
					'message' => $successMessage,
					'reload' => true,
					'data' => [
						'zones' => $results,
						'domain_count' => count($results)
					]
				]);
			}

			return back()->with('success', $successMessage);

		} catch (\Exception $e) {
			$errorMessage = 'Error fetching Zone IDs: ' . $e->getMessage();
			
			if ($request->expectsJson() || $request->ajax()) {
				return response()->json([
					'success' => false,
					'message' => $errorMessage
				], 500);
			}

			return back()->with('error', $errorMessage);
		}
	}

	/**
	 * Clear all saved settings by deleting the YAML file.
	 * 
	 * @param Request $request
	 * @return JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	public function clearSettings(Request $request)
	{
		try {
			$path = base_path('content/addons/cloudburst.yaml');
			
			if (File::exists($path)) {
				File::delete($path);
				$message = 'Cloudflare settings cleared successfully. You can now reconnect with a different domain.';
			} else {
				$message = 'No settings found to clear.';
			}

			if ($request->expectsJson() || $request->ajax()) {
				return response()->json([
					'success' => true,
					'message' => $message,
					'reload' => true
				]);
			}

			return back()->with('success', $message);

		} catch (\Exception $e) {
			Log::error('Failed to clear Cloudburst settings: ' . $e->getMessage());
			
			$errorMessage = 'Failed to clear settings. Check logs for details.';
			
			if ($request->expectsJson() || $request->ajax()) {
				return response()->json([
					'success' => false,
					'message' => $errorMessage
				], 500);
			}

			return back()->with('error', $errorMessage);
		}
	}

	/**
	 * Extract root domain (e.g. example.com, example.co.uk) from a host string.
	 * 
	 * @param string $host
	 * @return string
	 */
	protected function getRootDomain(string $host): string
	{
		$parts = explode('.', $host);
		$count = count($parts);

		// Handle multi-level TLDs (uk, au, nz)
		if ($count >= 3) {
			$last = $parts[$count - 1];
			if (in_array($last, ['uk', 'au', 'nz'])) {
				return implode('.', array_slice($parts, -3));
			}
			return implode('.', array_slice($parts, -2));
		}

		return $host;
	}

	/**
	 * Load addon settings from YAML.
	 * 
	 * @return array
	 */
	protected function getSettings(): array
	{
		$path = base_path('content/addons/cloudburst.yaml');

		if (!File::exists($path)) {
			return [];
		}

		return YAML::parse(File::get($path)) ?? [];
	}

	/**
	 * Save addon settings to YAML.
	 * 
	 * @param array $settings
	 * @return void
	 */
	protected function saveSettings(array $settings): void
	{
		$directory = base_path('content/addons');
		$path = $directory . '/cloudburst.yaml';

		if (!File::exists($directory)) {
			File::makeDirectory($directory, 0755, true);
		}

		$current = $this->getSettings();
		$merged = array_merge($current, $settings);

		$yaml = YAML::dump($merged);

		File::put($path, $yaml);
	}
}