<?php

use App\Constants\DatabaseConst;
use App\Constants\UserConst;
use App\Usecase\DiscoveryUsecase;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Task 1 (MVP 2): earthdistance-backed discovery
|--------------------------------------------------------------------------
| The cube + earthdistance path only runs on PostgreSQL, but the default
| test connection is SQLite in-memory. This file targets the configured pgsql
| connection directly, skips when the extension (or connection) is unavailable,
| and wraps each case in a transaction so the shared dev database is untouched.
*/

beforeEach(function () {
    // phpunit.xml forces DB_DATABASE=:memory: which leaks into the pgsql config,
    // so point the pgsql connection at the real dev database explicitly.
    config(['database.connections.pgsql.database' => env('TEST_PGSQL_DATABASE', 'suaralokal')]);
    DB::purge('pgsql');

    try {
        $ext = DB::connection('pgsql')
            ->selectOne("SELECT 1 AS ok FROM pg_extension WHERE extname = 'earthdistance'");
    } catch (Throwable $e) {
        $this->markTestSkipped('pgsql connection unavailable: '.$e->getMessage());

        return;
    }

    if (! $ext) {
        $this->markTestSkipped('earthdistance extension not enabled on pgsql.');
    }

    // Route DB::table() (and therefore the Usecase) at the pgsql connection.
    config(['database.default' => 'pgsql']);
    DB::beginTransaction();
});

afterEach(function () {
    if (DB::transactionLevel() > 0) {
        DB::rollBack();
    }
});

function insertUmkmAt(float $lat, float $lng, string $storeName): void
{
    $now = now();
    $userId = DB::table(DatabaseConst::USER())->insertGetId([
        'name' => $storeName.' Owner',
        'email' => strtolower(str_replace(' ', '.', $storeName)).'_'.uniqid().'@suaralokal.test',
        'password' => 'x',
        'role' => UserConst::ROLE_UMKM,
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table(DatabaseConst::UMKM_PROFILE())->insert([
        'user_id' => $userId,
        'store_name' => $storeName,
        'is_open' => true,
        'latitude' => $lat,
        'longitude' => $lng,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

test('earthdistance discovery returns nearest umkm first within radius', function () {
    // Origin far from seed data so only the two stores below are in range.
    insertUmkmAt(1.0100, 1.0100, 'Toko Sangat Dekat');
    insertUmkmAt(1.0500, 1.0500, 'Toko Lebih Jauh');

    $usecase = app(DiscoveryUsecase::class);
    $result = $usecase->findNearby([
        'latitude' => 1.0000,
        'longitude' => 1.0000,
        'radius' => 100,
    ]);

    $list = $result['data']['list'] ?? [];
    $names = array_column($list->toArray(), 'store_name');
    $distances = array_column($list->toArray(), 'distance');

    expect($result['success'])->toBeTrue()
        ->and($names)->toContain('Toko Sangat Dekat', 'Toko Lebih Jauh')
        ->and(array_search('Toko Sangat Dekat', $names))
        ->toBeLessThan(array_search('Toko Lebih Jauh', $names))
        ->and((float) $distances[array_search('Toko Sangat Dekat', $names)])
        ->toBeLessThan((float) $distances[array_search('Toko Lebih Jauh', $names)]);
});

test('earthdistance discovery excludes umkm outside radius', function () {
    insertUmkmAt(1.0100, 1.0100, 'Toko Dalam Radius');
    insertUmkmAt(5.0000, 5.0000, 'Toko Di Luar Radius');

    $usecase = app(DiscoveryUsecase::class);
    $result = $usecase->findNearby([
        'latitude' => 1.0000,
        'longitude' => 1.0000,
        'radius' => 50,
    ]);

    $names = array_column(($result['data']['list'] ?? collect())->toArray(), 'store_name');

    expect($result['success'])->toBeTrue()
        ->and($names)->toContain('Toko Dalam Radius')
        ->and($names)->not->toContain('Toko Di Luar Radius');
});
