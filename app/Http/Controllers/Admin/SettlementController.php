<?php

namespace App\Http\Controllers\Admin;

use App\Constants\ResponseConst;
use App\Http\Controllers\Controller;
use App\Usecase\SettlementUsecase;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    protected array $page = [
        'route' => 'settlements',
        'title' => 'Reimburse Talangan COD',
    ];

    public function __construct(
        protected SettlementUsecase $usecase
    ) {}

    /**
     * Show list of settlements.
     */
    public function index(Request $request): View
    {
        $status = $request->input('status');
        $result = $this->usecase->getSettlements($status);
        $settlements = $result['data']['list'] ?? [];

        return view('_admin.settlements.index', [
            'page' => $this->page,
            'settlements' => $settlements,
            'currentStatus' => $status,
        ]);
    }

    /**
     * Show settlement details.
     */
    public function detail(int $id): View|RedirectResponse
    {
        $result = $this->usecase->getSettlementDetails($id);

        if (! ($result['success'] ?? false) || empty($result['data'])) {
            return redirect()
                ->route('admin.settlements.index')
                ->with('error', $result['message'] ?? 'Settlement tidak ditemukan.');
        }

        $settlement = (object) $result['data'];

        return view('_admin.settlements.detail', [
            'page' => $this->page,
            'settlement' => $settlement,
        ]);
    }

    /**
     * Complete reimbursement.
     */
    public function settle(int $id): RedirectResponse
    {
        $process = $this->usecase->settle($id);

        if ($process['success'] ?? false) {
            return redirect()
                ->route('admin.settlements.index')
                ->with('success', 'Reimburse talangan berhasil diselesaikan.');
        }

        return redirect()
            ->back()
            ->with('error', $process['message'] ?? ResponseConst::DEFAULT_ERROR_MESSAGE);
    }
}
