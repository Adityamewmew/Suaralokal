<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SseController extends Controller
{
    /**
     * Stream real-time order status updates to active users.
     */
    public function streamOrders(Request $request): StreamedResponse
    {
        $userId = auth()->user()->id;
        $cacheKey = "sse_order_updates_{$userId}";

        return new StreamedResponse(function () use ($cacheKey) {
            // Disable output buffering
            if (function_exists('apache_setenv')) {
                @apache_setenv('no-gzip', '1');
            }
            @ini_set('zlib.output_compression', '0');
            @ini_set('implicit_flush', '1');
            if (!app()->environment('testing')) {
                @ob_end_clean();
            }

            // Send headers for SSE stream
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no'); // Disable buffering on Nginx

            $startTime = time();
            $maxDuration = app()->environment('testing') ? 0.1 : 25; // Limit loop in testing to prevent blocking FPM/tests

            while (time() - $startTime < $maxDuration) {
                if (Cache::has($cacheKey)) {
                    $updates = Cache::pull($cacheKey, []);
                    foreach ($updates as $update) {
                        echo "data: " . json_encode($update) . "\n\n";
                    }
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }

                // Heartbeat to prevent browser timeout
                echo ": heartbeat\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                sleep(1);
            }
        });
    }
}
