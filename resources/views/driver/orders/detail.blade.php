@extends('app.layout')

@section('title', 'Detail Pesanan #' . $order->id)

@section('content')
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
    $paymentLabels = [
        'cod_talangan' => 'COD Talangan',
        'non_tunai' => 'Non-Tunai',
    ];
    $grandTotal = (float) $order->total_items_price + (float) $order->shipping_fee;
@endphp

<div class="space-y-4">
    {{-- Back link --}}
    <a href="{{ route('driver.orders.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-emerald-600 gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        Kembali
    </a>

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold text-gray-800">Pesanan #{{ $order->id }}</h2>
        <span class="text-xs px-2.5 py-1 rounded-full font-semibold {{ $statusColors[$order->order_status] ?? 'bg-gray-100 text-gray-600' }}">
            {{ $statusLabels[$order->order_status] ?? $order->order_status }}
        </span>
    </div>

    {{-- Store Info --}}
    <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">📍 Titik Jemput (Toko)</h3>
        <div class="text-sm font-bold text-gray-800">{{ $order->store_name }}</div>
        <div class="text-xs text-gray-500 mt-1">{{ $order->store_address }}</div>
        @if ($order->store_phone)
            <a href="tel:{{ $order->store_phone }}" class="inline-flex items-center mt-2 text-xs text-emerald-600 font-medium gap-1">
                📞 {{ $order->store_phone }}
            </a>
        @endif
    </div>

    {{-- Pengguna Info --}}
    <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">📦 Titik Antar (Pelanggan)</h3>
        <div class="text-sm font-bold text-gray-800">{{ $order->pengguna_name }}</div>
        @if ($order->pengguna_phone)
            <a href="tel:{{ $order->pengguna_phone }}" class="inline-flex items-center mt-2 text-xs text-emerald-600 font-medium gap-1">
                📞 {{ $order->pengguna_phone }}
            </a>
        @endif
    </div>

    {{-- Order Items --}}
    <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">🧾 Rincian Barang</h3>
        <div class="space-y-2">
            @foreach ($order->items as $item)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-700">{{ $item->item_name }} <span class="text-gray-400">×{{ $item->quantity }}</span></span>
                    <span class="font-medium text-gray-800">Rp{{ number_format((float) $item->price * (int) $item->quantity, 0, ',', '.') }}</span>
                </div>
            @endforeach
        </div>

        <div class="border-t border-gray-100 mt-3 pt-3 space-y-1 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-500">Total Barang</span>
                <span class="font-medium">Rp{{ number_format((float) $order->total_items_price, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Ongkos Kirim</span>
                <span class="font-medium">Rp{{ number_format((float) $order->shipping_fee, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between border-t border-gray-100 pt-2">
                <span class="font-bold text-gray-800">Grand Total</span>
                <span class="font-bold text-emerald-600">Rp{{ number_format($grandTotal, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    {{-- Payment Method --}}
    <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">💳 Metode Pembayaran</h3>
        <span class="text-sm font-semibold px-2.5 py-1 rounded-full {{ $order->payment_method === 'cod_talangan' ? 'bg-orange-100 text-orange-700' : 'bg-sky-100 text-sky-700' }}">
            {{ $paymentLabels[$order->payment_method] ?? $order->payment_method }}
        </span>
        @if ($order->payment_method === 'cod_talangan')
            <p class="text-xs text-gray-400 mt-2">
                Talangi Rp{{ number_format((float) $order->total_items_price, 0, ',', '.') }} ke toko. Tagih Rp{{ number_format($grandTotal, 0, ',', '.') }} dari pelanggan saat serah terima.
            </p>
        @else
            <p class="text-xs text-gray-400 mt-2">
                Pelanggan sudah bayar. Terima ongkir Rp{{ number_format((float) $order->shipping_fee, 0, ',', '.') }} dari toko saat jemput.
            </p>
        @endif
    </div>

    {{-- Proof of Delivery (if completed) --}}
    @if ($order->order_status === 'selesai' && $order->proof_of_delivery_url)
        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">📸 Bukti Pengiriman</h3>
            <img src="{{ asset('storage/' . $order->proof_of_delivery_url) }}" alt="Bukti pengiriman" class="w-full rounded-lg">
        </div>
    @endif

    {{-- Action Buttons --}}
    @if ($order->order_status === 'dijemput')
        <form method="POST" action="{{ route('driver.orders.pickup', $order->id) }}">
            @csrf
            <button type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-3 rounded-xl transition-colors"
                    onclick="return confirm('Konfirmasi barang sudah diambil dari toko?')">
                📦 Sudah Diambil
            </button>
        </form>
    @endif

    @if ($order->order_status === 'diantar')
        <form method="POST" action="{{ route('driver.orders.complete', $order->id) }}" enctype="multipart/form-data">
            @csrf
            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm mb-3">
                <label class="text-xs font-semibold text-gray-500 block mb-2">📸 Upload Foto Bukti Pengiriman <span class="text-red-500">*</span></label>
                <input type="file" name="proof" accept="image/*" capture="environment" required
                       class="block w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                <p class="text-xs text-gray-400 mt-1">Format: JPG, PNG, WebP. Maks 5MB.</p>
                @error('proof')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold py-3 rounded-xl transition-colors"
                    onclick="return confirm('Selesaikan pesanan ini?')">
                ✅ Selesaikan Pesanan
            </button>
        </form>
    @endif
</div>
@endsection
