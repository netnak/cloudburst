@php
    use Illuminate\Support\Facades\File;
    use Statamic\Facades\YAML;

    $path = base_path('content/addons/cloudburst.yaml');
    $settings = File::exists($path) ? YAML::parse(File::get($path)) : [];
    $zoneId = $settings['zone_id'] ?? null;
    $domain = $settings['domain'] ?? null;
@endphp

<div class="card h-full p-0">
    <header class="flex items-center justify-between px-4 py-3 border-b">
        <h2 class="flex items-center text-base font-semibold text-gray-800">
            <div class="w-5 h-5 mr-2 text-gray-700">
                @cp_svg('icons/regular/pulse')
            </div>
             <span class="text-gray-700">{{ __('Cloudburst') }}</span>
        </h2>
    </header>

    <div class="px-4 py-3 text-sm text-gray-600">
        <p class="mb-3">
            Clear your Cloudflare cache directly from the Statamic Control Panel.
        </p>

        @if ($zoneId)
            <form method="POST" action="{{ cp_route('cloudburst.purge') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-primary">
                    Purge Cloudflare Cache
                </button>
            </form>
            <p class="mt-2 text-xs text-gray-500">
                This will clear the cloudflare cache for <strong>{{ $domain ?? 'your site' }}</strong><br> (Zone ID: {{ $zoneId }})
            </p>
            
        @else
            <form method="POST" action="{{ cp_route('cloudburst.find_zone_id') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-primary">
                    Connect to Cloudflare
                </button>
            </form>

            <p class="mt-2 text-xs text-gray-500">
                Your zone ID will be automatically retrieved using the site’s domain (you can override this in the .env file)
            </p>
        @endif
    </div>
</div>
