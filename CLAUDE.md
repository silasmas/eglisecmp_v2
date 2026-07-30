# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

Laravel 12 (PHP 8.3) application for **Centre Missionnaire Philadelphie** ("CMP"), a church in
Kinshasa (DRC). It has two faces:

- A **Filament 5 admin panel** (`/admin`) for staff — content management, donations/offerings,
  appointments, testimonies, YouTube sync, etc.
- A **public React SPA** ("site") served by Laravel and consumed via a read-mostly JSON API under
  `/api/site/*`.

The app appears to be a rebuild/modernization of a legacy PHP church-management system: migrations
explicitly recreate legacy `roles`/`permissions`/`user_roles`/`password_resets` tables
(`2026_04_13_160200_create_sitecmp_legacy_auth_tables.php`) alongside new Spatie-permission-style
tables, and sync commands bridge legacy role data into `model_has_roles`. Table/migration names use
the `sitecmp` prefix.

## Commands

- Install PHP deps: `composer install`
- Install JS deps: `npm install`
- Full local dev (server + queue worker + logs + vite, concurrently): `composer run dev`
- Vite dev server only: `npm run dev`
- Production frontend build: `npm run build`
- Run all tests: `php artisan test` (or `vendor/bin/phpunit`)
- Run a single test: `php artisan test --filter=TestClassName::test_method_name`
  (or `vendor/bin/phpunit --filter=test_method_name path/to/TestFile.php`)
- Code style: `vendor/bin/pint` (Laravel Pint; no separate lint script is defined in composer.json)
- Tinker: `php artisan tinker`

No `npm run lint` / `npm test` scripts are defined in package.json — only `dev` and `build`.

## Architecture

### Backend structure
- `app/Http/Controllers` — small set of top-level controllers: FlexPay payment callbacks
  (`FlexPayCallbackController`, `FlexPayPaidController`), deployment/ops webhooks
  (`StorageLinkController`, `ShieldSyncController`, `SchedulerHttpController`).
- `app/Http/Controllers/Api/Site` — the public JSON API consumed by the React SPA (all `Public*`
  controllers: events, posts, testimonies, galleries, offrandes/donations, appointments, daily
  verse, alert subscriptions, reactions, search, etc.). These are read-mostly + a few
  throttled `store` endpoints. No `Http/Requests` (form request classes) or `Actions` directories
  exist — validation is done inline in controllers/services.
- `app/Services` — business logic lives here (not in controllers): `FlexPayGatewayService` (payment
  gateway), `AppointmentAvailabilityService` / `AppointmentConfirmationService`,
  `PrayerRequestNotificationService`, `TestimonyNotificationService`, `SmsSender`,
  `UserRoleSyncService`, `SiteSchedulerRunner`, plus `Alert/` and `Youtube/` sub-namespaces
  (YouTube channel/live sync).
- `app/Jobs` — `SyncYoutubeChannelJob` (queued YouTube import).
- `app/Policies` — one policy per Filament resource model (standard Laravel authorization),
  wired through Filament Shield.
- `app/Models` — Eloquent models for church domain entities: `Event`, `Post`, `Testimony`,
  `TestimonyImage`, `TestimonyWallSetting`, `Gallery`, `Minister`, `MinisterReceptionSchedule`,
  `Bureau`, `BundaProgram`, `ScheduleProgram`, `DailyVerse`, `Offrande` (donation/offering),
  `Transaction` (payment record), `SiteInquiry` (contact/appointment/prayer requests),
  `SiteStatistic`, `AlertSubscription`, `ContentReaction`, `YoutubeSyncRun`, `Role`, `User`.
- `routes/web.php` — FlexPay callback/paid routes, deploy webhooks, and a catch-all that serves the
  `site` Blade view (mounting the React SPA) for any path not starting with
  `admin|api|livewire|broadcasting|storage|sanctum|_ignition|horizon|telescope|build|paid`
  (also mirrored under `/public/*` for hosts where the public URL includes that segment).
- `routes/api.php` — all under `Route::prefix('site')` with `SetSiteApiLocale` middleware; heavy use
  of `throttle:N,1` on write endpoints (inquiries, testimonies, reactions, alert subscriptions,
  donation init/process).
- `routes/console.php` — scheduled commands: `youtube:sync` (30 min), `youtube:check-live` (3 min),
  `events:check-alerts` (5 min). A parallel HTTP-triggered scheduler exists
  (`SchedulerHttpController`, `config/site_scheduler.php`) for hosts without real cron.

### Admin panel (Filament 5)
Single panel at `/admin` (`app/Providers/Filament/AdminPanelProvider.php`), resources
auto-discovered from `app/Filament/Resources`. Notable plugins wired in:
- **Filament Shield** — role/permission management (`bezhansalleh/filament-shield`).
- **Filament Studio** (`flexpik/filament-studio`) — a dynamic/no-code collection builder; its own
  migrations create `studio_collections`, `studio_fields`, `studio_records`, `studio_values`,
  `studio_dashboards`, `studio_panels`, `studio_api_keys`, etc. Has its own policies
  (`StudioCollectionPolicy`, `StudioDashboardPolicy`, `StudioRecordPolicy`).
- Media Manager, Media Gallery, Menu Manager, Search Spotlight, Record Watcher, Pinnable
  Navigation, Clear Cache (local/staging only), Tabbed — all third-party Filament plugins adding
  navigation groups (Studio, Navigation, Médias).
- Activity log resource from `mradder/filament-logger`.

### Frontend stack
- **Admin panel**: Filament (Blade + Livewire 4), themed via `resources/css/filament/admin/theme.css`.
- **Public site**: React 19 + TypeScript SPA under `resources/js/site/` (entry `main.tsx`; pages,
  components, hooks, context, routes, lib, data, styles subfolders), using `react-router-dom`,
  `framer-motion`, `lucide-react`, Tailwind CSS v4. Built via Vite (`laravel-vite-plugin`) with two
  independent entry graphs: legacy `resources/js/app.js` and the SPA `resources/js/site/main.tsx`.
  `tsconfig.site.json` scopes TypeScript checking to just `resources/js/site/**/*.{ts,tsx}` (React
  JSX, ES2022, bundler resolution) — the rest of the repo has no TS config.
- Single Blade entry point `resources/views/site.blade.php` mounts the SPA; `resources/views/home.blade.php`
  and `welcome.blade.php` appear to be legacy/unused Laravel starter views.

### Auth
Default Laravel session guard (`config/auth.php`), `App\Models\User` provider. Layered with
Spatie-style `model_has_roles` (via Filament Shield) plus legacy `roles`/`user_roles` tables kept
for backward compatibility and synced by `UserRoleSyncService` / dedicated migrations
(`sync_legacy_user_roles_to_spatie_model_has_roles`, `sync_legacy_user_roles_to_spatie`).

### Payments — FlexPay integration
Church donations ("offrandes") are processed through **FlexPay** (Congolese payment gateway, Mobile
Money + card). Full integration guide lives in `docs/integration-paiement-flexpay/` — **read
`08-MOBILE-MONEY-CORRECTIFS.md` before touching Mobile Money code**. Key gotcha: the FlexPay API
`type` field is **always `"1"`** for every Mobile Money operator (M-Pesa, Airtel, Orange, Afri…);
`"2"` is card only. The operator picker in the UI is only for phone-number validation —
FlexPay itself routes based on the `phone` value, not a per-operator `type`. Config lives in
`config/flexpay.php` / `config/services.php`; env vars documented in
`docs/integration-paiement-flexpay/07-EXEMPLE-ENV.md`. Callback routes:
`/payment/flexpay/callback/{mobile|card}` and `/paid/{reference}/{amount}/{currency}/{status}`.
`Offrande` and `Transaction` models back this flow; `PublicOffrandePaymentController` exposes
`offrandes/init`, `offrandes/process`, `offrandes/status` to the SPA.

### Other integrations
- **YouTube Data API v3** — channel/live sync (`app/Services/Youtube/`, `SyncYoutubeChannelJob`,
  `youtube:sync` / `youtube:check-live` commands) feeding the "Enseignements" (teachings) section;
  config in `config/site_public.php` (`youtube_sync`, `youtube_meditation_playlist_groups`).
- **SMS** — `SmsSender` service, `config/sms.php`, used for appointment confirmations and prayer
  request notifications.
- Queue driver defaults to `database` (`QUEUE_CONNECTION` env-overridable).

### Docs
- `docs/` is current; `docs/integration-paiement-flexpay/` is the authoritative, reusable FlexPay
  integration package (config, backend examples, frontend snippets, routes, migrations, donation
  adaptation checklist).
- `docs_old/` is superseded/legacy (old deployment notes, SPA notes, YouTube sync notes, and an
  earlier version of the FlexPay docs) — do not treat as current; prefer `docs/`.
