<?php

namespace Netnak\CloudBurst\Controllers;

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
     * Purge entire Cloudflare cache for the saved zone.
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function purge()
    {
        try {
            $domain = $this->purgeAll();

            return back()->with('success', "Successfully purged Cloudflare cache for {$domain}");

        } catch (\Exception $e) {
            Log::error('Cloudflare purge failed: ' . $e->getMessage());

            return back()->with('error', 'There was a problem purging Cloudflare cache. Check logs for details.');
        }
    }

    /**
     * Purge all cache via Cloudflare API.
     * 
     * @return string Domain purged
     * @throws \Exception
     */
    private function purgeAll(): string
    {
        $settings = $this->getSettings();
        $zoneId = $settings['zone_id'] ?? null;
        $domain = $settings['domain'] ?? null;

        if (!$zoneId) {
            throw new \Exception('Cloudflare Zone ID is missing. Please find the zone ID first.');
        }

        $response = $this->cloudFlare->post("zones/{$zoneId}/purge_cache", ['purge_everything' => true]);

        if (!$response) {
            throw new \Exception('Cloudflare API error: ' . $this->cloudFlare->getLastResponse()['body'] ?? 'Unknown error');
        }

        return $domain ?? 'unknown domain';
    }

    /**
     * Find zone ID for domain and save it.
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function findZoneId()
    {
        // Determine domain to use, prefer override_domain config
        $host = config('cloudburst.override_domain') ?? parse_url(config('app.url'), PHP_URL_HOST);

        if (!$host) {
            return redirect()
                ->back()
                ->with('error', 'No domain found in app URL or override domain.');
        }

        $domain = $this->getRootDomain($host);

        try {
            $response = $this->cloudFlare->get('zones', ['query' => ['name' => $domain]]);
            $data = $response;

            if (!empty($data['result'][0]['id'])) {
                $zoneId = $data['result'][0]['id'];

                // Save zone ID and domain
                $this->saveSettings(['zone_id' => $zoneId, 'domain' => $domain]);

                return redirect()
                    ->back()
                    ->with('success', "Cloudflare zone ID found for domain {$domain} (ID: {$zoneId}).");
            }

            return redirect()
                ->back()
                ->with('error', "Zone ID not found for domain {$domain}.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error fetching Zone ID: ' . $e->getMessage());
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
