# ☁️ Cloudburst

> **Cloudburst** is a Statamic addon that integrates with Cloudflare to let you easily purge cache directly from your control panel.

---

## 🚀 Features

- One-click Cloudflare cache purge from Statamic CP  
- Automatically detects and stores your Cloudflare Zone ID  
- Dashboard widget for quick access  
- Configurable via `.env` or config file

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


```pjp
'show_widget' => false
'''
'
---

## ✅ Usage


These values toggle options in `config/cloudburst.php`:

```env

CLOUDBURST_ACCESS_KEY=your_cloudflare_access_key
CLOUDBURST_OVERRIDE_DOMAIN=yourdomain.com # optional override

```
Or edit config/cloudburst.php directly.

---

## 🪪 License

MIT — see the [LICENSE](LICENSE) file for details.
