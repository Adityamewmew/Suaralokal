@extends('_admin._layout.app')

@section('title', $page['title'])

@section('content')
    <x-admin.page-header :title="$page['title']" subtitle="Detail reimbursement talangan dana COD.">
    </x-admin.page-header>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2 space-y-6">
            {{-- Settlement Info Card --}}
            <div class="bg-white dark:bg-neutral-800 rounded-2xl shadow-sm border border-gray-200 dark:border-neutral-700 p-6">
                <h3 class="text-lg font-bold text-gray-800 dark:text-neutral-200 mb-4">Informasi Reimburse</h3>
                
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-400 dark:text-neutral-500">ID Settlement</span>
                        <p class="font-bold text-gray-800 dark:text-neutral-200 mt-0.5">#{{ $settlement->id }}</p>
                    </div>
                    <div>
                        <span class="text-gray-400 dark:text-neutral-500">ID Pesanan</span>
                        <p class="font-bold text-gray-800 dark:text-neutral-200 mt-0.5">#{{ $settlement->order_id }}</p>
                    </div>
                    <div>
                        <span class="text-gray-400 dark:text-neutral-500">Mitra Driver (Talangan)</span>
                        <p class="font-semibold text-gray-800 dark:text-neutral-200 mt-0.5">{{ $settlement->driver_name }}</p>
                    </div>
                    <div>
                        <span class="text-gray-400 dark:text-neutral-500">Toko UMKM (Penerima Cash)</span>
                        <p class="font-semibold text-gray-800 dark:text-neutral-200 mt-0.5">{{ $settlement->store_name }}</p>
                    </div>
                    <div>
                        <span class="text-gray-400 dark:text-neutral-500">Pelanggan (Pembayar COD)</span>
                        <p class="font-semibold text-gray-800 dark:text-neutral-200 mt-0.5">{{ $settlement->pengguna_name }}</p>
                    </div>
                    <div>
                        <span class="text-gray-400 dark:text-neutral-500">Metode Pembayaran</span>
                        <p class="font-medium text-orange-600 mt-0.5">COD Talangan</p>
                    </div>
                </div>
            </div>

            {{-- Financial Breakdown --}}
            <div class="bg-white dark:bg-neutral-800 rounded-2xl shadow-sm border border-gray-200 dark:border-neutral-700 p-6">
                <h3 class="text-lg font-bold text-gray-800 dark:text-neutral-200 mb-4">Rincian Keuangan</h3>
                
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-neutral-400">Total Harga Barang</span>
                        <span class="font-semibold text-gray-800 dark:text-neutral-200">Rp{{ number_format((float) $settlement->total_items_price, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-neutral-400">Ongkos Kirim</span>
                        <span class="font-semibold text-gray-800 dark:text-neutral-200">Rp{{ number_format((float) $settlement->shipping_fee, 0, ',', '.') }}</span>
                    </div>
                    <div class="border-t border-gray-200 dark:border-neutral-700 pt-3 flex justify-between">
                        <span class="font-bold text-gray-800 dark:text-neutral-200">Jumlah Reimburse (Grand Total)</span>
                        <span class="font-extrabold text-emerald-600 dark:text-emerald-400 text-lg">Rp{{ number_format((float) $settlement->amount, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- Audit Trail (Riwayat Pesanan) --}}
            <div class="bg-white dark:bg-neutral-800 rounded-2xl shadow-sm border border-gray-200 dark:border-neutral-700 p-6">
                <h3 class="text-lg font-bold text-gray-800 dark:text-neutral-200 mb-4">Riwayat Aktivitas Pesanan</h3>
                
                @if (count($events) > 0)
                    <div class="flow-root">
                        <ul role="list" class="-mb-8">
                            @foreach ($events as $idx => $event)
                                <li>
                                    <div class="relative pb-8">
                                        @if ($idx !== count($events) - 1)
                                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-neutral-700" aria-hidden="true"></span>
                                        @endif
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-neutral-800 {{ 
                                                    $event->event_type === 'create' ? 'bg-blue-100 text-blue-600' : (
                                                    $event->event_type === 'confirm' ? 'bg-sky-100 text-sky-600' : (
                                                    $event->event_type === 'assign' ? 'bg-yellow-100 text-yellow-600' : (
                                                    $event->event_type === 'pickup' ? 'bg-purple-100 text-purple-600' : (
                                                    $event->event_type === 'complete' ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-100 text-gray-600'
                                                    ))))
                                                }}">
                                                    @if ($event->event_type === 'create') 📝 @elseif ($event->event_type === 'confirm') ✅ @elseif ($event->event_type === 'assign') 👤 @elseif ($event->event_type === 'pickup') 📦 @elseif ($event->event_type === 'complete') 🏁 @else 💰 @endif
                                                </span>
                                            </div>
                                            <div class="flex-1 min-w-0 pt-1.5 flex justify-between space-x-4">
                                                <div>
                                                    <p class="text-sm font-semibold text-gray-800 dark:text-neutral-200">
                                                        Event: <span class="capitalize text-emerald-600 dark:text-emerald-400">{{ $event->event_type }}</span>
                                                    </p>
                                                    @if ($event->payload)
                                                        @php $payload = json_decode($event->payload, true); @endphp
                                                        @if ($payload)
                                                            <div class="mt-1 text-xs text-gray-500 dark:text-neutral-400 bg-gray-50 dark:bg-neutral-900/50 p-2 rounded-lg font-mono">
                                                                @foreach ($payload as $key => $val)
                                                                    <div><span class="font-bold text-gray-600 dark:text-neutral-500">{{ $key }}:</span> {{ is_array($val) ? json_encode($val) : $val }}</div>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    @endif
                                                </div>
                                                <div class="text-right text-xs whitespace-nowrap text-gray-400 dark:text-neutral-500">
                                                    <time datetime="{{ $event->created_at }}">{{ $event->created_at }}</time>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <p class="text-xs text-gray-400 dark:text-neutral-500 text-center py-4">Belum ada riwayat aktivitas tercatat.</p>
                @endif
            </div>
        </div>

        {{-- Actions Card --}}
        <div>
            <div class="bg-white dark:bg-neutral-800 rounded-2xl shadow-sm border border-gray-200 dark:border-neutral-700 p-6 sticky top-6 space-y-4">
                <div>
                    <span class="text-xs text-gray-400 dark:text-neutral-500 uppercase font-bold tracking-wider">Status Saat Ini</span>
                    <div class="mt-1">
                        <span class="inline-flex text-sm font-bold px-3 py-1 rounded-full {{ $settlement->status === 'menunggu_reimburse' ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' }}">
                            {{ $settlement->status === 'menunggu_reimburse' ? 'Menunggu Reimburse' : 'Selesai' }}
                        </span>
                    </div>
                </div>

                @if ($settlement->settled_at)
                    <div>
                        <span class="text-xs text-gray-400 dark:text-neutral-500">Diselesaikan pada</span>
                        <p class="text-sm font-medium text-gray-700 dark:text-neutral-300 mt-0.5">{{ $settlement->settled_at }}</p>
                    </div>
                @endif

                <div class="pt-4 border-t border-gray-100 dark:border-neutral-700">
                    @if ($settlement->status === 'menunggu_reimburse')
                        <form method="POST" action="{{ route('admin.settlements.settle', $settlement->id) }}">
                            @csrf
                            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-4 rounded-xl transition-all shadow-sm active:scale-[0.98] text-sm">
                                Selesaikan Reimburse
                            </button>
                        </form>
                    @else
                        <button disabled class="w-full bg-gray-100 dark:bg-neutral-700 text-gray-400 dark:text-neutral-500 font-bold py-2.5 px-4 rounded-xl cursor-not-allowed text-sm">
                            Selesai Direimburse
                        </button>
                    @endif
                </div>

                <a href="{{ route('admin.settlements.index') }}" class="block text-center text-xs font-semibold text-gray-500 hover:text-emerald-600 transition-colors">
                    Kembali ke Daftar
                </a>
            </div>
        </div>
    </div>
@endsection
