# ApiDocs Module — Plug & Play

Self-contained API documentation module for Laravel admin panels.  
Based on design mockup **nova-connect-api-design-2-payment-payout**.

## What's included

```
modules/ApiDocs/
├── config/api-docs.php          ← Edit brand, base URL, page content
├── routes/web.php               ← Admin routes (loaded inside admin group)
├── src/
│   ├── ApiDocsServiceProvider.php
│   └── Http/Controllers/DocsController.php
├── resources/views/             ← Blade templates (design 2 layout)
└── public/                      ← CSS + JS assets
```

## Install in a new project (Khushi, Mono, etc.)

### 1. Copy the folder

Copy the entire `modules/ApiDocs` directory into the target Laravel project root.

### 2. Composer autoload

Add to `composer.json` → `autoload.psr-4`:

```json
"Modules\\ApiDocs\\": "modules/ApiDocs/src/"
```

Then run (recommended):

```bash
composer dump-autoload
```

**Note:** The module also ships `modules/ApiDocs/autoload.php`, loaded automatically from `AppServiceProvider`, so it works even before `composer dump-autoload` is run.

### 3. Register service provider

Add to `config/app.php` → `providers`:

```php
Modules\ApiDocs\ApiDocsServiceProvider::class,
```

### 4. Load routes

Inside your admin route group in `routes/admin.php` (must already use `auth` + `admin` middleware):

```php
require base_path('modules/ApiDocs/routes/web.php');
```

### 5. Publish assets

```bash
php artisan vendor:publish --tag=api-docs-assets
```

Or manually copy `modules/ApiDocs/public/*` → `public/vendor/api-docs/`

### 6. Add admin sidebar link (optional)

Add one entry in `resources/views/admin/layout/include/sidebar.blade.php`:

```blade
<li class="@if (Route::is('admin.api-docs.*')) active @endif nav-item">
    <a class="d-flex align-items-center" href="{{ route('admin.api-docs.show', 'get-started') }}">
        <i data-feather="book-open"></i>API Docs</a>
</li>
```

**Do not modify** existing admin sidebar colors or styles — only add this link.

### 7. Customize per project

Edit `modules/ApiDocs/config/api-docs.php` or publish config:

```bash
php artisan vendor:publish --tag=api-docs-config
```

Environment variables (optional):

| Variable | Default | Description |
|----------|---------|-------------|
| `API_DOCS_BRAND_NAME` | Monotech | Brand name shown in docs |
| `API_DOCS_BASE_URL` | https://monotech.pk/api | API base URL |
| `API_DOCS_LOGO` | favicon.ico | Logo asset path |
| `API_DOCS_SUPPORT_EMAIL` | info@monotech.pk | Support email |
| `API_DOCS_SERVER_IP` | _(empty)_ | Server IP for callback whitelist notice |
| `API_DOCS_API_VERSION` | v1 | Version badge |

## URLs

| Page | URL |
|------|-----|
| Get Started | `/admin/api-docs/get-started` |
| Payment Checkout | `/admin/api-docs/payment-checkout` |
| Payment Payout | `/admin/api-docs/payment-payout` |
| Status Check | `/admin/api-docs/status-check` |
| Dashboard Data | `/admin/api-docs/dashboard-data` |
| Callbacks | `/admin/api-docs/callbacks` |
| HMAC Generator | `/admin/api-docs/hmac-generator` |

## Editing content

All documentation text, parameters, and JSON examples live in:

`modules/ApiDocs/config/api-docs.php` → `pages` array

Use placeholders in strings:

- `:brand` — brand name
- `:base_url` — API base URL
- `:support_email` — support email
- `:server_ip` — server IP for callbacks notice

## Design notes

- **Admin sidebar** (Vuexy): unchanged — only a single "API Docs" link is added
- **Docs sidebar** (inside page): design 2 purple navigation (`#5850ec`)
- **Code panel**: dark slate right column with copy buttons
