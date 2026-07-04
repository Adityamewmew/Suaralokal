<?php

namespace App\Usecase;

use App\Constants\DatabaseConst;
use App\Constants\ResponseConst;
use App\Http\Presenter\Response;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class OrderUsecase extends Usecase
{
    /** @var int Maximum total items price allowed for cod_talangan (Rp100.000). */
    private const COD_TALANGAN_LIMIT = 100000;

    public function __construct()
    {
        $this->className = __CLASS__;
    }

    /**
     * Create an order from a conversation (UMKM action).
     *
     * Validates items, computes total_items_price, enforces cod_talangan cap,
     * then inserts orders + order_items inside a DB transaction.
     *
     * @return array{success: bool, data?: array, message?: string}
     */
    public function createFromConversation(array $data, int $umkmId): array
    {
        $validator = Validator::make($data, [
            'conversation_id' => 'required|integer',
            'pengguna_id' => 'required|integer',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'shipping_fee' => 'required|numeric|min:0',
            'payment_method' => 'required|string|in:cod_talangan,non_tunai',
        ]);

        $validator->validate();

        // Compute total items price
        $totalItemsPrice = 0;
        foreach ($data['items'] as $item) {
            $totalItemsPrice += (float) $item['price'] * (int) $item['quantity'];
        }

        // ponytail: COD Talangan maximum Rp100.000 per mvp.md §4.5
        if ($data['payment_method'] === 'cod_talangan' && $totalItemsPrice > self::COD_TALANGAN_LIMIT) {
            throw ValidationException::withMessages([
                'payment_method' => ['COD Talangan tidak tersedia untuk total barang di atas Rp100.000.'],
            ]);
        }

        DB::beginTransaction();

        try {
            $now = now();

            $orderId = DB::table(DatabaseConst::ORDER())->insertGetId([
                'pengguna_id' => (int) $data['pengguna_id'],
                'umkm_id' => $umkmId,
                'conversation_id' => (int) $data['conversation_id'],
                'total_items_price' => $totalItemsPrice,
                'shipping_fee' => (float) $data['shipping_fee'],
                'payment_method' => $data['payment_method'],
                'order_status' => 'tunggu_konfirm',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($data['items'] as $item) {
                DB::table(DatabaseConst::ORDER_ITEM())->insert([
                    'order_id' => $orderId,
                    'item_name' => $item['item_name'],
                    'quantity' => (int) $item['quantity'],
                    'price' => (float) $item['price'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::commit();

            $order = DB::table(DatabaseConst::ORDER())->where('id', $orderId)->first();

            return Response::buildSuccessCreated(data: collect($order)->toArray());
        } catch (Exception $e) {
            DB::rollback();

            Log::error(
                message: $e->getMessage(),
                context: ['method' => __METHOD__]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }

    /**
     * Confirm an order (pengguna action): tunggu_konfirm → cari_driver.
     *
     * @return array{success: bool, data?: array, message?: string}
     */
    public function confirmByPengguna(int $orderId, int $penggunaId): array
    {
        try {
            $order = DB::table(DatabaseConst::ORDER())
                ->where('id', $orderId)
                ->first();

            if (! $order) {
                return Response::buildErrorNotFound('Pesanan tidak ditemukan.');
            }

            if ((int) $order->pengguna_id !== $penggunaId) {
                return Response::buildError(
                    code: ResponseConst::HTTP_FORBIDDEN,
                    message: 'Anda tidak berhak mengonfirmasi pesanan ini.'
                );
            }

            if ($order->order_status !== 'tunggu_konfirm') {
                return Response::buildError(
                    code: ResponseConst::HTTP_BAD_REQUEST,
                    message: 'Pesanan ini tidak dalam status menunggu konfirmasi.'
                );
            }

            DB::table(DatabaseConst::ORDER())
                ->where('id', $orderId)
                ->update([
                    'order_status' => 'cari_driver',
                    'updated_at' => now(),
                ]);

            $order = DB::table(DatabaseConst::ORDER())->where('id', $orderId)->first();

            return Response::buildSuccess(
                data: collect($order)->toArray(),
                message: 'Pesanan dikonfirmasi.'
            );
        } catch (Exception $e) {
            Log::error(
                message: $e->getMessage(),
                context: ['method' => __METHOD__]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }

    /**
     * Get all orders belonging to a conversation, each with its items.
     */
    public function getOrdersByConversation(int $conversationId): array
    {
        try {
            $orders = DB::table(DatabaseConst::ORDER())
                ->where('conversation_id', $conversationId)
                ->orderBy('created_at', 'desc')
                ->get();

            // Attach items to each order
            foreach ($orders as $order) {
                $order->items = DB::table(DatabaseConst::ORDER_ITEM())
                    ->where('order_id', $order->id)
                    ->get();
            }

            return Response::buildSuccess(data: ['list' => $orders]);
        } catch (Exception $e) {
            Log::error(
                message: $e->getMessage(),
                context: ['method' => __METHOD__]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }

    /**
     * Get a single order by id with its items.
     */
    public function getOrderById(int $orderId): array
    {
        try {
            $order = DB::table(DatabaseConst::ORDER())
                ->where('id', $orderId)
                ->first();

            if (! $order) {
                return Response::buildErrorNotFound('Pesanan tidak ditemukan.');
            }

            $order->items = DB::table(DatabaseConst::ORDER_ITEM())
                ->where('order_id', $order->id)
                ->get();

            return Response::buildSuccess(data: collect($order)->toArray());
        } catch (Exception $e) {
            Log::error(
                message: $e->getMessage(),
                context: ['method' => __METHOD__]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }

    /**
     * Get all orders with order_status = 'cari_driver'.
     * Joins pengguna user details and UMKM store profile.
     */
    public function getWaitingDriverOrders(): array
    {
        try {
            $orders = DB::table(DatabaseConst::ORDER() . ' as o')
                ->join(DatabaseConst::USER() . ' as up', 'o.pengguna_id', '=', 'up.id')
                ->join(DatabaseConst::UMKM_PROFILE() . ' as umkm', 'o.umkm_id', '=', 'umkm.user_id')
                ->select(
                    'o.*',
                    'up.name as pengguna_name',
                    'umkm.store_name as store_name',
                    'umkm.address as store_address'
                )
                ->where('o.order_status', 'cari_driver')
                ->orderBy('o.created_at', 'desc')
                ->get();

            return Response::buildSuccess(data: ['list' => $orders]);
        } catch (Exception $e) {
            Log::error(
                message: $e->getMessage(),
                context: ['method' => __METHOD__]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }

    /**
     * Get all active driver users (role = 'driver').
     */
    public function getActiveDrivers(): array
    {
        try {
            $drivers = DB::table(DatabaseConst::USER())
                ->where('role', \App\Constants\UserConst::ROLE_DRIVER)
                ->whereNull('deleted_at')
                ->orderBy('name', 'asc')
                ->get();

            return Response::buildSuccess(data: ['list' => $drivers]);
        } catch (Exception $e) {
            Log::error(
                message: $e->getMessage(),
                context: ['method' => __METHOD__]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }

    /**
     * Get detailed order info for admin review.
     */
    public function getOrderDetailsForAdmin(int $orderId): array
    {
        try {
            $order = DB::table(DatabaseConst::ORDER() . ' as o')
                ->join(DatabaseConst::USER() . ' as up', 'o.pengguna_id', '=', 'up.id')
                ->join(DatabaseConst::UMKM_PROFILE() . ' as umkm', 'o.umkm_id', '=', 'umkm.user_id')
                ->leftJoin(DatabaseConst::USER() . ' as d', 'o.driver_id', '=', 'd.id')
                ->select(
                    'o.*',
                    'up.name as pengguna_name',
                    'up.phone as pengguna_phone',
                    'umkm.store_name as store_name',
                    'umkm.address as store_address',
                    'umkm.phone as store_phone',
                    'd.name as driver_name',
                    'd.phone as driver_phone'
                )
                ->where('o.id', $orderId)
                ->first();

            if (! $order) {
                return Response::buildErrorNotFound('Pesanan tidak ditemukan.');
            }

            $order->items = DB::table(DatabaseConst::ORDER_ITEM())
                ->where('order_id', $order->id)
                ->get();

            return Response::buildSuccess(data: collect($order)->toArray());
        } catch (Exception $e) {
            Log::error(
                message: $e->getMessage(),
                context: ['method' => __METHOD__]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }

    /**
     * Assign a driver to a pending order and change status to 'dijemput'.
     */
    public function assignDriver(int $orderId, int $driverId): array
    {
        DB::beginTransaction();

        try {
            $order = DB::table(DatabaseConst::ORDER())
                ->where('id', $orderId)
                ->first();

            if (! $order) {
                return Response::buildErrorNotFound('Pesanan tidak ditemukan.');
            }

            if ($order->order_status !== 'cari_driver') {
                return Response::buildError(
                    code: ResponseConst::HTTP_BAD_REQUEST,
                    message: 'Pesanan tidak bisa ditugaskan karena status bukan cari_driver.'
                );
            }

            // Verify driver exists and is active
            $driver = DB::table(DatabaseConst::USER())
                ->where('id', $driverId)
                ->where('role', \App\Constants\UserConst::ROLE_DRIVER)
                ->whereNull('deleted_at')
                ->first();

            if (! $driver) {
                return Response::buildErrorNotFound('Driver tidak ditemukan atau tidak aktif.');
            }

            DB::table(DatabaseConst::ORDER())
                ->where('id', $orderId)
                ->update([
                    'driver_id' => $driverId,
                    'order_status' => 'dijemput',
                    'updated_at' => now(),
                ]);

            DB::commit();

            return Response::buildSuccess(
                message: 'Driver berhasil ditugaskan.'
            );
        } catch (Exception $e) {
            DB::rollback();

            Log::error(
                message: $e->getMessage(),
                context: ['method' => __METHOD__]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }

    /**
     * Get all orders assigned to a specific driver.
     * Joins pengguna and umkm_profile for display info.
     */
    public function getDriverOrders(int $driverId): array
    {
        try {
            $orders = DB::table(DatabaseConst::ORDER() . ' as o')
                ->join(DatabaseConst::USER() . ' as up', 'o.pengguna_id', '=', 'up.id')
                ->join(DatabaseConst::UMKM_PROFILE() . ' as umkm', 'o.umkm_id', '=', 'umkm.user_id')
                ->select(
                    'o.*',
                    'up.name as pengguna_name',
                    'up.phone as pengguna_phone',
                    'umkm.store_name as store_name',
                    'umkm.address as store_address',
                    'umkm.phone as store_phone',
                    'umkm.latitude as store_latitude',
                    'umkm.longitude as store_longitude'
                )
                ->where('o.driver_id', $driverId)
                ->whereIn('o.order_status', ['dijemput', 'diantar', 'selesai'])
                ->orderByRaw("CASE o.order_status WHEN 'dijemput' THEN 1 WHEN 'diantar' THEN 2 WHEN 'selesai' THEN 3 END")
                ->orderBy('o.updated_at', 'desc')
                ->get();

            return Response::buildSuccess(data: ['list' => $orders]);
        } catch (Exception $e) {
            Log::error(
                message: $e->getMessage(),
                context: ['method' => __METHOD__]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }

    /**
     * Driver marks an order as picked up: dijemput → diantar.
     */
    public function markPickedUp(int $orderId, int $driverId): array
    {
        try {
            $order = DB::table(DatabaseConst::ORDER())
                ->where('id', $orderId)
                ->first();

            if (! $order) {
                return Response::buildErrorNotFound('Pesanan tidak ditemukan.');
            }

            if ((int) $order->driver_id !== $driverId) {
                return Response::buildError(
                    code: ResponseConst::HTTP_FORBIDDEN,
                    message: 'Anda tidak ditugaskan untuk pesanan ini.'
                );
            }

            if ($order->order_status !== 'dijemput') {
                return Response::buildError(
                    code: ResponseConst::HTTP_BAD_REQUEST,
                    message: 'Pesanan tidak dalam status dijemput.'
                );
            }

            DB::table(DatabaseConst::ORDER())
                ->where('id', $orderId)
                ->update([
                    'order_status' => 'diantar',
                    'updated_at' => now(),
                ]);

            return Response::buildSuccess(message: 'Status pesanan diubah ke diantar.');
        } catch (Exception $e) {
            Log::error(
                message: $e->getMessage(),
                context: ['method' => __METHOD__]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }

    /**
     * Driver completes an order with proof of delivery: diantar → selesai.
     * Requires a valid image file upload.
     */
    public function completeWithProof(int $orderId, int $driverId, \Illuminate\Http\UploadedFile $proof): array
    {
        try {
            $order = DB::table(DatabaseConst::ORDER())
                ->where('id', $orderId)
                ->first();

            if (! $order) {
                return Response::buildErrorNotFound('Pesanan tidak ditemukan.');
            }

            if ((int) $order->driver_id !== $driverId) {
                return Response::buildError(
                    code: ResponseConst::HTTP_FORBIDDEN,
                    message: 'Anda tidak ditugaskan untuk pesanan ini.'
                );
            }

            if ($order->order_status !== 'diantar') {
                return Response::buildError(
                    code: ResponseConst::HTTP_BAD_REQUEST,
                    message: 'Pesanan tidak dalam status diantar.'
                );
            }

            // Store proof image
            $path = $proof->store('proof-of-delivery', 'public');

            DB::table(DatabaseConst::ORDER())
                ->where('id', $orderId)
                ->update([
                    'order_status' => 'selesai',
                    'proof_of_delivery_url' => $path,
                    'updated_at' => now(),
                ]);

            return Response::buildSuccess(message: 'Pesanan selesai, bukti pengiriman tersimpan.');
        } catch (Exception $e) {
            Log::error(
                message: $e->getMessage(),
                context: ['method' => __METHOD__]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }
}

