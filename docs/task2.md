# SuaraLokal MVP 2 Task Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Harden the SuaraLokal product after MVP 1 so the app is more accurate, stable on HP, easier to operate, and safer against tampered flows.

**Architecture:** Keep the existing `Controller -> Usecase -> View` pattern. Push heavy logic into Usecase and job classes, keep controllers thin, and preserve the mobile-first PWA experience from MVP 1.

**Tech Stack:** Laravel 13, PHP 8.3, PostgreSQL, PostGIS, Query Builder, Blade, Tailwind CSS 4, Preline UI, Alpine.js, Bun/Vite, Pest, queue jobs, Firebase Cloud Messaging.

---

## File Map

- Modify: `db-migrator-with-drizzle/src/db/schema.ts`
- Modify: `db-migrator-with-drizzle/src/seeds/index.ts`
- Modify: `app/Usecase/DiscoveryUsecase.php`
- Modify: `app/Usecase/ConversationUsecase.php`
- Modify: `app/Usecase/OrderUsecase.php`
- Modify: `app/Http/Controllers/App/DiscoveryController.php`
- Modify: `app/Http/Controllers/App/ConversationController.php`
- Modify: `app/Http/Controllers/App/OrderController.php`
- Modify: `app/Http/Controllers/Admin/BangjekOrderController.php`
- Modify: `app/Http/Controllers/Driver/DriverOrderController.php`
- Modify: `resources/views/app/layout.blade.php`
- Modify: `resources/views/app/discovery/index.blade.php`
- Modify: `resources/views/app/conversations/show.blade.php`
- Modify: `resources/views/app/orders/_invoice-card.blade.php`
- Modify: `resources/css/app.css`
- Modify: `resources/js/app.js`
- Modify: `vite.config.js`
- Modify: `config/services.php`
- Modify: `routes/web.php`
- Create: `app/Jobs/SendPushNotificationJob.php`
- Create: `app/Usecase/NotificationUsecase.php`
- Create: `app/Usecase/SettlementUsecase.php`
- Create: `app/Usecase/OrderEventUsecase.php`
- Create: `app/Http/Controllers/Admin/SettlementController.php`
- Create: `resources/views/_admin/settlements/index.blade.php`
- Create: `resources/views/_admin/settlements/detail.blade.php`
- Test: `tests/Feature/SuaraLokalMvp2FlowTest.php`

---

## Task 1: Harden UMKM Discovery With PostGIS

> **Implementation note:** PostGIS is not installed on the Postgres server and could not be enabled, so this task uses the `cube` + `earthdistance` contrib extensions instead (user decision). Radius search uses `earth_distance(ll_to_earth(...), ...)` with a GiST expression index on `ll_to_earth(latitude, longitude)`; no geometry column or backfill is needed because coordinates are read directly from the existing `latitude`/`longitude` columns.

**Files:**
- Modify: `db-migrator-with-drizzle/src/db/schema.ts`
- Modify: `db-migrator-with-drizzle/src/seeds/suaraLokalSeeder.ts`
- Modify: `app/Usecase/DiscoveryUsecase.php`
- Create: `tests/Feature/SuaraLokalDiscoveryEarthdistanceTest.php`

- [x] Add a GiST expression index on `ll_to_earth(latitude, longitude)` for radius/nearest search (earthdistance replaces the PostGIS geometry column + GiST index).
- [x] N/A backfill: earthdistance derives directly from the existing `latitude`/`longitude` columns, no geometry column to populate.
- [x] Replace the Haversine-only discovery path with an earthdistance query path that uses `earth_distance` for the radius filter and the cube `<->` operator for the nearest sort.
- [x] Keep keyword and radius filtering stable when the UMKM dataset grows.
- [x] Add a test proving the earthdistance-backed discovery query still returns the nearest UMKM in the correct order.
- [x] Run `php artisan test --filter=SuaraLokalDiscoveryEarthdistanceTest`.
- [x] Commit with `feat: harden earthdistance discovery`.

## Task 2: Lock Down Chat And Order Ownership

**Files:**
- Modify: `app/Usecase/ConversationUsecase.php`
- Modify: `app/Usecase/OrderUsecase.php`
- Modify: `app/Http/Controllers/App/ConversationController.php`
- Modify: `app/Http/Controllers/App/OrderController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/SuaraLokalMvp2FlowTest.php`

- [x] Derive conversation participants from authenticated user context instead of trusting `peerId` or other request values.
- [x] Reject attempts to open or reuse a conversation when the peer does not match the logged-in user's allowed counterpart role.
- [x] Reject order creation if `conversation_id` does not belong to the authenticated UMKM and target pengguna pair.
- [x] Reject order confirmation if the order does not belong to the authenticated pengguna, even when a `peerId` is supplied.
- [x] Ensure driver-facing order detail actions still check ownership before status transitions.
- [x] Add regression tests for forged `peerId`, forged `conversation_id`, and forged `pengguna_id` values.
- [x] Run `php artisan test --filter=SuaraLokalMvp2FlowTest`.
- [x] Commit with `feat: lock down chat and order ownership`.

## Task 3: Add Push Notifications And Queue Dispatch

**Files:**
- Create: `app/Jobs/SendPushNotificationJob.php`
- Create: `app/Usecase/NotificationUsecase.php`
- Create: `app/Http/Controllers/App/SseController.php`
- Modify: `app/Http/Controllers/Admin/BangjekOrderController.php`
- Modify: `app/Http/Controllers/Driver/DriverOrderController.php`
- Modify: `app/Http/Controllers/App/OrderController.php`
- Modify: `routes/web.php`
- Modify: `config/services.php`
- Modify: `tests/Feature/SuaraLokalMvp2FlowTest.php`

- [x] Add a notification usecase that prepares payloads for driver assignment, order status changes, and delivery completion.
- [x] Dispatch push notification jobs when admin assigns a driver.
- [x] Dispatch push notification jobs when a driver marks an order picked up or completed.
- [x] Keep the actual FCM send operation in a queue job so request latency stays low.
- [x] Store the notification configuration in `config/services.php` so credentials stay out of controllers.
- [x] Create an SSE endpoint `/app/sse/orders` to stream real-time order status updates to active users.
- [x] Add tests that fake the queue and prove notification jobs are dispatched for assignment and completion flows.
- [x] Run `php artisan test --filter=SuaraLokalMvp2FlowTest`.
- [x] Commit with `feat: add queued push notifications and SSE real-time stream`.

## Task 4: Polish Mobile UX And PWA Stability

**Files:**
- Modify: `resources/views/app/layout.blade.php`
- Modify: `resources/views/app/discovery/index.blade.php`
- Modify: `resources/views/app/conversations/show.blade.php`
- Modify: `resources/views/app/orders/_invoice-card.blade.php`
- Modify: `resources/css/app.css`
- Modify: `resources/js/app.js`
- Modify: `vite.config.js`
- Modify: `public/manifest.webmanifest`

- [x] Improve loading, empty, and error states on the discovery, chat, and order screens.
- [x] Tighten the bottom navigation and safe-area handling so the app feels correct on narrow HP screens.
- [x] Make the main action buttons easier to reach and scan with one thumb.
- [x] Cache core assets more aggressively through the PWA setup so the app remains usable on weak connections.
- [x] Make the app layout resilient when the browser is small, tall, or has mobile browser chrome visible.
- [x] Run `bun run build`.
- [x] Verify the app in Chrome DevTools mobile viewport and on the Android Emulator.
- [x] Commit with `feat: polish mobile pwa ux`.

## Task 5: Add Settlement And Operational Tracking

**Files:**
- Modify: `db-migrator-with-drizzle/src/db/schema.ts`
- Modify: `db-migrator-with-drizzle/src/seeds/index.ts`
- Create: `app/Usecase/SettlementUsecase.php`
- Create: `app/Http/Controllers/Admin/SettlementController.php`
- Create: `resources/views/_admin/settlements/index.blade.php`
- Create: `resources/views/_admin/settlements/detail.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/SuaraLokalMvp2FlowTest.php`

- [x] Add a settlement table or equivalent structure to track COD Talangan reimbursement and settlement state.
- [x] Add settlement states that make operational handoff clear, such as waiting for settlement and settled.
- [x] Add an admin settlement dashboard that can filter, inspect, and close out pending items.
- [x] Store order settlement history so the admin can trace what happened without reading raw logs.
- [x] Keep the settlement logic separate from normal order status transitions.
- [x] Add tests proving settlement rows are created and state transitions stay consistent.
- [x] Run `php artisan test --filter=SuaraLokalMvp2FlowTest`.
- [x] Commit with `feat: add settlement tracking`.

## Task 6: Add Audit And Regression Coverage

**Files:**
- Create: `app/Usecase/OrderEventUsecase.php`
- Modify: `app/Usecase/OrderUsecase.php`
- Modify: `app/Usecase/ConversationUsecase.php`
- Modify: `tests/Feature/SuaraLokalMvp2FlowTest.php`

- [x] Add an order event or audit trail structure for important transitions such as create, confirm, assign, pickup, complete, and settlement.
- [x] Record enough context to make failures diagnosable without reading raw database rows.
- [x] Add tests that cover the full hardening path: discovery, chat ownership, order ownership, notification dispatch, and settlement.
- [x] Add regression tests for invalid payloads and invalid role access that could break the MVP 2 flow later.
- [x] Run `php artisan test --filter=SuaraLokalMvp2FlowTest`.
- [x] Run `composer test`.
- [x] Run `bun run build`.
- [x] Manually verify the mobile flow in browser device mode.
- [x] Manually verify the app in Android Emulator.
- [x] Commit with `test: cover suaralokal mvp2 flow`.

## Done Criteria

- [x] PostGIS discovery is the default path for radius search.
- [x] Conversation and order ownership cannot be bypassed with tampered request values.
- [x] Push notifications are dispatched through queue jobs.
- [x] Mobile UX holds up on small HP screens and weak connections.
- [x] Settlement state is visible and auditable.
- [x] `composer test` passes.
- [x] `bun run build` passes.

## Items To Change / Finish

- [x] Ganti test harness MVP 2 dari SQLite in-memory ke PostgreSQL, atau buat integration suite yang benar-benar jalan di PostgreSQL target.
- [x] Samakan implementasi discovery dengan spesifikasi MVP 2: kalau PostGIS yang diinginkan, ganti `earthdistance` ke PostGIS geometry; kalau `earthdistance` tetap dipakai, update `mvp2.md` agar tidak kontradiktif.
- [x] Tambahkan test yang membuktikan discovery, settlement, dan event log tetap valid di database target, bukan hanya di SQLite.
- [x] Pastikan flow yang menyentuh `settlements` dan `order_events` tidak bergantung pada DDL SQLite seperti `AUTOINCREMENT`.
- [x] Verifikasi ulang `php artisan test` dan `bun run build` setelah perubahan database target selesai.
