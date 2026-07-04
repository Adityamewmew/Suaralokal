<?php

namespace App\Http\Controllers\App;

use App\Constants\ResponseConst;
use App\Constants\UserConst;
use App\Http\Controllers\Controller;
use App\Usecase\OrderUsecase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected array $page = [
        'route' => 'orders',
        'title' => 'Pesanan',
    ];

    public function __construct(
        protected OrderUsecase $usecase
    ) {}

    /**
     * UMKM creates an order from a conversation.
     */
    public function store(Request $request, int $conversationId): RedirectResponse
    {
        $user = auth()->user();

        // Security: verify conversation exists and belongs to the authenticated UMKM
        $conversation = \Illuminate\Support\Facades\DB::table(\App\Constants\DatabaseConst::CONVERSATION())
            ->where('id', $conversationId)
            ->first();

        if (! $conversation) {
            abort(404, 'Percakapan tidak ditemukan.');
        }

        if ((int) $conversation->umkm_id !== $user->id) {
            abort(403, 'Akses ditolak.');
        }

        // Securely derive penggunaId from the conversation record
        $penggunaId = (int) $conversation->pengguna_id;

        // Build items array from the flat form inputs
        $items = [];
        $itemNames = $request->input('item_name', []);
        $itemQuantities = $request->input('item_quantity', []);
        $itemPrices = $request->input('item_price', []);

        for ($i = 0; $i < count($itemNames); $i++) {
            if (! empty($itemNames[$i])) {
                $items[] = [
                    'item_name' => $itemNames[$i],
                    'quantity' => $itemQuantities[$i] ?? 1,
                    'price' => $itemPrices[$i] ?? 0,
                ];
            }
        }

        $data = [
            'conversation_id' => $conversationId,
            'pengguna_id' => $penggunaId,
            'items' => $items,
            'shipping_fee' => $request->input('shipping_fee', 0),
            'payment_method' => $request->input('payment_method', 'cod_talangan'),
        ];

        $process = $this->usecase->createFromConversation(data: $data, umkmId: $user->id);

        if ($process['success'] ?? false) {
            return redirect()
                ->route('app.conversations.show', $penggunaId)
                ->with('success', 'Pesanan berhasil dibuat.');
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('error', $process['message'] ?? ResponseConst::DEFAULT_ERROR_MESSAGE);
    }

    /**
     * Pengguna confirms an order: tunggu_konfirm → cari_driver.
     */
    public function confirm(Request $request, int $orderId): RedirectResponse
    {
        $user = auth()->user();

        // Security: verify order exists and belongs to the authenticated pengguna
        $order = \Illuminate\Support\Facades\DB::table(\App\Constants\DatabaseConst::ORDER())
            ->where('id', $orderId)
            ->first();

        if (! $order) {
            abort(404, 'Pesanan tidak ditemukan.');
        }

        if ((int) $order->pengguna_id !== $user->id) {
            abort(403, 'Akses ditolak.');
        }

        $process = $this->usecase->confirmByPengguna(orderId: $orderId, penggunaId: $user->id);

        if ($process['success'] ?? false) {
            // Securely derive peerId (UMKM ID) from the order record
            $peerId = (int) $order->umkm_id;

            return redirect()
                ->route('app.conversations.show', $peerId)
                ->with('success', 'Pesanan dikonfirmasi.');
        }

        return redirect()
            ->back()
            ->with('error', $process['message'] ?? ResponseConst::DEFAULT_ERROR_MESSAGE);
    }
}
