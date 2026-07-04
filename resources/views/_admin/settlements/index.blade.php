@extends('_admin._layout.app')

@section('title', $page['title'])

@section('content')
    <x-admin.page-header :title="$page['title']" subtitle="Kelola reimburse talangan dana COD dari driver untuk pesanan selesai.">
    </x-admin.page-header>

    {{-- Filter Tabs --}}
    <div class="flex gap-2 mb-4">
        <x-admin.button href="{{ route('admin.settlements.index') }}" size="sm" :color="empty($currentStatus) ? 'primary' : 'secondary'">
            Semua
        </x-admin.button>
        <x-admin.button href="{{ route('admin.settlements.index', ['status' => 'menunggu_reimburse']) }}" size="sm" :color="$currentStatus === 'menunggu_reimburse' ? 'primary' : 'secondary'">
            Menunggu Reimburse
        </x-admin.button>
        <x-admin.button href="{{ route('admin.settlements.index', ['status' => 'selesai_reimburse']) }}" size="sm" :color="$currentStatus === 'selesai_reimburse' ? 'primary' : 'secondary'">
            Selesai
        </x-admin.button>
    </div>

    <div class="bg-white dark:bg-neutral-800 rounded-2xl shadow-sm border border-gray-200 dark:border-neutral-700 overflow-hidden">
        @if (count($settlements) > 0)
            <x-admin.table.wrapper>
                <x-admin.table>
                    <x-admin.table.thead>
                        <tr>
                            <x-admin.table.th>ID</x-admin.table.th>
                            <x-admin.table.th>ID Order</x-admin.table.th>
                            <x-admin.table.th>Driver</x-admin.table.th>
                            <x-admin.table.th>Toko UMKM</x-admin.table.th>
                            <x-admin.table.th>Jumlah Reimburse</x-admin.table.th>
                            <x-admin.table.th>Status</x-admin.table.th>
                            <x-admin.table.th align="end"></x-admin.table.th>
                        </tr>
                    </x-admin.table.thead>
                    <x-admin.table.tbody>
                        @foreach ($settlements as $set)
                            <x-admin.table.tr>
                                <x-admin.table.td>
                                    <span class="font-bold text-gray-800 dark:text-neutral-200">#{{ $set->id }}</span>
                                </x-admin.table.td>
                                <x-admin.table.td>
                                    <span class="text-gray-600 dark:text-neutral-300 font-semibold">#{{ $set->order_id }}</span>
                                </x-admin.table.td>
                                <x-admin.table.td>
                                    <span class="text-gray-800 dark:text-neutral-200 font-medium">{{ $set->driver_name }}</span>
                                </x-admin.table.td>
                                <x-admin.table.td>
                                    <span class="text-gray-800 dark:text-neutral-200 font-medium">{{ $set->store_name }}</span>
                                </x-admin.table.td>
                                <x-admin.table.td>
                                    <span class="text-emerald-600 dark:text-emerald-400 font-bold">Rp{{ number_format((float) $set->amount, 0, ',', '.') }}</span>
                                </x-admin.table.td>
                                <x-admin.table.td>
                                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $set->status === 'menunggu_reimburse' ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' }}">
                                        {{ $set->status === 'menunggu_reimburse' ? 'Menunggu Reimburse' : 'Selesai' }}
                                    </span>
                                </x-admin.table.td>
                                <x-admin.table.td align="end">
                                    <x-admin.button href="{{ route('admin.settlements.detail', $set->id) }}" size="sm" color="primary">
                                        Detail
                                    </x-admin.button>
                                </x-admin.table.td>
                            </x-admin.table.tr>
                        @endforeach
                    </x-admin.table.tbody>
                </x-admin.table>
            </x-admin.table.wrapper>
        @else
            <x-admin.empty-state message="Tidak ada data reimburse talangan saat ini." />
        @endif
    </div>
@endsection
