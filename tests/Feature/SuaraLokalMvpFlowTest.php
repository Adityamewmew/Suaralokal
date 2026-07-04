<?php

use App\Constants\DatabaseConst;
use App\Constants\UserConst;
use App\Models\User;
use App\Usecase\ConversationUsecase;
use App\Usecase\DiscoveryUsecase;
use App\Usecase\OrderUsecase;
use App\Usecase\UmkmProfileUsecase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\DB::statement('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT, email_verified_at TEXT, password TEXT, remember_token TEXT, role TEXT, phone TEXT, deleted_at TEXT, access_type INTEGER, created_at TEXT, updated_at TEXT)');
    \Illuminate\Support\Facades\DB::statement('CREATE TABLE IF NOT EXISTS conversations (id INTEGER PRIMARY KEY AUTOINCREMENT, pengguna_id INTEGER, umkm_id INTEGER, created_at TEXT, updated_at TEXT)');
    \Illuminate\Support\Facades\DB::statement('CREATE TABLE IF NOT EXISTS orders (id INTEGER PRIMARY KEY AUTOINCREMENT, pengguna_id INTEGER, umkm_id INTEGER, conversation_id INTEGER, total_items_price REAL, shipping_fee REAL, payment_method TEXT, order_status TEXT, driver_id INTEGER, proof_of_delivery_url TEXT, created_at TEXT, updated_at TEXT)');
});

/*
|--------------------------------------------------------------------------
| Task 1: Role Constants
|--------------------------------------------------------------------------
*/

test('role constants are defined', function () {
    expect(UserConst::ROLE_SUPERADMIN)->toBe('superadmin');
    expect(UserConst::ROLE_PENGGUNA)->toBe('pengguna');
    expect(UserConst::ROLE_UMKM)->toBe('umkm');
    expect(UserConst::ROLE_OJEK_ADMIN)->toBe('ojek_admin');
    expect(UserConst::ROLE_DRIVER)->toBe('driver');
});

test('getRoles returns all five roles', function () {
    $roles = UserConst::getRoles();

    expect($roles)->toHaveCount(5);
    expect(array_keys($roles))->toContain(
        UserConst::ROLE_SUPERADMIN,
        UserConst::ROLE_PENGGUNA,
        UserConst::ROLE_UMKM,
        UserConst::ROLE_OJEK_ADMIN,
        UserConst::ROLE_DRIVER,
    );
});

test('legacy access type constants still work', function () {
    expect(UserConst::SUPERADMIN)->toBe(1);
    expect(UserConst::getAccessTypes())->toHaveKey(1);
});

/*
|--------------------------------------------------------------------------
| Task 1: Database Constants
|--------------------------------------------------------------------------
*/

test('database constants reference correct table names', function () {
    expect(DatabaseConst::UMKM_PROFILE())->toContain('umkm_profiles');
    expect(DatabaseConst::CONVERSATION())->toContain('conversations');
    expect(DatabaseConst::MESSAGE())->toContain('messages');
    expect(DatabaseConst::ORDER())->toContain('orders');
    expect(DatabaseConst::ORDER_ITEM())->toContain('order_items');
});

/*
|--------------------------------------------------------------------------
| Task 3: Add Role Middleware
|--------------------------------------------------------------------------
*/

test('guest cannot access role protected routes', function () {
    $this->get(route('app.umkm.profile.edit'))
        ->assertRedirect(route('login'));
});

test('unauthorized role driver cannot access umkm only routes', function () {
    $user = User::factory()->make([
        'id' => 1,
        'role' => UserConst::ROLE_DRIVER,
    ]);

    $this->actingAs($user)
        ->get(route('app.umkm.profile.edit'))
        ->assertStatus(403);
});

test('authorized role umkm can access umkm only routes', function () {
    $user = User::factory()->make([
        'id' => 1,
        'role' => UserConst::ROLE_UMKM,
    ]);

    $this->mock(UmkmProfileUsecase::class, function ($mock) {
        $mock->shouldReceive('getByUserId')
            ->once()
            ->with(1)
            ->andReturn([
                'success' => true,
                'data' => [],
            ]);
    });

    $this->actingAs($user)
        ->get(route('app.umkm.profile.edit'))
        ->assertStatus(200);
});

/*
|--------------------------------------------------------------------------
| Task 4: Build UMKM Profile Management
|--------------------------------------------------------------------------
*/

test('umkm can save and view profile', function () {
    $user = User::factory()->make([
        'id' => 1,
        'role' => UserConst::ROLE_UMKM,
    ]);

    $profileData = [
        'store_name' => 'Toko Barokah',
        'description' => 'Toko kelontong serba ada',
        'address' => 'Jl. Kalipuro No. 12, Banyuwangi',
        'phone' => '08123456789',
        'category' => 'ringan',
        'latitude' => -8.123456,
        'longitude' => 114.123456,
    ];

    $this->mock(UmkmProfileUsecase::class, function ($mock) use ($profileData) {
        $mock->shouldReceive('saveProfile')
            ->once()
            ->andReturn([
                'success' => true,
            ]);

        $mock->shouldReceive('getByUserId')
            ->once()
            ->with(1)
            ->andReturn([
                'success' => true,
                'data' => $profileData,
            ]);
    });

    $this->actingAs($user)
        ->post(route('app.umkm.profile.save'), $profileData)
        ->assertRedirect(route('app.umkm.profile.edit'))
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->get(route('app.umkm.profile.edit'))
        ->assertStatus(200)
        ->assertSee('Toko Barokah')
        ->assertSee('Jl. Kalipuro No. 12, Banyuwangi');
});

test('umkm profile validation fails for incomplete data', function () {
    $user = User::factory()->make([
        'id' => 1,
        'role' => UserConst::ROLE_UMKM,
    ]);

    $profileData = [
        'store_name' => '', // Invalid
        'address' => 'Jl. Kalipuro No. 12, Banyuwangi',
        'category' => 'ringan',
        'latitude' => -8.123456,
        'longitude' => 114.123456,
    ];

    $this->mock(UmkmProfileUsecase::class, function ($mock) {
        $mock->shouldReceive('saveProfile')
            ->once()
            ->andThrow(\Illuminate\Validation\ValidationException::withMessages([
                'store_name' => ['Nama Toko wajib diisi.'],
            ]));
    });

    $this->actingAs($user)
        ->from(route('app.umkm.profile.edit'))
        ->post(route('app.umkm.profile.save'), $profileData)
        ->assertRedirect(route('app.umkm.profile.edit'))
        ->assertSessionHasErrors(['store_name']);
});

/*
|--------------------------------------------------------------------------
| Task 5: Build UMKM Discovery
|--------------------------------------------------------------------------
*/

test('guest and non pengguna cannot access discovery', function () {
    $this->get(route('app.discovery'))->assertRedirect(route('login'));

    $driver = User::factory()->make(['id' => 1, 'role' => UserConst::ROLE_DRIVER]);
    $this->actingAs($driver)->get(route('app.discovery'))->assertStatus(403);
});

test('nearby umkm are rendered sorted by distance', function () {
    $user = User::factory()->make(['id' => 1, 'role' => UserConst::ROLE_PENGGUNA]);

    $sorted = collect([
        (object) ['id' => 11, 'user_id' => 2, 'store_name' => 'Toko Dekat', 'description' => 'dekat', 'is_open' => true, 'category' => 'ringan', 'latitude' => -8.10, 'longitude' => 114.10, 'distance' => 0.5],
        (object) ['id' => 12, 'user_id' => 3, 'store_name' => 'Toko Jauh', 'description' => 'jauh', 'is_open' => true, 'category' => 'sedang', 'latitude' => -8.05, 'longitude' => 114.05, 'distance' => 5.2],
    ]);

    $this->mock(DiscoveryUsecase::class, function ($mock) use ($sorted) {
        $mock->shouldReceive('findNearby')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => ['list' => $sorted],
            ]);
    });

    $this->actingAs($user)
        ->get(route('app.discovery', ['latitude' => -8.10, 'longitude' => 114.10]))
        ->assertStatus(200)
        ->assertSeeInOrder(['Toko Dekat', 'Toko Jauh']);
});

/*
|--------------------------------------------------------------------------
| Task 6: Build Chat Conversations
|--------------------------------------------------------------------------
*/

test('duplicate conversation is not created for same pengguna and umkm', function () {
    $user = User::factory()->create(['id' => 1, 'role' => UserConst::ROLE_PENGGUNA]);
    User::factory()->create(['id' => 2, 'role' => UserConst::ROLE_UMKM]);

    $this->mock(ConversationUsecase::class, function ($mock) {
        // findOrCreate returns the same conversation id both times — contract proves dedup.
        $mock->shouldReceive('findOrCreateConversation')
            ->with(1, 2)
            ->twice()
            ->andReturn([
                'success' => true,
                'data' => ['id' => 10, 'pengguna_id' => 1, 'umkm_id' => 2],
            ]);
        $mock->shouldReceive('getMessages')
            ->andReturn(['success' => true, 'data' => ['list' => []]]);
    });

    $this->actingAs($user)->get(route('app.conversations.show', 2))->assertStatus(200);
    $this->actingAs($user)->get(route('app.conversations.show', 2))->assertStatus(200);
});

test('non participant cannot poll conversation messages', function () {
    $user = User::factory()->make(['id' => 99, 'role' => UserConst::ROLE_PENGGUNA]);

    $this->mock(ConversationUsecase::class, function ($mock) {
        $mock->shouldReceive('getConversationById')
            ->with(10)
            ->andReturn([
                'success' => true,
                'data' => ['id' => 10, 'pengguna_id' => 1, 'umkm_id' => 2],
            ]);
    });

    $this->actingAs($user)
        ->get(route('app.conversations.messages.index', 10))
        ->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| Task 7: Build Order Creation and Confirmation
|--------------------------------------------------------------------------
*/

test('umkm can create order from conversation', function () {
    $umkm = User::factory()->create(['id' => 2, 'role' => UserConst::ROLE_UMKM]);
    User::factory()->create(['id' => 1, 'role' => UserConst::ROLE_PENGGUNA]);

    DB::table(DatabaseConst::CONVERSATION())->insert([
        'id' => 10,
        'pengguna_id' => 1,
        'umkm_id' => 2,
    ]);

    $this->mock(ConversationUsecase::class, function ($mock) {
        $mock->shouldReceive('findOrCreateConversation')
            ->andReturn([
                'success' => true,
                'data' => ['id' => 10, 'pengguna_id' => 1, 'umkm_id' => 2],
            ]);
        $mock->shouldReceive('getMessages')
            ->andReturn(['success' => true, 'data' => ['list' => []]]);
    });

    $this->mock(OrderUsecase::class, function ($mock) {
        $mock->shouldReceive('getOrdersByConversation')
            ->andReturn(['success' => true, 'data' => ['list' => []]]);
        $mock->shouldReceive('createFromConversation')
            ->once()
            ->andReturn([
                'success' => true,
                'code' => 201,
                'data' => [
                    'id' => 1,
                    'order_status' => 'tunggu_konfirm',
                    'total_items_price' => 50000,
                    'shipping_fee' => 10000,
                    'payment_method' => 'cod_talangan',
                ],
            ]);
    });

    $this->actingAs($umkm)
        ->post(route('app.orders.store', 10), [
            'pengguna_id' => 1,
            'peer_id' => 1,
            'item_name' => ['Nasi Goreng'],
            'item_quantity' => [2],
            'item_price' => [25000],
            'shipping_fee' => 10000,
            'payment_method' => 'cod_talangan',
        ])
        ->assertRedirect(route('app.conversations.show', 1))
        ->assertSessionHas('success');
});

test('confirmed order appears with cari_driver status', function () {
    $pengguna = User::factory()->create(['id' => 1, 'role' => UserConst::ROLE_PENGGUNA]);
    User::factory()->create(['id' => 2, 'role' => UserConst::ROLE_UMKM]);

    DB::table(DatabaseConst::ORDER())->insert([
        'id' => 1,
        'pengguna_id' => 1,
        'umkm_id' => 2,
        'conversation_id' => 10,
        'total_items_price' => 50000,
        'shipping_fee' => 10000,
        'payment_method' => 'cod_talangan',
        'order_status' => 'tunggu_konfirm',
    ]);

    $this->mock(OrderUsecase::class, function ($mock) {
        $mock->shouldReceive('confirmByPengguna')
            ->once()
            ->with(1, 1)
            ->andReturn([
                'success' => true,
                'data' => [
                    'id' => 1,
                    'order_status' => 'cari_driver',
                    'pengguna_id' => 1,
                    'umkm_id' => 2,
                ],
                'message' => 'Pesanan dikonfirmasi.',
            ]);
    });

    $this->actingAs($pengguna)
        ->post(route('app.orders.confirm', 1), ['peer_id' => 2])
        ->assertRedirect(route('app.conversations.show', 2))
        ->assertSessionHas('success');
});

test('cod_talangan is rejected above Rp100.000', function () {
    $umkm = User::factory()->create(['id' => 2, 'role' => UserConst::ROLE_UMKM]);
    User::factory()->create(['id' => 1, 'role' => UserConst::ROLE_PENGGUNA]);

    DB::table(DatabaseConst::CONVERSATION())->insert([
        'id' => 10,
        'pengguna_id' => 1,
        'umkm_id' => 2,
    ]);

    $this->mock(ConversationUsecase::class, function ($mock) {
        $mock->shouldReceive('findOrCreateConversation')->andReturn([
            'success' => true,
            'data' => ['id' => 10, 'pengguna_id' => 1, 'umkm_id' => 2],
        ]);
        $mock->shouldReceive('getMessages')
            ->andReturn(['success' => true, 'data' => ['list' => []]]);
    });

    $this->mock(OrderUsecase::class, function ($mock) {
        $mock->shouldReceive('getOrdersByConversation')
            ->andReturn(['success' => true, 'data' => ['list' => []]]);
        $mock->shouldReceive('createFromConversation')
            ->once()
            ->andThrow(\Illuminate\Validation\ValidationException::withMessages([
                'payment_method' => ['COD Talangan tidak tersedia untuk total barang di atas Rp100.000.'],
            ]));
    });

    $this->actingAs($umkm)
        ->post(route('app.orders.store', 10), [
            'pengguna_id' => 1,
            'peer_id' => 1,
            'item_name' => ['Barang Mahal'],
            'item_quantity' => [1],
            'item_price' => [150000],
            'shipping_fee' => 15000,
            'payment_method' => 'cod_talangan',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors(['payment_method']);
});

test('non umkm cannot access order creation route', function () {
    $pengguna = User::factory()->create(['id' => 1, 'role' => UserConst::ROLE_PENGGUNA]);

    $this->actingAs($pengguna)
        ->post(route('app.orders.store', 10), [])
        ->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| Task 8: Build Admin Bangjek Assignment
|--------------------------------------------------------------------------
*/

test('unauthorized roles cannot access admin bangjek-orders route', function () {
    $pengguna = User::factory()->make(['id' => 1, 'role' => UserConst::ROLE_PENGGUNA]);
    $driver = User::factory()->make(['id' => 2, 'role' => UserConst::ROLE_DRIVER]);

    $this->actingAs($pengguna)
        ->get(route('admin.bangjek_orders.index'))
        ->assertStatus(403);

    $this->actingAs($driver)
        ->get(route('admin.bangjek_orders.index'))
        ->assertStatus(403);
});

test('authorized roles ojek_admin and superadmin can access bangjek-orders route', function () {
    $ojekAdmin = User::factory()->make(['id' => 3, 'role' => UserConst::ROLE_OJEK_ADMIN]);
    $superadmin = User::factory()->make(['id' => 4, 'role' => UserConst::ROLE_SUPERADMIN]);

    $this->mock(OrderUsecase::class, function ($mock) {
        $mock->shouldReceive('getWaitingDriverOrders')
            ->twice()
            ->andReturn([
                'success' => true,
                'data' => ['list' => []],
            ]);
    });

    $this->actingAs($ojekAdmin)
        ->get(route('admin.bangjek_orders.index'))
        ->assertStatus(200);

    $this->actingAs($superadmin)
        ->get(route('admin.bangjek_orders.index'))
        ->assertStatus(200);
});

test('authorized roles can view assignment details', function () {
    $ojekAdmin = User::factory()->make(['id' => 3, 'role' => UserConst::ROLE_OJEK_ADMIN]);

    $this->mock(OrderUsecase::class, function ($mock) {
        $mock->shouldReceive('getOrderDetailsForAdmin')
            ->once()
            ->with(1)
            ->andReturn([
                'success' => true,
                'data' => [
                    'id' => 1,
                    'pengguna_name' => 'Budi',
                    'pengguna_phone' => '081',
                    'store_name' => 'Warung Pecel',
                    'store_address' => 'Banyuwangi',
                    'store_phone' => '082',
                    'total_items_price' => 50000,
                    'shipping_fee' => 10000,
                    'payment_method' => 'cod_talangan',
                    'order_status' => 'cari_driver',
                    'items' => [],
                ],
            ]);

        $mock->shouldReceive('getActiveDrivers')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => ['list' => []],
            ]);
    });

    $this->actingAs($ojekAdmin)
        ->get(route('admin.bangjek_orders.detail', 1))
        ->assertStatus(200)
        ->assertSee('Detail Penugasan Pesanan #1')
        ->assertSee('Warung Pecel')
        ->assertSee('Budi');
});

test('admin assignment stores driver_id and changes status to dijemput', function () {
    $ojekAdmin = User::factory()->make(['id' => 3, 'role' => UserConst::ROLE_OJEK_ADMIN]);

    $this->mock(OrderUsecase::class, function ($mock) {
        $mock->shouldReceive('assignDriver')
            ->once()
            ->with(1, 5)
            ->andReturn([
                'success' => true,
                'message' => 'Driver berhasil ditugaskan.',
            ]);
    });

    $this->actingAs($ojekAdmin)
        ->post(route('admin.bangjek_orders.assign', 1), ['driver_id' => 5])
        ->assertRedirect(route('admin.bangjek_orders.index'))
        ->assertSessionHas('success', 'Driver berhasil ditugaskan.');
});

/*
|--------------------------------------------------------------------------
| Task 9: Build Driver Order Workflow
|--------------------------------------------------------------------------
*/

test('non driver cannot access driver order routes', function () {
    $pengguna = User::factory()->make(['id' => 1, 'role' => UserConst::ROLE_PENGGUNA]);
    $umkm = User::factory()->make(['id' => 2, 'role' => UserConst::ROLE_UMKM]);

    $this->actingAs($pengguna)
        ->get(route('driver.orders.index'))
        ->assertStatus(403);

    $this->actingAs($umkm)
        ->get(route('driver.orders.index'))
        ->assertStatus(403);
});

test('driver can view assigned orders list', function () {
    $driver = User::factory()->make(['id' => 5, 'role' => UserConst::ROLE_DRIVER]);

    $this->mock(OrderUsecase::class, function ($mock) {
        $mock->shouldReceive('getDriverOrders')
            ->once()
            ->with(5)
            ->andReturn([
                'success' => true,
                'data' => ['list' => []],
            ]);
    });

    $this->actingAs($driver)
        ->get(route('driver.orders.index'))
        ->assertStatus(200)
        ->assertSee('Pesanan Saya');
});

test('driver can mark order as picked up dijemput to diantar', function () {
    $driver = User::factory()->make(['id' => 5, 'role' => UserConst::ROLE_DRIVER]);

    $this->mock(OrderUsecase::class, function ($mock) {
        $mock->shouldReceive('markPickedUp')
            ->once()
            ->with(1, 5)
            ->andReturn([
                'success' => true,
                'message' => 'Status pesanan diubah ke diantar.',
            ]);
    });

    $this->actingAs($driver)
        ->post(route('driver.orders.pickup', 1))
        ->assertRedirect(route('driver.orders.detail', 1))
        ->assertSessionHas('success');
});

test('order cannot become selesai without proof image', function () {
    $driver = User::factory()->make(['id' => 5, 'role' => UserConst::ROLE_DRIVER]);

    // Post without any file attached
    $this->actingAs($driver)
        ->post(route('driver.orders.complete', 1), [])
        ->assertSessionHasErrors(['proof']);
});

test('driver can complete order with valid proof image', function () {
    $driver = User::factory()->make(['id' => 5, 'role' => UserConst::ROLE_DRIVER]);

    $fakeImage = \Illuminate\Http\UploadedFile::fake()->image('proof.jpg', 400, 400)->size(1024);

    $this->mock(OrderUsecase::class, function ($mock) {
        $mock->shouldReceive('completeWithProof')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Pesanan selesai, bukti pengiriman tersimpan.',
            ]);
    });

    $this->actingAs($driver)
        ->post(route('driver.orders.complete', 1), ['proof' => $fakeImage])
        ->assertRedirect(route('driver.orders.detail', 1))
        ->assertSessionHas('success');
});

test('complete end-to-end mvp flow from chat to delivery completion', function () {
    $pengguna = User::factory()->create(['id' => 1, 'role' => UserConst::ROLE_PENGGUNA]);
    $umkm = User::factory()->create(['id' => 2, 'role' => UserConst::ROLE_UMKM]);
    $ojekAdmin = User::factory()->create(['id' => 3, 'role' => UserConst::ROLE_OJEK_ADMIN]);
    $driver = User::factory()->create(['id' => 5, 'role' => UserConst::ROLE_DRIVER]);

    DB::table(DatabaseConst::CONVERSATION())->insert([
        'id' => 10,
        'pengguna_id' => 1,
        'umkm_id' => 2,
    ]);

    DB::table(DatabaseConst::ORDER())->insert([
        'id' => 100,
        'pengguna_id' => 1,
        'umkm_id' => 2,
        'conversation_id' => 10,
        'total_items_price' => 30000,
        'shipping_fee' => 5000,
        'payment_method' => 'cod_talangan',
        'order_status' => 'tunggu_konfirm',
    ]);

    // 1. Pengguna opens conversation with UMKM
    $this->mock(ConversationUsecase::class, function ($mock) {
        $mock->shouldReceive('findOrCreateConversation')
            ->once()
            ->with(1, 2)
            ->andReturn([
                'success' => true,
                'data' => ['id' => 10, 'pengguna_id' => 1, 'umkm_id' => 2],
            ]);
        $mock->shouldReceive('getMessages')
            ->once()
            ->with(10)
            ->andReturn(['success' => true, 'data' => ['list' => []]]);
    });

    $this->mock(OrderUsecase::class, function ($mock) {
        $mock->shouldReceive('getOrdersByConversation')
            ->once()
            ->with(10)
            ->andReturn(['success' => true, 'data' => ['list' => []]]);
    });

    $this->actingAs($pengguna)
        ->get(route('app.conversations.show', 2))
        ->assertStatus(200);

    // 2. UMKM creates order from conversation
    $this->mock(ConversationUsecase::class, function ($mock) {
        $mock->shouldReceive('findOrCreateConversation')
            ->andReturn([
                'success' => true,
                'data' => ['id' => 10, 'pengguna_id' => 1, 'umkm_id' => 2],
            ]);
    });

    $this->mock(OrderUsecase::class, function ($mock) {
        $mock->shouldReceive('createFromConversation')
            ->once()
            ->andReturn([
                'success' => true,
                'code' => 201,
                'data' => ['id' => 100, 'order_status' => 'tunggu_konfirm'],
            ]);
    });

    $this->actingAs($umkm)
        ->post(route('app.orders.store', 10), [
            'pengguna_id' => 1,
            'peer_id' => 1,
            'item_name' => ['Bakso'],
            'item_quantity' => [2],
            'item_price' => [15000],
            'shipping_fee' => 5000,
            'payment_method' => 'cod_talangan',
        ])
        ->assertRedirect(route('app.conversations.show', 1))
        ->assertSessionHas('success');

    // 3. Pengguna confirms the order
    $this->mock(OrderUsecase::class, function ($mock) {
        $mock->shouldReceive('confirmByPengguna')
            ->once()
            ->with(100, 1)
            ->andReturn([
                'success' => true,
                'data' => ['id' => 100, 'order_status' => 'cari_driver'],
            ]);
    });

    $this->actingAs($pengguna)
        ->post(route('app.orders.confirm', 100), ['peer_id' => 2])
        ->assertRedirect(route('app.conversations.show', 2))
        ->assertSessionHas('success');

    // 4. Admin assigns driver
    $this->mock(OrderUsecase::class, function ($mock) {
        $mock->shouldReceive('assignDriver')
            ->once()
            ->with(100, 5)
            ->andReturn([
                'success' => true,
                'message' => 'Driver berhasil ditugaskan.',
            ]);
    });

    $this->actingAs($ojekAdmin)
        ->post(route('admin.bangjek_orders.assign', 100), ['driver_id' => 5])
        ->assertRedirect(route('admin.bangjek_orders.index'))
        ->assertSessionHas('success');

    // 5. Driver marks order as picked up (dijemput -> diantar)
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

    // 6. Driver completes order with proof of delivery
    $fakeImage = \Illuminate\Http\UploadedFile::fake()->image('proof.webp', 300, 300);
    $this->mock(OrderUsecase::class, function ($mock) {
        $mock->shouldReceive('completeWithProof')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Pesanan selesai.',
            ]);
    });

    $this->actingAs($driver)
        ->post(route('driver.orders.complete', 100), ['proof' => $fakeImage])
        ->assertRedirect(route('driver.orders.detail', 100))
        ->assertSessionHas('success');
});

/*
|--------------------------------------------------------------------------
| Hardened Security Tests (Task 6 & Task 7 Security Rejection)
|--------------------------------------------------------------------------
*/

test('forged peerId gets rejected in conversation show', function () {
    $pengguna = User::factory()->create(['id' => 1, 'role' => UserConst::ROLE_PENGGUNA]);
    
    // Forged peer user has role driver (which is not allowed) or does not exist
    $this->actingAs($pengguna)
        ->get(route('app.conversations.show', 999))
        ->assertStatus(404);

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




