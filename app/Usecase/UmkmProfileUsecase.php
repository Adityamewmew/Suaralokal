<?php

namespace App\Usecase;

use App\Constants\DatabaseConst;
use App\Constants\ResponseConst;
use App\Http\Presenter\Response;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UmkmProfileUsecase extends Usecase
{
    public function __construct()
    {
        $this->className = __CLASS__;
    }

    /**
     * Get UMKM profile by user ID.
     */
    public function getByUserId(int $userId): array
    {
        try {
            $data = DB::table(DatabaseConst::UMKM_PROFILE())
                ->where('user_id', $userId)
                ->first();

            return Response::buildSuccess(
                data: $data ? collect($data)->toArray() : []
            );
        } catch (Exception $e) {
            Log::error(
                message: $e->getMessage(),
                context: ['method' => __METHOD__]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }

    /**
     * Save (insert or update) UMKM profile.
     */
    public function saveProfile(array $data, int $userId): array
    {
        $validator = Validator::make($data, [
            'store_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'required|string',
            'phone' => 'nullable|string|max:20',
            'category' => 'required|string|in:ringan,sedang,besar',
            'item_dimension' => 'nullable|string|in:ringan,sedang,besar',
            'is_open' => 'nullable|boolean',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $validator->validate();

        DB::beginTransaction();

        try {
            $profileData = [
                'store_name' => $data['store_name'],
                'description' => $data['description'] ?? null,
                'address' => $data['address'],
                'phone' => $data['phone'] ?? null,
                'category' => $data['category'],
                'item_dimension' => $data['item_dimension'] ?? $data['category'],
                'is_open' => ! empty($data['is_open']),
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'updated_at' => now(),
            ];

            $existing = DB::table(DatabaseConst::UMKM_PROFILE())
                ->where('user_id', $userId)
                ->first();

            if ($existing) {
                DB::table(DatabaseConst::UMKM_PROFILE())
                    ->where('user_id', $userId)
                    ->update($profileData);
            } else {
                $profileData['user_id'] = $userId;
                $profileData['created_at'] = now();

                DB::table(DatabaseConst::UMKM_PROFILE())
                    ->insert($profileData);
            }

            DB::commit();

            return Response::buildSuccess(
                message: ResponseConst::SUCCESS_MESSAGE_UPDATED
            );
        } catch (Exception $e) {
            DB::rollback();

            Log::error(
                message: $e->getMessage(),
                context: ['method' => __METHOD__]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }

    /**
     * Check if UMKM profile is complete (all required fields filled).
     */
    public function isComplete(int $userId): bool
    {
        $profile = DB::table(DatabaseConst::UMKM_PROFILE())
            ->where('user_id', $userId)
            ->first();

        if (! $profile) {
            return false;
        }

        return ! empty($profile->store_name)
            && ! empty($profile->address)
            && ! empty($profile->category)
            && ! is_null($profile->latitude)
            && ! is_null($profile->longitude);
    }
}
