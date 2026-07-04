<?php

namespace App\Usecase;

use App\Constants\DatabaseConst;
use App\Http\Presenter\Response;
use App\Jobs\SendPushNotificationJob;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationUsecase extends Usecase
{
    public function __construct()
    {
        $this->className = __CLASS__;
    }

    /**
     * Dispatch push notification to a user.
     */
    public function sendPush($userId, string $title, string $body, array $payload = []): array
    {
        try {
            $user = DB::table(DatabaseConst::USER())->where('id', $userId)->first();
            if (! $user) {
                return Response::buildErrorNotFound('User tidak ditemukan.');
            }

            // In real app, we fetch the FCM device token from user table or user_tokens table.
            // For MVP, we will check if they have a mock token or default to a mock token.
            $token = $user->fcm_token ?? 'mock_fcm_token_' . $userId;

            // Dispatch FCM queued job
            SendPushNotificationJob::dispatch($token, $title, $body, $payload);

            return Response::buildSuccess(message: 'Push notification disinkronkan ke queue.');
        } catch (Exception $e) {
            Log::error(
                message: $e->getMessage(),
                context: ['method' => __METHOD__]
            );
            return Response::buildErrorService($e->getMessage());
        }
    }

    /**
     * Trigger real-time order update for SSE and push notifications.
     */
    public function triggerOrderUpdate(int $orderId, string $status): void
    {
        try {
            $order = DB::table(DatabaseConst::ORDER())->where('id', $orderId)->first();
            if (! $order) return;

            $penggunaId = (int) $order->pengguna_id;
            $umkmId = (int) $order->umkm_id;
            $driverId = $order->driver_id ? (int) $order->driver_id : null;

            // SSE payload
            $ssePayload = [
                'order_id' => $orderId,
                'status' => $status,
                'updated_at' => now()->toDateTimeString(),
            ];

            // 1. Store event in cache for each participant to consume via SSE stream
            $participants = array_filter([$penggunaId, $umkmId, $driverId]);
            foreach ($participants as $pId) {
                $cacheKey = "sse_order_updates_{$pId}";
                $existing = Cache::get($cacheKey, []);
                $existing[] = $ssePayload;
                Cache::put($cacheKey, $existing, now()->addMinutes(5));
            }

            // 2. Prepare and send push notifications based on status changes
            $statusLabels = [
                'cari_driver' => 'Mencari Driver',
                'dijemput' => 'Pesanan Dijemput',
                'diantar' => 'Pesanan Sedang Diantar',
                'selesai' => 'Pesanan Selesai',
            ];

            $statusText = $statusLabels[$status] ?? $status;
            $title = "Status Pesanan #{$orderId}";
            $body = "Pesanan Anda kini berstatus: {$statusText}";

            // Notify pengguna
            $this->sendPush($penggunaId, $title, $body, $ssePayload);

            // Notify UMKM
            $this->sendPush($umkmId, $title, $body, $ssePayload);

            // Notify driver if assigned
            if ($driverId) {
                $this->sendPush($driverId, $title, $body, $ssePayload);
            }
        } catch (Exception $e) {
            Log::error(
                message: $e->getMessage(),
                context: ['method' => __METHOD__]
            );
        }
    }
}
