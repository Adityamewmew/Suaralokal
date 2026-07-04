<?php

namespace App\Http\Controllers\App;

use App\Constants\ResponseConst;
use App\Constants\UserConst;
use App\Http\Controllers\Controller;
use App\Usecase\ConversationUsecase;
use App\Usecase\OrderUsecase;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    protected array $page = [
        'route' => 'conversations',
        'title' => 'Percakapan',
    ];

    public function __construct(
        protected ConversationUsecase $usecase,
        protected OrderUsecase $orderUsecase
    ) {}

    /**
     * Open (or create) the conversation with the peer user, then render the chat.
     */
    public function show(int $peerId): View|RedirectResponse
    {
        $user = auth()->user();

        // Check peer role validation
        $peer = \Illuminate\Support\Facades\DB::table(\App\Constants\DatabaseConst::USER())
            ->where('id', $peerId)
            ->whereNull('deleted_at')
            ->first();

        if (! $peer) {
            abort(404, 'User tidak ditemukan.');
        }

        if ($user->role === UserConst::ROLE_PENGGUNA && $peer->role !== UserConst::ROLE_UMKM) {
            abort(403, 'Akses ditolak.');
        }

        if ($user->role === UserConst::ROLE_UMKM && $peer->role !== UserConst::ROLE_PENGGUNA) {
            abort(403, 'Akses ditolak.');
        }

        // Resolve sides by role: pengguna chats with an UMKM, UMKM chats with a pengguna.
        if ($user->role === UserConst::ROLE_PENGGUNA) {
            $penggunaId = $user->id;
            $umkmId = $peerId;
        } else {
            $umkmId = $user->id;
            $penggunaId = $peerId;
        }

        $result = $this->usecase->findOrCreateConversation(penggunaId: $penggunaId, umkmId: $umkmId);

        if (! ($result['success'] ?? false) || empty($result['data'])) {
            return redirect()
                ->back()
                ->with('error', $result['message'] ?? ResponseConst::DEFAULT_ERROR_MESSAGE);
        }

        $conversation = (object) $result['data'];

        $messages = $this->usecase->getMessages(conversationId: $conversation->id);
        $orders = $this->orderUsecase->getOrdersByConversation(conversationId: $conversation->id);

        return view('app.conversations.show', [
            'page' => $this->page,
            'conversation' => $conversation,
            'messages' => $messages['data']['list'] ?? [],
            'orders' => $orders['data']['list'] ?? [],
            'peerId' => $peerId,
        ]);
    }

    /**
     * Poll new messages for a conversation (JSON, used by chat polling).
     */
    public function messages(int $conversationId): JsonResponse
    {
        if ($errorResponse = $this->ensureParticipant($conversationId)) {
            return $errorResponse;
        }

        $result = $this->usecase->getMessages(conversationId: $conversationId);

        return response()->json($result);
    }

    /**
     * Send a message.
     */
    public function sendMessage(Request $request, int $conversationId): JsonResponse|RedirectResponse
    {
        if ($errorResponse = $this->ensureParticipant($conversationId)) {
            return $errorResponse;
        }

        $userId = auth()->user()->id;

        // Derive peerId from the conversation record instead of request payload
        $convResult = $this->usecase->getConversationById($conversationId);
        $conv = $convResult['data'] ?? [];
        $peerId = (int) $conv['pengguna_id'] === $userId ? (int) $conv['umkm_id'] : (int) $conv['pengguna_id'];

        $process = $this->usecase->sendMessage(
            conversationId: $conversationId,
            senderId: $userId,
            content: (string) $request->input('content', '')
        );

        if ($request->expectsJson()) {
            return response()->json($process, $process['code'] ?? ResponseConst::HTTP_SUCCESS);
        }

        if ($process['success'] ?? false) {
            return redirect()->route('app.conversations.show', $peerId)->with('success', ResponseConst::SUCCESS_MESSAGE_CREATED);
        }

        return redirect()->back()->with('error', $process['message'] ?? ResponseConst::DEFAULT_ERROR_MESSAGE);
    }

    /**
     * Guard: only participants (pengguna or UMKM of this conversation) may poll or post.
     */
    private function ensureParticipant(int $conversationId): ?JsonResponse
    {
        $result = $this->usecase->getConversationById(conversationId: $conversationId);
        $data = $result['data'] ?? [];

        if (empty($data)) {
            return response()->json([
                'success' => false,
                'message' => ResponseConst::ERROR_MESSAGE_NOT_FOUND,
            ], ResponseConst::HTTP_NOT_FOUND);
        }

        $userId = auth()->user()->id;

        if ((int) $data['pengguna_id'] !== $userId && (int) $data['umkm_id'] !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], ResponseConst::HTTP_FORBIDDEN);
        }

        return null;
    }
}
