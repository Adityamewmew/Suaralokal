@extends('app.layout')

@section('title', $page['title'])

@section('content')
<div class="space-y-4">
    <h2 class="text-lg font-bold text-gray-800">🛵 {{ $page['title'] }}</h2>

    @forelse ($orders as $order)
        @php
            $statusLabels = [
                'dijemput' => 'Menuju Toko',
                'diantar' => 'Sedang Diantar',
                'selesai' => 'Selesai',
            ];
            $statusColors = [
                'dijemput' => 'bg-indigo-100 text-indigo-700',
                'diantar' => 'bg-purple-100 text-purple-700',
                'selesai' => 'bg-emerald-100 text-emerald-700',
            ];
            $grandTotal = (float) $order->total_items_price + (float) $order->shipping_fee;
        @endphp

        <a href="{{ route('driver.orders.detail', $order->id) }}"
           class="block bg-white border border-gray-200 rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-2">
                <div>
                    <span class="text-sm font-bold text-gray-800">#{{ $order->id }}</span>
                    <span class="text-xs text-gray-400 ml-1">{{ \Carbon\Carbon::parse($order->created_at)->diffForHumans() }}</span>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $statusColors[$order->order_status] ?? 'bg-gray-100 text-gray-600' }}">
                    {{ $statusLabels[$order->order_status] ?? $order->order_status }}
                </span>
            </div>

            <div class="mb-2">
                <div class="text-sm font-semibold text-gray-700">{{ $order->store_name }}</div>
                <div class="text-xs text-gray-400 truncate">📍 {{ $order->store_address }}</div>
            </div>

            <div class="flex items-center justify-between text-xs">
                <span class="text-gray-500">Pelanggan: <span class="font-medium text-gray-700">{{ $order->pengguna_name }}</span></span>
                <span class="font-bold text-emerald-600">Rp{{ number_format($grandTotal, 0, ',', '.') }}</span>
            </div>

            <div class="mt-2">
                <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $order->payment_method === 'cod_talangan' ? 'bg-orange-100 text-orange-700' : 'bg-sky-100 text-sky-700' }}">
                    {{ $order->payment_method === 'cod_talangan' ? 'COD Talangan' : 'Non-Tunai' }}
                </span>
            </div>
        </a>
    @empty
        <div class="text-center py-12">
            <p class="text-sm text-gray-400">Belum ada pesanan yang ditugaskan.</p>
        </div>
    @endforelse
</div>
@endsection
