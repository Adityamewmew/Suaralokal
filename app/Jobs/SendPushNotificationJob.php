<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $token,
        public string $title,
        public string $body,
        public array $payload = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $serverKey = config('services.fcm.key');
        $projectId = config('services.fcm.project_id');

        Log::info('[FCM Push Notification Job] Sending notification', [
            'token' => $this->token,
            'title' => $this->title,
            'body' => $this->body,
            'payload' => $this->payload,
        ]);

        if (empty($serverKey)) {
            Log::warning('[FCM Push Notification Job] Server Key not set, skipping HTTP request.');
            return;
        }

        // Send FCM notification (Legacy FCM HTTP v1 or legacy endpoint depending on keys configured)
        // Here we show the standard HTTP request:
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $this->token,
                'notification' => [
                    'title' => $this->title,
                    'body' => $this->body,
                ],
                'data' => $this->payload,
            ]);

            if ($response->failed()) {
                Log::error('[FCM Push Notification Job] FCM API failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('[FCM Push Notification Job] Connection failed: ' . $e->getMessage());
        }
    }
}
