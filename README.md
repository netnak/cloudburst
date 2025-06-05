# ☁️ Cloudburst

> **Cloudburst** is a Statamic addon that integrates with Cloudflare to let you easily purge cache directly from your control panel.

---

## 🚀 Features

- One-click Cloudflare cache purge from Statamic CP  
- Automatically detects and stores your Cloudflare Zone ID  
- Dashboard widget for quick access  
- Configurable via `.env` or config file
- allows multi domain cache busting

---

## 📦 Installation

Install via Composer:

```bash
composer require netnak/cloudburst
```

Then publish the config file (optional):

```bash
php artisan vendor:publish --tag=cloudburst-config --force
```

---

## ⚙️ Configuration

Cloudburst auto-registers itself as a widget

To manually turn it off you can use the setting in the config


```php
'show_widget' => false
'''

---

## ✅ Usage

This addon *requires* a CloudFlare API token. It will need read access to the cloudflare API zone resources and purge access to cache purge.
These env variables override defaults in `config/cloudburst.php`:

```env
CLOUDBURST_ACCESS_KEY=your_cloudflare_access_key
CLOUDBURST_OVERRIDE_DOMAIN=yourdomain.com # optional override or CSV yourdomain.com,yourotherdomain.com

```

Or edit config/cloudburst.php directly.

---

## 🪪 License

MIT — see the [LICENSE](LICENSE) file for details.
