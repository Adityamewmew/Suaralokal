<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Usecase\DiscoveryUsecase;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DiscoveryController extends Controller
{
    protected array $page = [
        'route' => 'discovery',
        'title' => 'Cari UMKM Terdekat',
    ];

    public function __construct(
        protected DiscoveryUsecase $usecase
    ) {}

    public function index(Request $request): View
    {
        $latitude = $request->get('latitude');
        $longitude = $request->get('longitude');

        $result = $this->usecase->findNearby([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'radius' => $request->get('radius', 10),
            'keyword' => $request->get('keyword'),
        ]);

        $list = $result['data']['list'] ?? [];
        $hasLocation = ! is_null($latitude) && ! is_null($longitude);

        return view('app.discovery.index', [
            'data' => $list,
            'page' => $this->page,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'hasLocation' => $hasLocation,
            'keyword' => $request->get('keyword', ''),
            'radius' => $request->get('radius', 10),
        ]);
    }
}
