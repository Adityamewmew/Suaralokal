@extends('_admin._layout.app')

@section('title', 'Detail Penugasan Pesanan')

@section('content')
    <div class="flex items-center gap-x-3 mb-6">
        <a href="{{ route('admin.bangjek_orders.index') }}"
            class="py-2.5 px-3.5 inline-flex items-center gap-x-2 text-sm rounded-xl border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-800 dark:border-neutral-700 dark:text-white dark:hover:bg-neutral-700 dark:focus:bg-neutral-700 cursor-pointer">
            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round">
                <path d="m12 19-7-7 7-7" />
                <path d="M19 12H5" />
            </svg>
            Kembali
        </a>
        <div>
            <h2 class="text-xl font-bold text-gray-800 dark:text-neutral-200">
                Detail Penugasan Pesanan #{{ $order->id }}
            </h2>
            <p class="text-xs text-gray-500 dark:text-neutral-400">Silakan pilih driver untuk menugaskan pengiriman ini.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Detail Order --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Info Ringkas --}}
            <div class="bg-white dark:bg-neutral-800 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-neutral-700">
                <h3 class="text-base font-bold text-gray-800 dark:text-neutral-200 mb-4 pb-2 border-b border-gray-100 dark:border-neutral-700 flex items-center gap-2">
                    📦 Informasi Transaksi
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-400 dark:text-neutral-500 block">Pelanggan</span>
                        <span class="font-semibold text-gray-800 dark:text-neutral-200 block">{{ $order->pengguna_name }}</span>
                        <span class="text-xs text-gray-500 block">{{ $order->pengguna_phone ?? 'Tidak ada nomor telepon' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 dark:text-neutral-500 block">Toko UMKM</span>
                        <span class="font-semibold text-gray-800 dark:text-neutral-200 block">{{ $order->store_name }}</span>
                        <span class="text-xs text-gray-500 block">{{ $order->store_phone ?? 'Tidak ada nomor telepon' }}</span>
                        <span class="text-xs text-gray-400 dark:text-neutral-500 block mt-1 leading-relaxed">{{ $order->store_address }}</span>
                    </div>
                    <div class="sm:col-span-2 border-t border-gray-50 dark:border-neutral-700/50 pt-3 flex flex-wrap gap-x-8 gap-y-2">
                        <div>
                            <span class="text-gray-400 dark:text-neutral-500 text-xs block">Metode Pembayaran</span>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $order->payment_method === 'cod_talangan' ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' : 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400' }}">
                                {{ $order->payment_method === 'cod_talangan' ? 'COD Talangan' : 'Non-Tunai' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-400 dark:text-neutral-500 text-xs block">Status Order</span>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                                Menunggu Driver
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Detail Items --}}
            <div class="bg-white dark:bg-neutral-800 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-neutral-700">
                <h3 class="text-base font-bold text-gray-800 dark:text-neutral-200 mb-4 pb-2 border-b border-gray-100 dark:border-neutral-700 flex items-center gap-2">
                    📋 Rincian Belanja
                </h3>
                <div class="border border-gray-100 dark:border-neutral-700 rounded-xl overflow-hidden mb-4">
                    <x-admin.table>
                        <x-admin.table.thead>
                            <tr>
                                <x-admin.table.th>Nama Barang</x-admin.table.th>
                                <x-admin.table.th align="center">Jumlah</x-admin.table.th>
                                <x-admin.table.th align="end">Harga Satuan</x-admin.table.th>
                                <x-admin.table.th align="end">Subtotal</x-admin.table.th>
                            </tr>
                        </x-admin.table.thead>
                        <x-admin.table.tbody>
                            @foreach ($order->items as $item)
                                <x-admin.table.tr>
                                    <x-admin.table.td>
                                        <span class="font-medium text-gray-800 dark:text-neutral-200">{{ $item->item_name }}</span>
                                    </x-admin.table.td>
                                    <x-admin.table.td align="center">
                                        <span class="text-gray-600 dark:text-neutral-400">{{ $item->quantity }}</span>
                                    </x-admin.table.td>
                                    <x-admin.table.td align="end">
                                        <span class="text-gray-600 dark:text-neutral-400">Rp{{ number_format((float) $item->price, 0, ',', '.') }}</span>
                                    </x-admin.table.td>
                                    <x-admin.table.td align="end">
                                        <span class="font-semibold text-gray-800 dark:text-neutral-200">Rp{{ number_format((float) $item->price * $item->quantity, 0, ',', '.') }}</span>
                                    </x-admin.table.td>
                                </x-admin.table.tr>
                            @endforeach
                        </x-admin.table.tbody>
                    </x-admin.table>
                </div>

                {{-- Ringkasan Harga --}}
                <div class="space-y-2 text-sm max-w-xs ms-auto">
                    <div class="flex justify-between text-gray-600 dark:text-neutral-400">
                        <span>Total Barang:</span>
                        <span class="font-semibold">Rp{{ number_format((float) $order->total_items_price, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600 dark:text-neutral-400">
                        <span>Ongkos Kirim:</span>
                        <span class="font-semibold">Rp{{ number_format((float) $order->shipping_fee, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-base font-bold text-emerald-600 dark:text-emerald-400 border-t border-gray-100 dark:border-neutral-700 pt-2">
                        <span>Grand Total:</span>
                        <span>Rp{{ number_format((float) $order->total_items_price + $order->shipping_fee, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form Assignment --}}
        <div class="space-y-6">
            <div class="bg-white dark:bg-neutral-800 p-6 rounded-2xl shadow-sm border border-gray-200 dark:border-neutral-700">
                <h3 class="text-base font-bold text-gray-800 dark:text-neutral-200 mb-4 pb-2 border-b border-gray-100 dark:border-neutral-700 flex items-center gap-2">
                    🛵 Penugasan Driver
                </h3>
                
                @if (count($drivers) > 0)
                    @php
                        $driverOptions = [];
                        foreach ($drivers as $driver) {
                            $driverOptions[$driver->id] = $driver->name . ' (' . ($driver->phone ?? 'no-phone') . ')';
                        }
                    @endphp

                    <form method="POST" action="{{ route('admin.bangjek_orders.assign', $order->id) }}">
                        @csrf
                        <div class="space-y-4">
                            <x-admin.select 
                                label="Pilih Driver Aktif" 
                                name="driver_id" 
                                :options="$driverOptions" 
                                placeholder="-- Pilih Driver --" 
                                required
                            />

                            <button type="submit" 
                                class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-emerald-600 text-white hover:bg-emerald-700 focus:outline-hidden focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all dark:focus:ring-offset-neutral-850 cursor-pointer">
                                Tugaskan Pengiriman
                            </button>
                        </div>
                    </form>
                @else
                    <div class="text-center py-6">
                        <p class="text-sm text-gray-500 dark:text-neutral-400 mb-2">Tidak ada driver aktif yang terdaftar.</p>
                        <a href="{{ route('admin.users.index') }}" class="text-xs text-blue-500 hover:text-blue-600 font-semibold underline">
                            Kelola Akun Driver &rarr;
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
