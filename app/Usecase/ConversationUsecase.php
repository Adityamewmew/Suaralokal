<?php

namespace App\Usecase;

use App\Constants\DatabaseConst;
use App\Constants\ResponseConst;
use App\Http\Presenter\Response;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConversationUsecase extends Usecase
{
    public function __construct()
    {
        $this->className = __CLASS__;
    }

    /**
     * Find an existing conversation between a pengguna and an UMKM, or create it.
     * Dedup relies on the conversations_pengguna_umkm_unique index.
     *
     * @return array{success: bool, data: array}
     */
    public function findOrCreateConversation(int $penggunaId, int $umkmId): array
    {
        try {
            // Security: verify pengguna role and umkm role
            $pengguna = DB::table(DatabaseConst::USER())
                ->where('id', $penggunaId)
                ->whereNull('deleted_at')
                ->first();
            $umkm = DB::table(DatabaseConst::USER())
                ->where('id', $umkmId)
                ->whereNull('deleted_at')
                ->first();

            if (! $pengguna || $pengguna->role !== \App\Constants\UserConst::ROLE_PENGGUNA) {
                return Response::buildError(
                    code: ResponseConst::HTTP_FORBIDDEN,
                    message: 'Akses ditolak: User bukan Pengguna.'
                );
            }

            if (! $umkm || $umkm->role !== \App\Constants\UserConst::ROLE_UMKM) {
                return Response::buildError(
                    code: ResponseConst::HTTP_FORBIDDEN,
                    message: 'Akses ditolak: User bukan UMKM.'
                );
            }

            $conversation = DB::table(DatabaseConst::CONVERSATION())
                ->where('pengguna_id', $penggunaId)
                ->where('umkm_id', $umkmId)
                ->first();

            if (! $conversation) {
                $now = now();
                $id = DB::table(DatabaseConst::CONVERSATION())->insertGetId([
                    'pengguna_id' => $penggunaId,
                    'umkm_id' => $umkmId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $conversation = DB::table(DatabaseConst::CONVERSATION())->where('id', $id)->first();
            }

            return Response::buildSuccess(data: collect($conversation)->toArray());
        } catch (Exception $e) {
            Log::error(
                message: $e->getMessage(),
                context: ['method' => __METHOD__]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }

    /**
     * Get a conversation row by id.
     */
    public function getConversationById(int $conversationId): array
    {
        try {
            $conversation = DB::table(DatabaseConst::CONVERSATION())
                ->where('id', $conversationId)
                ->first();

            return Response::buildSuccess(
                data: $conversation ? collect($conversation)->toArray() : []
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
     * Get all messages of a conversation in send order.
     */
    public function getMessages(int $conversationId): array
    {
        try {
            $messages = DB::table(DatabaseConst::MESSAGE() . ' as m')
                ->join(DatabaseConst::USER() . ' as u', 'm.sender_id', '=', 'u.id')
                ->select('m.id', 'm.conversation_id', 'm.sender_id', 'u.name as sender_name', 'u.role as sender_role', 'm.content', 'm.created_at')
                ->where('m.conversation_id', $conversationId)
                ->orderBy('m.created_at', 'asc')
                ->get();

            return Response::buildSuccess(data: ['list' => $messages]);
        } catch (Exception $e) {
            Log::error(
                message: $e->getMessage(),
                context: ['method' => __METHOD__]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }

    /**
     * Persist a new message and bump the conversation timestamp.
     */
    public function sendMessage(int $conversationId, int $senderId, string $content): array
    {
        $content = trim($content);
        if ($content === '') {
            return Response::buildError(
                code: ResponseConst::HTTP_BAD_REQUEST,
                message: ResponseConst::ERROR_MESSAGE_VALIDATION
            );
        }

        DB::beginTransaction();

        try {
            $now = now();
            $id = DB::table(DatabaseConst::MESSAGE())->insertGetId([
                'conversation_id' => $conversationId,
                'sender_id' => $senderId,
                'content' => $content,
                'created_at' => $now,
            ]);

            DB::table(DatabaseConst::CONVERSATION())
                ->where('id', $conversationId)
                ->update(['updated_at' => $now]);

            DB::commit();

            $message = DB::table(DatabaseConst::MESSAGE())->where('id', $id)->first();

            return Response::buildSuccessCreated(data: collect($message)->toArray());
        } catch (Exception $e) {
            DB::rollback();

            Log::error(
                message: $e->getMessage(),
                context: ['method' => __METHOD__]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }
}
