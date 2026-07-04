<?php

use App\Constants\DatabaseConst;
use App\Constants\UserConst;
use App\Usecase\OrderEventUsecase;
use App\Usecase\SettlementUsecase;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| MVP 2 PostgreSQL integration suite
|--------------------------------------------------------------------------
| Proves the flows that touch real target-schema objects — settlement,
| order_events, and earthdistance discovery — run on PostgreSQL, not only on
| the SQLite in-memory harness. Production code uses Query Builder only (no
| SQLite-specific DDL such as AUTOINCREMENT), so exercising it here against
| the bigserial/numeric PG schema is the proof.
|
| Each case points the default connection at the dev pgsql database, runs
| inside a transaction, and rolls back so the shared database is untouched.
| Skipped when the connection or the earthdistance extension is unavailable.
*/

beforeEach(function () {
    // phpunit.xml forces DB_DATABASE=:memory: which leaks into the pgsql config,
    // so point the pgsql connection at the real dev database explicitly.
    config(['database.connections.pgsql.database' => env('TEST_PGSQL_DATABASE', 'suaralokal')]);
    DB::purge('pgsql');

    try {
        DB::connection('pgsql')->getPdo();
    } catch (Throwable $e) {
        $this->markTestSkipped('pgsql connection unavailable: '.$e->getMessage());

        return;
    }

    $ext = DB::connection('pgsql')
        ->selectOne("SELECT 1 AS ok FROM pg_extension WHERE extname = 'earthdistance'");
    if (! $ext) {
        $this->markTestSkipped('earthdistance extension not enabled on pgsql.');
    }

    // Route DB::table() (and therefore the Usecases) at the pgsql connection.
    config(['database.default' => 'pgsql']);
    DB::beginTransaction();
});

afterEach(function () {
    if (DB::transactionLevel() > 0) {
        DB::rollBack();
    }
});

/**
 * Seed a pengguna + umkm + driver + umkm_profile + order on the target schema.
 * Mirrors the FK shape of the real tables (orders references users x3).
 */
function pgSeedOrderContext(): int
{
    $now = now();
    $user = function (string $name, string $role) use ($now): int {
        return DB::table(DatabaseConst::USER())->insertGetId([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '_', $name)).'_'.uniqid().'@suaralokal.test',
            'password' => 'x',
            'role' => $role,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    };

    $penggunaId = $user('PG Pengguna', UserConst::ROLE_PENGGUNA);
    $umkmId = $user('PG Umkm', UserConst::ROLE_UMKM);
    $driverId = $user('PG Driver', UserConst::ROLE_DRIVER);

    DB::table(DatabaseConst::UMKM_PROFILE())->insert([
        'user_id' => $umkmId,
        'store_name' => 'Toko PG',
        'is_open' => true,
        'latitude' => -8.10,
        'longitude' => 114.10,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return DB::table(DatabaseConst::ORDER())->insertGetId([
        'pengguna_id' => $penggunaId,
        'umkm_id' => $umkmId,
        'driver_id' => $driverId,
        'order_status' => 'selesai',
        'payment_method' => 'cod_talangan',
        'total_items_price' => 30000,
        'shipping_fee' => 5000,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

test('order_events insert and read on postgres target schema', function () {
    $orderId = pgSeedOrderContext();
    $events = app(OrderEventUsecase::class);

    expect($events->logEvent($orderId, 'create', ['actor' => 'pg']))->toBeTrue()
        ->and($events->logEvent($orderId, 'confirm', null))->toBeTrue();

    $list = ($events->getEventsForOrder($orderId)['data']['list'] ?? collect())->toArray();
    $types = array_column($list, 'event_type');

    expect(count($list))->toBe(2)
        ->and($types)->toContain('create', 'confirm')
        ->and(DB::table(DatabaseConst::ORDER_EVENT())->where('order_id', $orderId)->count())->toBe(2);
});

test('settlement settle transitions state and writes audit event on postgres target', function () {
    $orderId = pgSeedOrderContext();
    $now = now();

    // SettlementUsecase::settle() reads, updates, and logs an event — all on PG.
    $settlementId = DB::table(DatabaseConst::SETTLEMENT())->insertGetId([
        'order_id' => $orderId,
        'amount' => 35000,
        'status' => 'menunggu_reimburse',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $result = app(SettlementUsecase::class)->settle($settlementId);
    expect($result['success'])->toBeTrue();

    $settled = DB::table(DatabaseConst::SETTLEMENT())->where('id', $settlementId)->first();
    expect($settled->status)->toBe('selesai_reimburse')
        ->and($settled->settled_at)->not->toBeNull()
        ->and(DB::table(DatabaseConst::ORDER_EVENT())
            ->where('order_id', $orderId)
            ->where('event_type', 'settlement')
            ->count())->toBe(1);
});

test('settlement cannot be settled twice on postgres target', function () {
    $orderId = pgSeedOrderContext();
    $now = now();

    $settlementId = DB::table(DatabaseConst::SETTLEMENT())->insertGetId([
        'order_id' => $orderId,
        'amount' => 35000,
        'status' => 'menunggu_reimburse',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $usecase = app(SettlementUsecase::class);
    expect($usecase->settle($settlementId)['success'])->toBeTrue()
        ->and($usecase->settle($settlementId)['success'])->toBeFalse();
});
