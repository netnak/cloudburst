@php
    use Illuminate\Support\Facades\File;
    use Statamic\Facades\YAML;
    $path = base_path('content/addons/cloudburst.yaml');
    $settings = File::exists($path) ? YAML::parse(File::get($path)) : [];

    // Support both old single zone format and new multiple zones format
    $zones = [];
    $hasConnection = false;

    if (isset($settings['zones']) && is_array($settings['zones'])) {
        // New format: multiple zones
        $zones = $settings['zones'];
        $hasConnection = !empty($zones);
    } elseif (isset($settings['zone_id']) && isset($settings['domain'])) {
        // Old format: single zone - convert for display
        $zones = [
            $settings['domain'] => [
                'zone_id' => $settings['zone_id'],
                'domain' => $settings['domain'],
            ],
        ];
        $hasConnection = true;
    }
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

        <div id="cloudburst-status" class="mb-3 p-2 rounded hidden"></div>

        @if ($zones && count($zones) > 0)
            <div class="space-y-3">
                <button type="button" id="cloudburst-purge-btn" class="btn btn-sm btn-primary " data-action="purge"
                    data-url="{{ cp_route('cloudburst.purge') }}">
                    <span class="btn-text">Purge Cloudflare Cache</span>
                    <span class="btn-spinner hidden">
                        <svg class="animate-spin h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        Purging...
                    </span>
                </button>

                <button type="button" id="cloudburst-clear-btn"
                    class="btn btn-sm btn-outline text-red-600 border-red-300 hover:bg-red-50" data-action="clear"
                    data-url="{{ cp_route('cloudburst.clear_settings') }}">
                    <span class="btn-text">Clear Settings</span>
                    <span class="btn-spinner hidden">
                        <svg class="animate-spin h-4 w-4 text-red-600 inline-block" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        Clearing...
                    </span>
                </button>
            </div>

            <p class="mt-2 text-xs text-gray-500">
                This will clear the cloudflare cache for<br>
                @foreach ($zones as $zone)
                    <label>{{ $zone['domain'] }} - {{ $zone['zone_id'] }}</label>
                @endforeach
            </p>
        @else
            <button type="button" id="cloudburst-connect-btn" class="btn btn-sm btn-primary" data-action="connect"
                data-url="{{ cp_route('cloudburst.find_zone_id') }}">
                <span class="btn-text">Connect to Cloudflare</span>
                <span class="btn-spinner hidden">
                    <svg class="animate-spin h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    Connecting...
                </span>
            </button>
            <p class="mt-2 text-xs text-gray-500">
                Your zone ID will be automatically retrieved using the site's domain (you can override this in your .env
                file)
            </p>

        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const CloudburstWidget = {
            init() {
                this.bindEvents();
            },

            bindEvents() {
                const purgeBtn = document.getElementById('cloudburst-purge-btn');
                const connectBtn = document.getElementById('cloudburst-connect-btn');
                const clearBtn = document.getElementById('cloudburst-clear-btn');

                if (purgeBtn) {
                    purgeBtn.addEventListener('click', (e) => this.handleAction(e));
                }

                if (connectBtn) {
                    connectBtn.addEventListener('click', (e) => this.handleAction(e));
                }

                if (clearBtn) {
                    clearBtn.addEventListener('click', (e) => this.handleClearSettings(e));
                }
            },

            async handleClearSettings(event) {
                const button = event.currentTarget;
                const url = button.dataset.url;

                // Show confirmation dialog
                if (!confirm(
                        'Are you sure you want to clear the Cloudflare settings, this will remove all zone information?'
                    )) {
                    return;
                }

                this.setButtonLoading(button, true);
                this.hideStatus();

                try {
                    const response = await this.makeRequest(url);

                    if (response.success) {
                        this.showStatus('success', response.message ||
                            'Settings cleared successfully!');

                        // Reload the page to show the connect button
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        this.showStatus('error', response.message || 'An error occurred');
                    }
                } catch (error) {
                    console.error('Cloudburst clear settings error:', error);
                    this.showStatus('error', 'Network error occurred. Please try again.');
                } finally {
                    this.setButtonLoading(button, false);
                }
            },

            async handleAction(event) {
                const button = event.currentTarget;
                const action = button.dataset.action;
                const url = button.dataset.url;

                this.setButtonLoading(button, true);
                this.hideStatus();

                try {
                    const response = await this.makeRequest(url);

                    if (response.success) {
                        this.showStatus('success', response.message ||
                            `${action === 'purge' ? 'Cache purged' : 'Connected'} successfully!`);

                        // If connecting and we got zone info, refresh the page to show the purge button
                        if (action === 'connect' && response.reload) {
                            setTimeout(() => window.location.reload(), 1500);
                        }
                    } else {
                        this.showStatus('error', response.message || 'An error occurred');
                    }
                } catch (error) {
                    console.error('Cloudburst error:', error);
                    this.showStatus('error', 'Network error occurred. Please try again.');
                } finally {
                    this.setButtonLoading(button, false);
                }
            },

            async makeRequest(url) {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                return await response.json();
            },

            setButtonLoading(button, loading) {
                const textSpan = button.querySelector('.btn-text');
                const spinnerSpan = button.querySelector('.btn-spinner');

                if (loading) {
                    textSpan.classList.add('hidden');
                    spinnerSpan.classList.remove('hidden');
                    button.disabled = true;
                } else {
                    textSpan.classList.remove('hidden');
                    spinnerSpan.classList.add('hidden');
                    button.disabled = false;
                }
            },

            showStatus(type, message) {
                const statusDiv = document.getElementById('cloudburst-status');

                statusDiv.className =
                    `mb-3 p-2 rounded ${type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'}`;
                statusDiv.textContent = message;
                statusDiv.classList.remove('hidden');
            },

            hideStatus() {
                const statusDiv = document.getElementById('cloudburst-status');
                statusDiv.classList.add('hidden');
            }
        };

        CloudburstWidget.init();
    });
</script>
