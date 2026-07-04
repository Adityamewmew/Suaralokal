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
     * Uses the PostgreSQL cube + earthdistance extensions for a great-circle
     * radius search in place of a full PostGIS geometry column. Distance is the
     * exact earth_distance (meters) divided to km; ordering uses the cube <->
     * operator so the umkm_profiles_ll_to_earth_gist_idx GiST index can serve
     * the nearest-neighbour sort as the dataset grows.
     *
     * @param  array{latitude?: mixed, longitude?: mixed, radius?: mixed, keyword?: string|null}  $filter
     */
    public function findNearby(array $filter = []): array
    {
        try {
            $lat = (float) ($filter['latitude'] ?? 0);
            $lng = (float) ($filter['longitude'] ?? 0);
            $radiusKm = (float) ($filter['radius'] ?? 10);
            $radiusMeters = $radiusKm * 1000;
            $keyword = is_string($filter['keyword'] ?? null) && $filter['keyword'] !== '' ? $filter['keyword'] : null;

            // Floats are pre-casted, safe to inline as the SQL origin point.
            $origin = 'll_to_earth('.$lat.', '.$lng.')';
            $distanceKm = 'round((earth_distance('.$origin.', ll_to_earth(up.latitude, up.longitude)) / 1000)::numeric, 2)';

            $query = DB::table(DatabaseConst::UMKM_PROFILE().' as up')
                ->join(DatabaseConst::USER().' as u', 'up.user_id', '=', 'u.id')
                ->select('up.*', 'u.name', 'u.phone', DB::raw($distanceKm.' as distance'))
                ->where('up.is_open', true)
                ->whereNotNull('up.latitude')
                ->whereNotNull('up.longitude')
                ->whereRaw('earth_distance('.$origin.', ll_to_earth(up.latitude, up.longitude)) <= ?', [$radiusMeters])
                ->when($keyword, function ($query, $keyword) {
                    return $query->where(function ($q) use ($keyword) {
                        $q->where('up.store_name', 'like', '%'.$keyword.'%')
                            ->orWhere('up.description', 'like', '%'.$keyword.'%');
                    });
                })
                ->orderByRaw('ll_to_earth(up.latitude, up.longitude) <-> '.$origin);

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
