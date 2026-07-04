<?php

use App\Constants\DatabaseConst;
use App\Constants\UserConst;
use App\Models\User;
use App\Usecase\ConversationUsecase;
use App\Usecase\OrderUsecase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    DB::statement('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT, email_verified_at TEXT, password TEXT, remember_token TEXT, role TEXT, phone TEXT, deleted_at TEXT, access_type INTEGER, created_at TEXT, updated_at TEXT)');
    DB::statement('CREATE TABLE IF NOT EXISTS conversations (id INTEGER PRIMARY KEY AUTOINCREMENT, pengguna_id INTEGER, umkm_id INTEGER, created_at TEXT, updated_at TEXT)');
    DB::statement('CREATE TABLE IF NOT EXISTS orders (id INTEGER PRIMARY KEY AUTOINCREMENT, pengguna_id INTEGER, umkm_id INTEGER, conversation_id INTEGER, total_items_price REAL, shipping_fee REAL, payment_method TEXT, order_status TEXT, driver_id INTEGER, proof_of_delivery_url TEXT, created_at TEXT, updated_at TEXT)');
    DB::statement('CREATE TABLE IF NOT EXISTS settlements (id INTEGER PRIMARY KEY AUTOINCREMENT, order_id INTEGER, amount REAL, status TEXT, settled_at TEXT, created_at TEXT, updated_at TEXT)');
    DB::statement('CREATE TABLE IF NOT EXISTS umkm_profiles (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, store_name TEXT, description TEXT, address TEXT, phone TEXT, category TEXT, is_open INTEGER, latitude REAL, longitude REAL)');
    DB::statement('CREATE TABLE IF NOT EXISTS order_events (id INTEGER PRIMARY KEY AUTOINCREMENT, order_id INTEGER, event_type TEXT, payload TEXT, created_at TEXT)');
    DB::statement('CREATE TABLE IF NOT EXISTS order_items (id INTEGER PRIMARY KEY AUTOINCREMENT, order_id INTEGER, item_name TEXT, quantity INTEGER, price REAL, created_at TEXT, updated_at TEXT)');
});

/*
|--------------------------------------------------------------------------
| Task 2: Lock Down Chat And Order Ownership
|--------------------------------------------------------------------------
*/

test('forged peerId gets rejected in conversation show', function () {
    $pengguna = User::factory()->create(['id' => 1, 'role' => UserConst::ROLE_PENGGUNA]);
    
    // Forged peer user does not exist
    $this->actingAs($pengguna)
        ->get(route('app.conversations.show', 999))
        ->assertStatus(404);

    // Forged peer user exists but is not an UMKM (e.g. driver)
    $driver = User::factory()->create(['id' => 5, 'role' => UserConst::ROLE_DRIVER]);
    $this->actingAs($pengguna)
        ->get(route('app.conversations.show', 5))
        ->assertStatus(403);
});

test('order creation is rejected if conversation does not belong to authenticated UMKM', function () {
    $umkm1 = User::factory()->create(['id' => 2, 'role' => UserConst::ROLE_UMKM]);
    $umkm2 = User::factory()->create(['id' => 3, 'role' => UserConst::ROLE_UMKM]);
    $pengguna = User::factory()->create(['id' => 1, 'role' => UserConst::ROLE_PENGGUNA]);

    // Conversation belongs to umkm2, not umkm1
    DB::table(DatabaseConst::CONVERSATION())->insert([
        'id' => 10,
        'pengguna_id' => 1,
        'umkm_id' => 3, 
    ]);

    $this->actingAs($umkm1)
        ->post(route('app.orders.store', 10), [
            'item_name' => ['Item'],
            'item_quantity' => [1],
            'item_price' => [1000],
            'shipping_fee' => 1000,
            'payment_method' => 'cod_talangan',
        ])
        ->assertStatus(403);
});

test('order confirmation is rejected if the order does not belong to the authenticated pengguna', function () {
    $pengguna1 = User::factory()->create(['id' => 1, 'role' => UserConst::ROLE_PENGGUNA]);
    $pengguna2 = User::factory()->create(['id' => 6, 'role' => UserConst::ROLE_PENGGUNA]);
    User::factory()->create(['id' => 2, 'role' => UserConst::ROLE_UMKM]);

    // Order belongs to pengguna2
    DB::table(DatabaseConst::ORDER())->insert([
        'id' => 100,
        'pengguna_id' => 6,
        'umkm_id' => 2,
        'conversation_id' => 10,
        'total_items_price' => 30000,
        'shipping_fee' => 5000,
        'payment_method' => 'cod_talangan',
        'order_status' => 'tunggu_konfirm',
    ]);

    // pengguna1 attempts to confirm pengguna2's order
    $this->actingAs($pengguna1)
        ->post(route('app.orders.confirm', 100), [])
        ->assertStatus(403);
});

test('driver actions check driver ownership before status transitions', function () {
    $driver1 = User::factory()->create(['id' => 5, 'role' => UserConst::ROLE_DRIVER]);
    $driver2 = User::factory()->create(['id' => 7, 'role' => UserConst::ROLE_DRIVER]);
    User::factory()->create(['id' => 1, 'role' => UserConst::ROLE_PENGGUNA]);
    User::factory()->create(['id' => 2, 'role' => UserConst::ROLE_UMKM]);

    // Order assigned to driver1
    DB::table(DatabaseConst::ORDER())->insert([
        'id' => 100,
        'pengguna_id' => 1,
        'umkm_id' => 2,
        'driver_id' => 5,
        'order_status' => 'dijemput',
        'total_items_price' => 30000,
        'shipping_fee' => 5000,
        'payment_method' => 'cod_talangan',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // driver2 attempts to mark driver1's order as picked up
    $this->actingAs($driver2)
        ->post(route('driver.orders.pickup', 100))
        ->assertStatus(302)
        ->assertSessionHas('error', 'Anda tidak ditugaskan untuk pesanan ini.');

    // driver2 attempts to complete driver1's order
    $fakeImage = \Illuminate\Http\UploadedFile::fake()->image('proof.jpg');
    $this->actingAs($driver2)
        ->post(route('driver.orders.complete', 100), ['proof' => $fakeImage])
        ->assertStatus(302)
        ->assertSessionHas('error', 'Anda tidak ditugaskan untuk pesanan ini.');
});

/*
|--------------------------------------------------------------------------
| Task 3: Add Push Notifications And Queue Dispatch + SSE
|--------------------------------------------------------------------------
*/

test('assigning driver dispatches SendPushNotificationJob and stores SSE event', function () {
    \Illuminate\Support\Facades\Queue::fake();

    $ojekAdmin = User::factory()->create(['id' => 3, 'role' => UserConst::ROLE_OJEK_ADMIN]);
    User::factory()->create(['id' => 5, 'role' => UserConst::ROLE_DRIVER]);
    User::factory()->create(['id' => 1, 'role' => UserConst::ROLE_PENGGUNA]);
    User::factory()->create(['id' => 2, 'role' => UserConst::ROLE_UMKM]);

    DB::table(DatabaseConst::ORDER())->insert([
        'id' => 100,
        'pengguna_id' => 1,
        'umkm_id' => 2,
        'order_status' => 'cari_driver',
        'total_items_price' => 30000,
        'shipping_fee' => 5000,
        'payment_method' => 'cod_talangan',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Mock OrderUsecase for admin assignment to pass
    $this->mock(OrderUsecase::class, function ($mock) {
        $mock->shouldReceive('assignDriver')
            ->once()
            ->with(100, 5)
            ->andReturnUsing(function ($id, $driverId) {
                DB::table(DatabaseConst::ORDER())->where('id', $id)->update(['driver_id' => $driverId]);
                return [
                    'success' => true,
                    'message' => 'Driver berhasil ditugaskan.',
                ];
            });
    });

    $this->actingAs($ojekAdmin)
        ->post(route('admin.bangjek_orders.assign', 100), ['driver_id' => 5])
        ->assertRedirect(route('admin.bangjek_orders.index'))
        ->assertSessionHas('success');

    // Assert push notification job was dispatched
    \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SendPushNotificationJob::class);

    // Assert SSE update cache key was populated
    expect(\Illuminate\Support\Facades\Cache::has('sse_order_updates_1'))->toBeTrue()
        ->and(\Illuminate\Support\Facades\Cache::has('sse_order_updates_2'))->toBeTrue()
        ->and(\Illuminate\Support\Facades\Cache::has('sse_order_updates_5'))->toBeTrue();
});

test('driver actions dispatch SendPushNotificationJob and SSE events', function () {
    \Illuminate\Support\Facades\Queue::fake();

    $driver = User::factory()->create(['id' => 5, 'role' => UserConst::ROLE_DRIVER]);
    User::factory()->create(['id' => 1, 'role' => UserConst::ROLE_PENGGUNA]);
    User::factory()->create(['id' => 2, 'role' => UserConst::ROLE_UMKM]);

    DB::table(DatabaseConst::ORDER())->insert([
        'id' => 100,
        'pengguna_id' => 1,
        'umkm_id' => 2,
        'driver_id' => 5,
        'order_status' => 'dijemput',
        'total_items_price' => 30000,
        'shipping_fee' => 5000,
        'payment_method' => 'cod_talangan',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->mock(OrderUsecase::class, function ($mock) {
        $mock->shouldReceive('markPickedUp')
            ->once()
            ->with(100, 5)
            ->andReturn([
                'success' => true,
                'message' => 'Status pesanan diubah ke diantar.',
            ]);
    });

    $this->actingAs($driver)
        ->post(route('driver.orders.pickup', 100))
        ->assertRedirect(route('driver.orders.detail', 100))
        ->assertSessionHas('success');

    // Assert push notification job was dispatched
    \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SendPushNotificationJob::class);
});

test('SSE route requires authentication and streams events', function () {
    $pengguna = User::factory()->create(['id' => 1, 'role' => UserConst::ROLE_PENGGUNA]);

    // Guest gets redirected to login
    $this->get(route('app.sse.orders'))
        ->assertRedirect(route('login'));

    // Authenticated user can connect
    $this->actingAs($pengguna);

    // Let's populate the SSE cache with a mock event
    \Illuminate\Support\Facades\Cache::put('sse_order_updates_1', [
        [
            'order_id' => 100,
            'status' => 'dijemput',
            'updated_at' => '2026-07-04 12:00:00'
        ]
    ]);

    // Request the stream.
    $response = $this->get(route('app.sse.orders'))
        ->assertStatus(200);

    // Execute StreamedResponse callback to trigger Cache::pull
    ob_start();
    $response->sendContent();
    ob_end_clean();

    // Verify cache was cleared (pulled) after the request
    expect(\Illuminate\Support\Facades\Cache::has('sse_order_updates_1'))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Task 5: Add Settlement And Operational Tracking
|--------------------------------------------------------------------------
*/

test('completing COD order creates settlement record', function () {
    $driver = User::factory()->create(['id' => 5, 'role' => UserConst::ROLE_DRIVER]);
    User::factory()->create(['id' => 1, 'role' => UserConst::ROLE_PENGGUNA]);
    User::factory()->create(['id' => 2, 'role' => UserConst::ROLE_UMKM]);

    DB::table(DatabaseConst::UMKM_PROFILE())->insert([
        'user_id' => 2,
        'store_name' => 'Toko Sederhana',
        'latitude' => -8.10,
        'longitude' => 114.10,
        'is_open' => 1,
    ]);

    DB::table(DatabaseConst::ORDER())->insert([
        'id' => 100,
        'pengguna_id' => 1,
        'umkm_id' => 2,
        'driver_id' => 5,
        'order_status' => 'diantar',
        'total_items_price' => 30000,
        'shipping_fee' => 5000,
        'payment_method' => 'cod_talangan',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $fakeImage = \Illuminate\Http\UploadedFile::fake()->image('proof.jpg');

    $this->actingAs($driver)
        ->post(route('driver.orders.complete', 100), ['proof' => $fakeImage])
        ->assertRedirect(route('driver.orders.detail', 100))
        ->assertSessionHas('success');

    // Assert settlement was created with status menunggu_reimburse and correct amount (30000 + 5000 = 35000)
    $settlement = DB::table('settlements')->where('order_id', 100)->first();
    expect($settlement)->not->toBeNull()
        ->and((float) $settlement->amount)->toBe(35000.0)
        ->and($settlement->status)->toBe('menunggu_reimburse');
});

test('unauthorized roles cannot access settlements', function () {
    $pengguna = User::factory()->create(['id' => 1, 'role' => UserConst::ROLE_PENGGUNA]);

    $this->actingAs($pengguna)
        ->get(route('admin.settlements.index'))
        ->assertStatus(403);
});

test('admin can view and close out settlement', function () {
    $ojekAdmin = User::factory()->create(['id' => 3, 'role' => UserConst::ROLE_OJEK_ADMIN]);
    $driver = User::factory()->create(['id' => 5, 'role' => UserConst::ROLE_DRIVER]);
    User::factory()->create(['id' => 1, 'role' => UserConst::ROLE_PENGGUNA]);
    User::factory()->create(['id' => 2, 'role' => UserConst::ROLE_UMKM]);

    DB::table(DatabaseConst::UMKM_PROFILE())->insert([
        'user_id' => 2,
        'store_name' => 'Toko Sederhana',
        'latitude' => -8.10,
        'longitude' => 114.10,
        'is_open' => 1,
    ]);

    DB::table(DatabaseConst::ORDER())->insert([
        'id' => 100,
        'pengguna_id' => 1,
        'umkm_id' => 2,
        'driver_id' => 5,
        'order_status' => 'selesai',
        'total_items_price' => 30000,
        'shipping_fee' => 5000,
        'payment_method' => 'cod_talangan',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('settlements')->insert([
        'id' => 1,
        'order_id' => 100,
        'amount' => 35000,
        'status' => 'menunggu_reimburse',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($ojekAdmin);

    // Can access index
    $this->get(route('admin.settlements.index'))
        ->assertStatus(200);

    // Can access detail
    $this->get(route('admin.settlements.detail', 1))
        ->assertStatus(200);

    // Can settle
    $this->post(route('admin.settlements.settle', 1))
        ->assertRedirect(route('admin.settlements.index'))
        ->assertSessionHas('success');

    // Assert status updated in DB
    $settlement = DB::table('settlements')->where('id', 1)->first();
    expect($settlement->status)->toBe('selesai_reimburse')
        ->and($settlement->settled_at)->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Task 6: Add Audit And Regression Coverage
|--------------------------------------------------------------------------
*/

test('order lifecycle transition events are logged correctly', function () {
    $pengguna = User::factory()->create(['id' => 1, 'role' => UserConst::ROLE_PENGGUNA]);
    $umkm = User::factory()->create(['id' => 2, 'role' => UserConst::ROLE_UMKM]);
    $ojekAdmin = User::factory()->create(['id' => 3, 'role' => UserConst::ROLE_OJEK_ADMIN]);
    $driver = User::factory()->create(['id' => 5, 'role' => UserConst::ROLE_DRIVER]);

    DB::table(DatabaseConst::UMKM_PROFILE())->insert([
        'user_id' => 2,
        'store_name' => 'Toko Sederhana',
        'latitude' => -8.10,
        'longitude' => 114.10,
        'is_open' => 1,
    ]);

    // 1. Create order
    $orderData = [
        'pengguna_id' => 1,
        'peer_id' => 2,
        'item_name' => ['Sate Ayam'],
        'item_quantity' => [2],
        'item_price' => [15000],
        'shipping_fee' => 5000,
        'payment_method' => 'cod_talangan',
    ];

    DB::table(DatabaseConst::CONVERSATION())->insert([
        'id' => 12,
        'pengguna_id' => 1,
        'umkm_id' => 2,
    ]);

    $this->actingAs($umkm)
        ->post(route('app.orders.store', 12), $orderData)
        ->assertRedirect(route('app.conversations.show', 1))
        ->assertSessionHas('success');

    $orderId = DB::table(DatabaseConst::ORDER())->first()->id;

    $createEvent = DB::table('order_events')
        ->where('order_id', $orderId)
        ->where('event_type', 'create')
        ->first();
    expect($createEvent)->not->toBeNull();

    // 2. Confirm order
    $this->actingAs($pengguna)
        ->post(route('app.orders.confirm', $orderId))
        ->assertRedirect(route('app.conversations.show', 2))
        ->assertSessionHas('success');

    $confirmEvent = DB::table('order_events')
        ->where('order_id', $orderId)
        ->where('event_type', 'confirm')
        ->first();
    expect($confirmEvent)->not->toBeNull();

    // 3. Assign driver
    $this->actingAs($ojekAdmin)
        ->post(route('admin.bangjek_orders.assign', $orderId), ['driver_id' => 5])
        ->assertRedirect(route('admin.bangjek_orders.index'));

    $assignEvent = DB::table('order_events')
        ->where('order_id', $orderId)
        ->where('event_type', 'assign')
        ->first();
    expect($assignEvent)->not->toBeNull();

    // 4. Pickup order
    $this->actingAs($driver)
        ->post(route('driver.orders.pickup', $orderId))
        ->assertRedirect(route('driver.orders.detail', $orderId));

    $pickupEvent = DB::table('order_events')
        ->where('order_id', $orderId)
        ->where('event_type', 'pickup')
        ->first();
    expect($pickupEvent)->not->toBeNull();

    // 5. Complete order
    $fakeImage = \Illuminate\Http\UploadedFile::fake()->image('proof.jpg');
    $this->actingAs($driver)
        ->post(route('driver.orders.complete', $orderId), ['proof' => $fakeImage])
        ->assertRedirect(route('driver.orders.detail', $orderId));

    $completeEvent = DB::table('order_events')
        ->where('order_id', $orderId)
        ->where('event_type', 'complete')
        ->first();
    expect($completeEvent)->not->toBeNull();

    // 6. Settlement
    $settlementId = DB::table('settlements')->where('order_id', $orderId)->first()->id;
    $this->actingAs($ojekAdmin)
        ->post(route('admin.settlements.settle', $settlementId))
        ->assertRedirect(route('admin.settlements.index'));

    $settlementEvent = DB::table('order_events')
        ->where('order_id', $orderId)
        ->where('event_type', 'settlement')
        ->first();
    expect($settlementEvent)->not->toBeNull();
});


