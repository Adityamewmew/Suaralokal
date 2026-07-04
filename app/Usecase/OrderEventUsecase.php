<?php

namespace App\Usecase;

use App\Constants\DatabaseConst;
use App\Http\Presenter\Response;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderEventUsecase extends Usecase
{
    public function __construct()
    {
        $this->className = __CLASS__;
    }

    /**
     * Log an order event.
     */
    public function logEvent(int $orderId, string $eventType, ?array $payload = null): bool
    {
        try {
            DB::table(DatabaseConst::ORDER_EVENT())->insert([
                'order_id' => $orderId,
                'event_type' => $eventType,
                'payload' => $payload ? json_encode($payload) : null,
                'created_at' => now(),
            ]);

            return true;
        } catch (Exception $e) {
            Log::error(
                message: 'Failed to log order event: ' . $e->getMessage(),
                context: ['method' => __METHOD__, 'order_id' => $orderId, 'event_type' => $eventType]
            );
            return false;
        }
    }

    /**
     * Get all events for a specific order.
     */
    public function getEventsForOrder(int $orderId): array
    {
        try {
            $events = DB::table(DatabaseConst::ORDER_EVENT())
                ->where('order_id', $orderId)
                ->orderBy('created_at', 'asc')
                ->get();

            return Response::buildSuccess(data: ['list' => $events]);
        } catch (Exception $e) {
            Log::error(
                message: $e->getMessage(),
                context: ['method' => __METHOD__]
            );
            return Response::buildErrorService($e->getMessage());
        }
    }
}
