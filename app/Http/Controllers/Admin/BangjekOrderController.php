<?php

namespace App\Http\Controllers\Admin;

use App\Constants\ResponseConst;
use App\Http\Controllers\Controller;
use App\Usecase\OrderUsecase;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BangjekOrderController extends Controller
{
    protected array $page = [
        'route' => 'bangjek-orders',
        'title' => 'Antrean Pesanan Bangjek',
    ];

    public function __construct(
        protected OrderUsecase $usecase,
        protected \App\Usecase\NotificationUsecase $notificationUsecase
    ) {}

    /**
     * Show the queue of orders with status 'cari_driver'.
     */
    public function index(Request $request): View
    {
        $ordersResult = $this->usecase->getWaitingDriverOrders();
        $orders = $ordersResult['data']['list'] ?? [];

        return view('_admin.bangjek-orders.index', [
            'page' => $this->page,
            'orders' => $orders,
        ]);
    }

    /**
     * Show the order assignment detail page.
     */
    public function detail(int $id): View|RedirectResponse
    {
        $orderResult = $this->usecase->getOrderDetailsForAdmin($id);

        if (! ($orderResult['success'] ?? false) || empty($orderResult['data'])) {
            return redirect()
                ->route('admin.bangjek_orders.index')
                ->with('error', $orderResult['message'] ?? 'Pesanan tidak ditemukan.');
        }

        $order = (object) $orderResult['data'];
        $driversResult = $this->usecase->getActiveDrivers();
        $drivers = $driversResult['data']['list'] ?? [];

        return view('_admin.bangjek-orders.detail', [
            'page' => $this->page,
            'order' => $order,
            'drivers' => $drivers,
        ]);
    }

    /**
     * Assign a driver to the order.
     */
    public function assign(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'driver_id' => 'required|integer',
        ]);

        $driverId = (int) $request->input('driver_id');
        $process = $this->usecase->assignDriver($id, $driverId);

        if ($process['success'] ?? false) {
            $this->notificationUsecase->triggerOrderUpdate($id, 'dijemput');

            return redirect()
                ->route('admin.bangjek_orders.index')
                ->with('success', 'Driver berhasil ditugaskan.');
        }

        return redirect()
            ->back()
            ->with('error', $process['message'] ?? ResponseConst::DEFAULT_ERROR_MESSAGE);
    }
}
