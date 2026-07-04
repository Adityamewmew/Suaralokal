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
