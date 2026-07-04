{{-- Invoice card rendered inside chat --}}
@php
    $statusLabels = [
        'tunggu_konfirm' => 'Tunggu Konfirmasi',
        'cari_driver' => 'Cari Driver',
        'dijemput' => 'Dijemput',
        'diantar' => 'Diantar',
        'selesai' => 'Selesai',
    ];
    $statusColors = [
        'tunggu_konfirm' => 'bg-yellow-100 text-yellow-700',
        'cari_driver' => 'bg-blue-100 text-blue-700',
        'dijemput' => 'bg-indigo-100 text-indigo-700',
        'diantar' => 'bg-purple-100 text-purple-700',
        'selesai' => 'bg-emerald-100 text-emerald-700',
    ];
    $paymentLabels = [
        'cod_talangan' => 'COD Talangan',
        'non_tunai' => 'Non-Tunai',
    ];

    $grandTotal = (float) $order->total_items_price + (float) $order->shipping_fee;
@endphp

<div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 my-3" id="invoice-{{ $order->id }}">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-bold text-gray-800">🧾 Tagihan #{{ $order->id }}</h3>
        <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $statusColors[$order->order_status] ?? 'bg-gray-100 text-gray-600' }}">
            {{ $statusLabels[$order->order_status] ?? $order->order_status }}
        </span>
    </div>

    {{-- Items table --}}
    <div class="border border-gray-100 rounded-lg overflow-hidden mb-3">
        <table class="w-full text-xs">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-3 py-1.5 text-gray-500 font-medium">Item</th>
                    <th class="text-center px-2 py-1.5 text-gray-500 font-medium">Qty</th>
                    <th class="text-right px-3 py-1.5 text-gray-500 font-medium">Harga</th>
                    <th class="text-right px-3 py-1.5 text-gray-500 font-medium">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr class="border-t border-gray-50">
                        <td class="px-3 py-1.5 text-gray-700">{{ $item->item_name }}</td>
                        <td class="text-center px-2 py-1.5 text-gray-600">{{ $item->quantity }}</td>
                        <td class="text-right px-3 py-1.5 text-gray-600">Rp{{ number_format((float) $item->price, 0, ',', '.') }}</td>
                        <td class="text-right px-3 py-1.5 text-gray-700 font-medium">Rp{{ number_format((float) $item->price * (int) $item->quantity, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Totals --}}
    <div class="space-y-1 text-xs mb-3">
        <div class="flex justify-between">
            <span class="text-gray-500">Total Barang</span>
            <span class="text-gray-700 font-medium">Rp{{ number_format((float) $order->total_items_price, 0, ',', '.') }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-500">Ongkos Kirim</span>
            <span class="text-gray-700 font-medium">Rp{{ number_format((float) $order->shipping_fee, 0, ',', '.') }}</span>
        </div>
        <div class="flex justify-between border-t border-gray-200 pt-1">
            <span class="text-gray-800 font-bold">Grand Total</span>
            <span class="text-emerald-600 font-bold">Rp{{ number_format($grandTotal, 0, ',', '.') }}</span>
        </div>
    </div>

    {{-- Payment method --}}
    <div class="flex items-center justify-between mb-3">
        <span class="text-xs text-gray-500">Metode Bayar</span>
        <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $order->payment_method === 'cod_talangan' ? 'bg-orange-100 text-orange-700' : 'bg-sky-100 text-sky-700' }}">
            {{ $paymentLabels[$order->payment_method] ?? $order->payment_method }}
        </span>
    </div>

    {{-- Confirm button: only pengguna, only tunggu_konfirm --}}
    @if (auth()->user()->role === 'pengguna' && $order->order_status === 'tunggu_konfirm')
        <form method="POST" action="{{ route('app.orders.confirm', $order->id) }}">
            @csrf
            <input type="hidden" name="peer_id" value="{{ $peerId ?? '' }}">
            <button type="submit"
                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold py-2 rounded-lg transition-colors"
                    onclick="return confirm('Konfirmasi pesanan ini?')">
                ✅ Konfirmasi Pesanan
            </button>
        </form>
    @endif
</div>
