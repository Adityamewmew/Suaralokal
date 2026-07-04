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
