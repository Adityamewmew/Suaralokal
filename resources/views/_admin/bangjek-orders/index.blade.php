@extends('_admin._layout.app')

@section('title', $page['title'])

@section('content')
    <x-admin.page-header :title="$page['title']" subtitle="Daftar pesanan yang sedang menunggu penugasan driver.">
    </x-admin.page-header>

    <div class="bg-white dark:bg-neutral-800 rounded-2xl shadow-sm border border-gray-200 dark:border-neutral-700 overflow-hidden">
        @if (count($orders) > 0)
            <x-admin.table.wrapper>
                <x-admin.table>
                    <x-admin.table.thead>
                        <tr>
                            <x-admin.table.th>ID Order</x-admin.table.th>
                            <x-admin.table.th>Pelanggan</x-admin.table.th>
                            <x-admin.table.th>Toko UMKM</x-admin.table.th>
                            <x-admin.table.th>Total Belanja</x-admin.table.th>
                            <x-admin.table.th>Ongkir</x-admin.table.th>
                            <x-admin.table.th>Metode Bayar</x-admin.table.th>
                            <x-admin.table.th align="end"></x-admin.table.th>
                        </tr>
                    </x-admin.table.thead>
                    <x-admin.table.tbody>
                        @foreach ($orders as $order)
                            <x-admin.table.tr>
                                <x-admin.table.td>
                                    <span class="font-bold text-gray-800 dark:text-neutral-200">#{{ $order->id }}</span>
                                </x-admin.table.td>
                                <x-admin.table.td>
                                    <span class="text-gray-600 dark:text-neutral-300 font-medium">{{ $order->pengguna_name }}</span>
                                </x-admin.table.td>
                                <x-admin.table.td>
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-gray-800 dark:text-neutral-200">{{ $order->store_name }}</span>
                                        <span class="text-xs text-gray-400 dark:text-neutral-500 truncate max-w-xs">{{ $order->store_address }}</span>
                                    </div>
                                </x-admin.table.td>
                                <x-admin.table.td>
                                    <span class="text-gray-800 dark:text-neutral-200 font-semibold">Rp{{ number_format((float) $order->total_items_price, 0, ',', '.') }}</span>
                                </x-admin.table.td>
                                <x-admin.table.td>
                                    <span class="text-gray-800 dark:text-neutral-200 font-semibold">Rp{{ number_format((float) $order->shipping_fee, 0, ',', '.') }}</span>
                                </x-admin.table.td>
                                <x-admin.table.td>
                                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $order->payment_method === 'cod_talangan' ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' : 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400' }}">
                                        {{ $order->payment_method === 'cod_talangan' ? 'COD Talangan' : 'Non-Tunai' }}
                                    </span>
                                </x-admin.table.td>
                                <x-admin.table.td align="end">
                                    <x-admin.button href="{{ route('admin.bangjek_orders.detail', $order->id) }}" size="sm" color="primary">
                                        Assign Driver
                                    </x-admin.button>
                                </x-admin.table.td>
                            </x-admin.table.tr>
                        @endforeach
                    </x-admin.table.tbody>
                </x-admin.table>
            </x-admin.table.wrapper>
        @else
            <x-admin.empty-state message="Tidak ada pesanan yang sedang mencari driver saat ini." />
        @endif
    </div>
@endsection
