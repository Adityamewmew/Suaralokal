<?php

namespace App\Usecase;

use App\Constants\DatabaseConst;
use App\Http\Presenter\Response;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DiscoveryUsecase extends Usecase
{
    public function __construct()
    {
        $this->className = __CLASS__;
    }

    /**
     * Find nearby open UMKM within radius, sorted by distance (km).
     *
     * @param  array{latitude?: mixed, longitude?: mixed, radius?: mixed, keyword?: string|null}  $filter
     */
    public function findNearby(array $filter = []): array
    {
        try {
            $lat = (float) ($filter['latitude'] ?? 0);
            $lng = (float) ($filter['longitude'] ?? 0);
            $radius = (float) ($filter['radius'] ?? 10);
            $keyword = is_string($filter['keyword'] ?? null) && $filter['keyword'] !== '' ? $filter['keyword'] : null;

            // ponytail: Haversine computed in SQL over lat/lng columns. Bound by whereRaw radius.
            // Upgrade path: switch to PostGIS geography + ST_DWithin / ST_DistanceSphere with a GIST index for scale.
            $haversine = '(6371 * acos(cos(radians(' . $lat . ')) * cos(radians(latitude))'
                . ' * cos(radians(longitude) - radians(' . $lng . '))'
                . ' + sin(radians(' . $lat . ')) * sin(radians(latitude))))';

            $query = DB::table(DatabaseConst::UMKM_PROFILE() . ' as up')
                ->join(DatabaseConst::USER() . ' as u', 'up.user_id', '=', 'u.id')
                ->select('up.*', 'u.name', 'u.phone', DB::raw('round((' . $haversine . ')::numeric, 2) as distance'))
                ->where('up.is_open', true)
                ->whereNotNull('up.latitude')
                ->whereNotNull('up.longitude')
                ->whereRaw($haversine . ' <= ?', [$radius])
                ->when($keyword, function ($query, $keyword) {
                    return $query->where(function ($q) use ($keyword) {
                        $q->where('up.store_name', 'like', '%' . $keyword . '%')
                            ->orWhere('up.description', 'like', '%' . $keyword . '%');
                    });
                })
                ->orderBy('distance', 'asc');

            $data = $query->get();

            return Response::buildSuccess(data: ['list' => $data]);
        } catch (Exception $e) {
            Log::error(
                message: $e->getMessage(),
                context: ['method' => __METHOD__]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }
}
