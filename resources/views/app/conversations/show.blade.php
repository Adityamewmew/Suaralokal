@extends('app.layout')

@section('title', $page['title'])
@section('hide_bottom_nav', true)

@section('content')
<div class="flex flex-col h-[calc(100vh-3.5rem)]">
    <div class="flex-1 overflow-y-auto space-y-2 py-3" id="messages-box" data-conversation="{{ $conversation->id }}" data-me="{{ auth()->user()->id }}">
        @forelse ($messages as $msg)
            @php $mine = (int) $msg->sender_id === (int) auth()->user()->id; @endphp
            <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                <div class="{{ $mine ? 'bg-emerald-600 text-white' : 'bg-white border border-gray-200 text-gray-800' }} rounded-2xl px-3 py-2 max-w-[80%] text-sm shadow-sm">
                    @unless ($mine)
                        <div class="text-xs font-semibold mb-0.5 text-emerald-600">{{ $msg->sender_name }}</div>
                    @endunless
                    {{ $msg->content }}
                </div>
            </div>
        @empty
            <p class="text-center text-sm text-gray-400 mt-8">Mulai percakapan dengan toko ini.</p>
        @endforelse

        {{-- Invoice cards for orders in this conversation --}}
        @foreach ($orders as $order)
            @include('app.orders._invoice-card', ['order' => $order, 'peerId' => $peerId])
        @endforeach
    </div>

    {{-- UMKM: Order creation form (toggle visibility) --}}
    @if (auth()->user()->role === 'umkm')
        <div id="order-form-toggle" class="bg-white border-t border-gray-200 px-3 py-2">
            <button type="button" id="btn-toggle-order-form"
                    class="w-full bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold py-2 rounded-lg transition-colors">
                🧾 Buat Pesanan
            </button>
        </div>

        <div id="order-form-container" class="hidden bg-white border-t border-gray-200 p-3 overflow-y-auto max-h-[60vh]">
            <form method="POST" action="{{ route('app.orders.store', $conversation->id) }}" id="create-order-form">
                @csrf
                <input type="hidden" name="pengguna_id" value="{{ $conversation->pengguna_id }}">
                <input type="hidden" name="peer_id" value="{{ $peerId }}">

                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-gray-800">Buat Tagihan</h3>
                    <button type="button" id="btn-close-order-form" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                {{-- Dynamic items --}}
                <div id="items-container">
                    <div class="item-row flex gap-2 mb-2 items-end">
                        <div class="flex-1">
                            <label class="text-xs text-gray-500">Nama Item</label>
                            <input type="text" name="item_name[]" required
                                   class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>
                        <div class="w-16">
                            <label class="text-xs text-gray-500">Qty</label>
                            <input type="number" name="item_quantity[]" value="1" min="1" required
                                   class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>
                        <div class="w-28">
                            <label class="text-xs text-gray-500">Harga</label>
                            <input type="number" name="item_price[]" value="0" min="0" required
                                   class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>
                    </div>
                </div>

                <button type="button" id="btn-add-item" class="text-xs text-emerald-600 hover:text-emerald-700 font-medium mb-3">+ Tambah Item</button>

                {{-- Shipping fee --}}
                <div class="mb-3">
                    <label class="text-xs text-gray-500">Ongkos Kirim (Rp)</label>
                    <input type="number" name="shipping_fee" value="0" min="0" required
                           class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                {{-- Payment method --}}
                <div class="mb-3">
                    <label class="text-xs text-gray-500">Metode Pembayaran</label>
                    <select name="payment_method" required
                            class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                        <option value="cod_talangan">COD Talangan (max Rp100.000)</option>
                        <option value="non_tunai">Non-Tunai</option>
                    </select>
                </div>

                <button type="submit"
                        class="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold py-2 rounded-lg transition-colors">
                    Kirim Tagihan
                </button>
            </form>
        </div>
    @endif

    {{-- Composer --}}
    <form id="chat-form" class="bg-white border-t border-gray-200 p-3 flex gap-2 items-end safe-area-bottom">
        @csrf
        <input type="hidden" name="peerId" value="{{ $peerId }}">
        <input type="hidden" name="conversation_id" value="{{ $conversation->id }}">
        <textarea name="content" id="content" rows="1" placeholder="Tulis pesan..."
                  class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Kirim</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var box = document.getElementById('messages-box');
    var form = document.getElementById('chat-form');
    var conversationId = box.dataset.conversation;
    var me = parseInt(box.dataset.me, 10);
    var token = document.querySelector('meta[name="csrf-token"]').content;
    var pollUrl = "{{ route('app.conversations.messages.index', $conversation->id) }}";
    var postUrl = "{{ route('app.conversations.messages.store', $conversation->id) }}";

    function scrollBottom() { box.scrollTop = box.scrollHeight; }
    scrollBottom();

    function escapeHtml(s) {
        var d = document.createElement('div'); d.textContent = s; return d.innerHTML;
    }

    function bubble(msg) {
        var mine = parseInt(msg.sender_id, 10) === me;
        var wrap = mine ? 'justify-end' : 'justify-start';
        var color = mine ? 'bg-emerald-600 text-white' : 'bg-white border border-gray-200 text-gray-800';
        return '<div class="flex ' + wrap + '">'
            + '<div class="' + color + ' rounded-2xl px-3 py-2 max-w-[80%] text-sm shadow-sm">'
            + (!mine ? '<div class="text-xs font-semibold mb-0.5 ' + (mine ? 'text-emerald-100' : 'text-emerald-600') + '">' + escapeHtml(msg.sender_name) + '</div>' : '')
            + escapeHtml(msg.content)
            + '</div></div>';
    }

    // Real-time SSE updates for order status changes
    if (typeof EventSource !== 'undefined') {
        var sseSource = new EventSource("{{ route('app.sse.orders') }}");
        sseSource.onmessage = function (event) {
            try {
                var data = JSON.parse(event.data);
                if (data && data.order_id) {
                    // Reload page to instantly update invoice status cards in real-time
                    window.location.reload();
                }
            } catch (e) {}
        };
    }

    // 4s polling fallback for messages
    var lastId = @count($messages) > 0 ? ({{ collect($messages)->last()->id ?? 0 }}) : 0;
    setInterval(function () {
        fetch(pollUrl, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                (res.data && res.data.list ? res.data.list : []).forEach(function (msg) {
                    if (parseInt(msg.id, 10) <= lastId) { return; }
                    lastId = parseInt(msg.id, 10);
                    box.insertAdjacentHTML('beforeend', bubble(msg));
                    scrollBottom();
                });
            }).catch(function () {});
    }, 4000);

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var content = document.getElementById('content');
        if (!content.value.trim()) { return; }
        fetch(postUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
            body: JSON.stringify({ content: content.value, peerId: "{{ $peerId }}" })
        }).then(function (r) { return r.json(); }).then(function (res) {
            if (res.success) {
                content.value = '';
                // Poll loop will render the new message from the other side; render own immediately.
                if (res.data) {
                    lastId = parseInt(res.data.id, 10);
                    box.insertAdjacentHTML('beforeend', bubble(res.data));
                    scrollBottom();
                }
            }
        }).catch(function () {});
    });

    // Order form toggle (UMKM only)
    var toggleBtn = document.getElementById('btn-toggle-order-form');
    var closeBtn = document.getElementById('btn-close-order-form');
    var orderContainer = document.getElementById('order-form-container');
    var orderToggle = document.getElementById('order-form-toggle');

    if (toggleBtn && orderContainer) {
        toggleBtn.addEventListener('click', function () {
            orderContainer.classList.remove('hidden');
            orderToggle.classList.add('hidden');
        });
        closeBtn.addEventListener('click', function () {
            orderContainer.classList.add('hidden');
            orderToggle.classList.remove('hidden');
        });
    }

    // Add item row
    var addItemBtn = document.getElementById('btn-add-item');
    if (addItemBtn) {
        addItemBtn.addEventListener('click', function () {
            var container = document.getElementById('items-container');
            var row = container.querySelector('.item-row').cloneNode(true);
            row.querySelectorAll('input').forEach(function (inp) {
                if (inp.name === 'item_name[]') inp.value = '';
                if (inp.name === 'item_quantity[]') inp.value = '1';
                if (inp.name === 'item_price[]') inp.value = '0';
            });
            container.appendChild(row);
        });
    }
})();
</script>
@endpush

