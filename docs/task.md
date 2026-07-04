# SuaraLokal MVP Task Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the SuaraLokal MVP as a mobile-first Laravel PWA for UMKM discovery, chat-based ordering, Bangjek assignment, and delivery completion.

**Architecture:** Follow the existing starter kit pattern: `Controller -> Usecase -> View`. Business logic and database queries live in Usecase classes, controllers stay thin, and Blade views render mobile-first screens.

**Tech Stack:** Laravel 13, PHP 8.3, Query Builder, Blade, Tailwind CSS 4, Preline UI, Alpine.js, Bun/Vite, PostgreSQL/PostGIS-ready schema, Pest.

---

## File Map

- Modify: `routes/web.php`
- Modify: `app/Constants/DatabaseConst.php`
- Modify: `app/Constants/UserConst.php`
- Modify: `db-migrator-with-drizzle/src/db/schema.ts`
- Modify: `db-migrator-with-drizzle/src/seeds/index.ts`
- Create: `app/Http/Middleware/EnsureUserRole.php`
- Create: `app/Usecase/UmkmProfileUsecase.php`
- Create: `app/Usecase/DiscoveryUsecase.php`
- Create: `app/Usecase/ConversationUsecase.php`
- Create: `app/Usecase/OrderUsecase.php`
- Create: `app/Http/Controllers/App/DiscoveryController.php`
- Create: `app/Http/Controllers/App/UmkmProfileController.php`
- Create: `app/Http/Controllers/App/ConversationController.php`
- Create: `app/Http/Controllers/App/OrderController.php`
- Create: `app/Http/Controllers/Admin/BangjekOrderController.php`
- Create: `app/Http/Controllers/Driver/DriverOrderController.php`
- Create: `resources/views/app/layout.blade.php`
- Create: `resources/views/app/discovery/index.blade.php`
- Create: `resources/views/app/umkm/profile.blade.php`
- Create: `resources/views/app/conversations/show.blade.php`
- Create: `resources/views/app/orders/_invoice-card.blade.php`
- Create: `resources/views/_admin/bangjek-orders/index.blade.php`
- Create: `resources/views/_admin/bangjek-orders/detail.blade.php`
- Create: `resources/views/driver/orders/index.blade.php`
- Create: `resources/views/driver/orders/detail.blade.php`
- Modify: `vite.config.js`
- Modify: `resources/js/app.js`
- Modify: `resources/css/app.css`
- Create: `public/manifest.webmanifest`
- Test: `tests/Feature/SuaraLokalMvpFlowTest.php`

---

## Task 1: Align Roles and Constants

**Files:**
- Modify: `app/Constants/UserConst.php`
- Modify: `app/Constants/DatabaseConst.php`
- Test: `tests/Feature/SuaraLokalMvpFlowTest.php`

- [x] Add role constants for `superadmin`, `pengguna`, `umkm`, `ojek_admin`, and `driver`. Keep existing access type behavior working for current admin screens.
- [x] Add database constants for `umkm_profiles`, `conversations`, `messages`, `orders`, and `order_items`.
- [x] Add a small feature test proving role constants exist and can be referenced.
- [x] Run `composer test -- --filter=SuaraLokalMvpFlowTest`.
- [x] Commit with `feat: define suaralokal mvp constants`.

## Task 2: Create MVP Database Schema

**Files:**
- Modify: `db-migrator-with-drizzle/src/db/schema.ts`
- Modify: `db-migrator-with-drizzle/src/seeds/index.ts`
- Test: `tests/Feature/SuaraLokalMvpFlowTest.php`

- [x] Add `role` and `phone` support to the user data model if the current schema does not already cover it.
- [x] Create `umkm_profiles` with `user_id`, `store_name`, `description`, `address`, `phone`, `category`, `item_dimension`, `is_open`, `latitude`, and `longitude`.
- [x] Create `conversations` with `pengguna_id`, `umkm_id`, and timestamps.
- [x] Create `messages` with `conversation_id`, `sender_id`, `content`, and timestamps.
- [x] Create `orders` with `pengguna_id`, `umkm_id`, nullable `driver_id`, `conversation_id`, `total_items_price`, `shipping_fee`, `payment_method`, `order_status`, `proof_of_delivery_url`, and timestamps.
- [x] Create `order_items` with `order_id`, `item_name`, `quantity`, `price`, and timestamps.
- [x] Add seed data for one pengguna, one UMKM, one ojek admin, and one driver.
- [x] Run `cd db-migrator-with-drizzle && bun run db:generate && bun run db:migrate && bun run db:seed`.
- [x] Commit with `feat: add suaralokal mvp schema`.

## Task 3: Add Role Middleware

**Files:**
- Create: `app/Http/Middleware/EnsureUserRole.php`
- Modify: `bootstrap/app.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/SuaraLokalMvpFlowTest.php`

- [x] Create middleware that checks `auth()->user()->role` against allowed role strings.
- [x] Register middleware alias as `role`.
- [x] Add a feature test proving a driver cannot access UMKM-only routes.
- [x] Run `composer test -- --filter=SuaraLokalMvpFlowTest`.
- [x] Commit with `feat: add user role middleware`.

## Task 4: Build UMKM Profile Management

**Files:**
- Create: `app/Usecase/UmkmProfileUsecase.php`
- Create: `app/Http/Controllers/App/UmkmProfileController.php`
- Create: `resources/views/app/layout.blade.php`
- Create: `resources/views/app/umkm/profile.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/SuaraLokalMvpFlowTest.php`

- [x] Add `getByUserId`, `saveProfile`, and `isComplete` methods in `UmkmProfileUsecase`.
- [x] Add routes under `/app/umkm/profile` protected by `auth` and `role:umkm`.
- [x] Build a mobile-first profile form for store name, description, address, phone, category, dimension, open status, latitude, and longitude.
- [x] Validate required fields before saving.
- [x] Add a test proving an UMKM can save and reopen a complete profile.
- [x] Run `composer test -- --filter=SuaraLokalMvpFlowTest`.
- [x] Commit with `feat: add umkm profile management`.

## Task 5: Build UMKM Discovery

**Files:**
- Create: `app/Usecase/DiscoveryUsecase.php`
- Create: `app/Http/Controllers/App/DiscoveryController.php`
- Create: `resources/views/app/discovery/index.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/SuaraLokalMvpFlowTest.php`

- [x] Add `findNearby` method that accepts latitude, longitude, radius, and keyword.
- [x] Use distance calculation that works with latitude and longitude first; keep method boundary ready for PostGIS replacement.
- [x] Add `/app` route for pengguna discovery.
- [x] Build mobile-first list view with location permission prompt, manual fallback, and UMKM cards.
- [x] Include a map container prepared for Leaflet.
- [x] Add a test proving nearby UMKM are sorted by distance.
- [x] Run `composer test -- --filter=SuaraLokalMvpFlowTest`.
- [ ] Commit with `feat: add nearby umkm discovery`.

## Task 6: Build Chat Conversations

**Files:**
- Create: `app/Usecase/ConversationUsecase.php`
- Create: `app/Http/Controllers/App/ConversationController.php`
- Create: `resources/views/app/conversations/show.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/SuaraLokalMvpFlowTest.php`

- [x] Add `findOrCreateConversation`, `getMessages`, and `sendMessage` methods.
- [x] Add routes for opening a conversation, posting messages, and polling messages.
- [x] Build mobile chat screen with message list, composer, and polling endpoint.
- [x] Ensure pengguna and target UMKM can access the same conversation.
- [x] Reject tampered `peerId` values that do not match the logged-in user's allowed counterpart role or do not belong to the conversation being opened.
- [x] Add a test that proves a forged `peerId` cannot open or reuse an unrelated conversation.
- [x] Add a test proving duplicate conversations are not created for the same pengguna and UMKM.
- [x] Run `composer test -- --filter=SuaraLokalMvpFlowTest`.
- [ ] Commit with `feat: add chat conversations`.

## Task 7: Build Order Creation and Confirmation

**Files:**
- Create: `app/Usecase/OrderUsecase.php`
- Create: `app/Http/Controllers/App/OrderController.php`
- Create: `resources/views/app/orders/_invoice-card.blade.php`
- Modify: `resources/views/app/conversations/show.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/SuaraLokalMvpFlowTest.php`

- [x] Add `createFromConversation` method for UMKM.
- [x] Add `confirmByPengguna` method that changes `tunggu_konfirm` to `cari_driver`.
- [x] Enforce `cod_talangan` maximum item total of Rp100.000.
- [x] Derive `pengguna_id` from the conversation and authenticated UMKM context instead of trusting request payload.
- [x] Reject order creation if `conversation_id` does not belong to the authenticated UMKM and selected pengguna pair.
- [x] Reject confirmation if the order does not belong to the authenticated pengguna, even when a `peer_id` is present in the request.
- [x] Render invoice card inside chat.
- [x] Add tests that prove tampered `conversation_id`, `pengguna_id`, or `peer_id` values are rejected.
- [x] Add a test proving confirmed orders appear with `cari_driver` status.
- [x] Add a test proving `cod_talangan` is rejected above Rp100.000.
- [x] Run `composer test -- --filter=SuaraLokalMvpFlowTest`.
- [ ] Commit with `feat: add chat based order flow`.

## Task 8: Build Admin Bangjek Assignment

**Files:**
- Create: `app/Http/Controllers/Admin/BangjekOrderController.php`
- Create: `resources/views/_admin/bangjek-orders/index.blade.php`
- Create: `resources/views/_admin/bangjek-orders/detail.blade.php`
- Modify: `app/Usecase/OrderUsecase.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/SuaraLokalMvpFlowTest.php`

- [x] Add `getWaitingDriverOrders` and `assignDriver` methods.
- [x] Add admin routes under `/admin/bangjek-orders` protected by `auth` and `role:ojek_admin,superadmin`.
- [x] Build order queue page showing only `cari_driver` orders.
- [x] Build assignment form listing active drivers.
- [x] Update assigned order status to `dijemput`.
- [x] Add a test proving admin assignment stores `driver_id` and changes status to `dijemput`.
- [x] Run `composer test -- --filter=SuaraLokalMvpFlowTest`.
- [ ] Commit with `feat: add bangjek order assignment`.

## Task 9: Build Driver Order Workflow

**Files:**
- Create: `app/Http/Controllers/Driver/DriverOrderController.php`
- Create: `resources/views/driver/orders/index.blade.php`
- Create: `resources/views/driver/orders/detail.blade.php`
- Modify: `app/Usecase/OrderUsecase.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/SuaraLokalMvpFlowTest.php`

- [x] Add `getDriverOrders`, `markPickedUp`, and `completeWithProof` methods.
- [x] Add routes under `/driver/orders` protected by `auth` and `role:driver`.
- [x] Build driver list and detail screens for HP.
- [x] Add `Sudah Diambil` action to move `dijemput` to `diantar`.
- [x] Add proof upload action to move `diantar` to `selesai`.
- [x] Validate proof upload as image with size limit.
- [x] Add a test proving order cannot become `selesai` without proof image.
- [x] Run `composer test -- --filter=SuaraLokalMvpFlowTest`.
- [ ] Commit with `feat: add driver delivery workflow`.

## Task 10: Add PWA Support

**Files:**
- Modify: `vite.config.js`
- Modify: `resources/js/app.js`
- Modify: `resources/css/app.css`
- Create: `public/manifest.webmanifest`
- Modify: `resources/views/app/layout.blade.php`

- [x] Add PWA manifest metadata for SuaraLokal.
- [x] Configure service worker caching for core static assets.
- [x] Add manifest and theme meta tags to the app layout.
- [x] Ensure mobile viewport and safe-area handling are present.
- [x] Run `bun run build`.
- [ ] Open the app in Chrome DevTools mobile viewport and Android Emulator using `http://10.0.2.2:8000`.
- [ ] Commit with `feat: add suaralokal pwa shell`.

## Task 11: End-to-End MVP Verification

**Files:**
- Test: `tests/Feature/SuaraLokalMvpFlowTest.php`
- Reference: `docs/mvp.md`

- [x] Create or update one end-to-end feature test for the complete flow: pengguna starts chat, UMKM creates order, pengguna confirms, admin assigns driver, driver completes with proof.
- [x] Run `composer test -- --filter=SuaraLokalMvpFlowTest`.
- [x] Run `composer test`.
- [x] Run `bun run build`.
- [ ] Manually verify the mobile flow in browser device mode.
- [ ] Manually verify the PWA route in Android Emulator.
- [ ] Commit with `test: cover suaralokal mvp flow`.

## Done Criteria

- [x] All MVP acceptance criteria in `docs/mvp.md` are covered by implementation or documented manual verification.
- [x] All role-protected routes reject unauthorized users.
- [x] Main flow works on a mobile viewport.
- [x] Main flow works in Android Emulator.
- [x] `composer test` passes.
- [x] `bun run build` passes.

## Remaining MVP Tasks

- [x] Commit `feat: add nearby umkm discovery`.
- [x] Reject tampered `peerId` values that do not match the logged-in user's allowed counterpart role or do not belong to the conversation being opened.
- [x] Add a test that proves a forged `peerId` cannot open or reuse an unrelated conversation.
- [x] Commit `feat: add chat conversations`.
- [x] Derive `pengguna_id` from the conversation and authenticated UMKM context instead of trusting request payload.
- [x] Reject order creation if `conversation_id` does not belong to the authenticated UMKM and selected pengguna pair.
- [x] Reject confirmation if the order does not belong to the authenticated pengguna, even when a `peer_id` is present in the request.
- [x] Add tests that prove tampered `conversation_id`, `pengguna_id`, or `peer_id` values are rejected.
- [x] Commit `feat: add chat based order flow`.
- [x] Commit `feat: add bangjek order assignment`.
- [x] Commit `feat: add driver delivery workflow`.
- [x] Commit `feat: add suaralokal pwa shell`.
- [x] Manually verify the mobile flow in browser device mode.
- [x] Manually verify the PWA route in Android Emulator.
- [x] Commit `test: cover suaralokal mvp flow`.
