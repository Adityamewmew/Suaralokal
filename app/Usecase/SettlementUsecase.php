<?php

namespace App\Usecase;

use App\Constants\DatabaseConst;
use App\Constants\ResponseConst;
use App\Http\Presenter\Response;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettlementUsecase extends Usecase
{
    public function __construct()
    {
        $this->className = __CLASS__;
    }

    /**
     * Get settlements list with filters.
     */
    public function getSettlements(?string $status = null): array
    {
        try {
            $query = DB::table(DatabaseConst::SETTLEMENT() . ' as s')
                ->join(DatabaseConst::ORDER() . ' as o', 's.order_id', '=', 'o.id')
                ->join(DatabaseConst::USER() . ' as d', 'o.driver_id', '=', 'd.id')
                ->join(DatabaseConst::UMKM_PROFILE() . ' as umkm', 'o.umkm_id', '=', 'umkm.user_id')
                ->select(
                    's.*',
                    'o.payment_method',
                    'o.order_status',
                    'd.name as driver_name',
                    'umkm.store_name as store_name'
                );

            if ($status) {
                $query->where('s.status', $status);
            }

            $list = $query->orderBy('s.created_at', 'desc')->get();

            return Response::buildSuccess(data: ['list' => $list]);
        } catch (Exception $e) {
            Log::error(
                message: $e->getMessage(),
                context: ['method' => __METHOD__]
            );
            return Response::buildErrorService($e->getMessage());
        }
    }

    /**
     * Get specific settlement details.
     */
    public function getSettlementDetails(int $id): array
    {
        try {
            $settlement = DB::table(DatabaseConst::SETTLEMENT() . ' as s')
                ->join(DatabaseConst::ORDER() . ' as o', 's.order_id', '=', 'o.id')
                ->join(DatabaseConst::USER() . ' as d', 'o.driver_id', '=', 'd.id')
                ->join(DatabaseConst::USER() . ' as p', 'o.pengguna_id', '=', 'p.id')
                ->join(DatabaseConst::UMKM_PROFILE() . ' as umkm', 'o.umkm_id', '=', 'umkm.user_id')
                ->select(
                    's.*',
                    'o.total_items_price',
                    'o.shipping_fee',
                    'o.payment_method',
                    'o.order_status',
                    'd.name as driver_name',
                    'p.name as pengguna_name',
                    'umkm.store_name as store_name'
                )
                ->where('s.id', $id)
                ->first();

            if (! $settlement) {
                return Response::buildErrorNotFound('Settlement tidak ditemukan.');
            }

            return Response::buildSuccess(data: collect($settlement)->toArray());
        } catch (Exception $e) {
            Log::error(
                message: $e->getMessage(),
                context: ['method' => __METHOD__]
            );
            return Response::buildErrorService($e->getMessage());
        }
    }

    /**
     * Settle reimbursement: menunggu_reimburse → selesai_reimburse.
     */
    public function settle(int $id): array
    {
        DB::beginTransaction();

        try {
            $settlement = DB::table(DatabaseConst::SETTLEMENT())->where('id', $id)->first();
            if (! $settlement) {
                return Response::buildErrorNotFound('Settlement tidak ditemukan.');
            }

            if ($settlement->status !== 'menunggu_reimburse') {
                return Response::buildError(
                    code: ResponseConst::HTTP_BAD_REQUEST,
                    message: 'Settlement sudah diselesaikan sebelumnya.'
                );
            }

            DB::table(DatabaseConst::SETTLEMENT())
                ->where('id', $id)
                ->update([
                    'status' => 'selesai_reimburse',
                    'settled_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::commit();

            return Response::buildSuccess(message: 'Reimburse talangan berhasil diselesaikan.');
        } catch (Exception $e) {
            DB::rollback();
            Log::error(
                message: $e->getMessage(),
                context: ['method' => __METHOD__]
            );
            return Response::buildErrorService($e->getMessage());
        }
    }
}
