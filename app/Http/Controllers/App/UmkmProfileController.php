<?php

namespace App\Http\Controllers\App;

use App\Constants\ResponseConst;
use App\Http\Controllers\Controller;
use App\Usecase\UmkmProfileUsecase;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UmkmProfileController extends Controller
{
    protected array $page = [
        'route' => 'umkm.profile',
        'title' => 'Profil Toko',
    ];

    public function __construct(
        protected UmkmProfileUsecase $usecase
    ) {}

    public function edit(): View
    {
        $userId = auth()->user()->id;
        $result = $this->usecase->getByUserId($userId);
        $data = $result['data'] ?? [];

        return view('app.umkm.profile', [
            'data' => ! empty($data) ? (object) $data : null,
            'page' => $this->page,
        ]);
    }

    public function doSave(Request $request): RedirectResponse
    {
        $userId = auth()->user()->id;

        $process = $this->usecase->saveProfile(
            data: $request->all(),
            userId: $userId,
        );

        if ($process['success']) {
            return redirect()
                ->route('app.umkm.profile.edit')
                ->with('success', ResponseConst::SUCCESS_MESSAGE_UPDATED);
        } else {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $process['message'] ?? ResponseConst::DEFAULT_ERROR_MESSAGE);
        }
    }
}
