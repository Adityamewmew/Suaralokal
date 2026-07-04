<?php

namespace App\Http\Controllers\Driver;

use App\Constants\ResponseConst;
use App\Http\Controllers\Controller;
use App\Usecase\OrderUsecase;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DriverOrderController extends Controller
{
    protected array $page = [
        'route' => 'driver.orders',
        'title' => 'Pesanan Saya',
    ];

    public function __construct(
        protected OrderUsecase $usecase,
        protected \App\Usecase\NotificationUsecase $notificationUsecase
    ) {}

    /**
     * Show all orders assigned to this driver.
     */
    public function index(): View
    {
        $driverId = auth()->user()->id;
        $result = $this->usecase->getDriverOrders($driverId);
        $orders = $result['data']['list'] ?? [];

        return view('driver.orders.index', [
            'page' => $this->page,
            'orders' => $orders,
        ]);
    }

    /**
     * Show order detail with action buttons.
     */
    public function detail(int $id): View|RedirectResponse
    {
        $result = $this->usecase->getOrderDetailsForAdmin($id);

        if (! ($result['success'] ?? false) || empty($result['data'])) {
            return redirect()
                ->route('driver.orders.index')
                ->with('error', 'Pesanan tidak ditemukan.');
        }

        $order = (object) $result['data'];

        // Ensure this order belongs to the logged-in driver
        if ((int) $order->driver_id !== (int) auth()->user()->id) {
            return redirect()
                ->route('driver.orders.index')
                ->with('error', 'Anda tidak ditugaskan untuk pesanan ini.');
        }

        return view('driver.orders.detail', [
            'page' => $this->page,
            'order' => $order,
        ]);
    }

    /**
     * Mark order as picked up: dijemput → diantar.
     */
    public function pickUp(int $id): RedirectResponse
    {
        $driverId = auth()->user()->id;
        $process = $this->usecase->markPickedUp($id, $driverId);

        if ($process['success'] ?? false) {
            $this->notificationUsecase->triggerOrderUpdate($id, 'diantar');

            return redirect()
                ->route('driver.orders.detail', $id)
                ->with('success', 'Barang sudah diambil, status diubah ke diantar.');
        }

        return redirect()
            ->back()
            ->with('error', $process['message'] ?? ResponseConst::DEFAULT_ERROR_MESSAGE);
    }

    /**
     * Complete order with proof of delivery: diantar → selesai.
     */
    public function complete(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'proof' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
        ], [
            'proof.required' => 'Foto bukti pengiriman wajib diunggah.',
            'proof.image' => 'File harus berupa gambar.',
            'proof.mimes' => 'Format gambar harus jpeg, jpg, png, atau webp.',
            'proof.max' => 'Ukuran gambar maksimal 5MB.',
        ]);

        $driverId = auth()->user()->id;
        $proof = $request->file('proof');
        $process = $this->usecase->completeWithProof($id, $driverId, $proof);

        if ($process['success'] ?? false) {
            $this->notificationUsecase->triggerOrderUpdate($id, 'selesai');

            return redirect()
                ->route('driver.orders.detail', $id)
                ->with('success', 'Pesanan selesai! Bukti pengiriman tersimpan.');
        }

        return redirect()
            ->back()
            ->with('error', $process['message'] ?? ResponseConst::DEFAULT_ERROR_MESSAGE);
    }
}
