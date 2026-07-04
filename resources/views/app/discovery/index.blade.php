@extends('app.layout')

@section('title', $page['title'])

@section('content')
<div class="space-y-4">
    <div>
        <h2 class="text-xl font-bold text-gray-800">{{ $page['title'] }}</h2>
        <p class="text-sm text-gray-500 mt-1">Temukan usaha terdekat di sekitar lokasi Anda.</p>
    </div>

    {{-- Location + keyword filter --}}
    <form id="discovery-form" method="GET" action="{{ route('app.discovery') }}" class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 space-y-3">
        <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', $latitude) }}">
        <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', $longitude) }}">
        <input type="hidden" name="radius" value="{{ $radius }}">

        <div class="flex gap-2">
            <input type="text" name="keyword" value="{{ $keyword }}" placeholder="Cari nama/deskripsi toko"
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 rounded-lg">Cari</button>
        </div>

        <button type="button" id="locate-btn"
                class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold py-2 px-4 rounded-lg flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            Gunakan Lokasi Saya
        </button>
        <p id="locate-status" class="text-xs text-gray-500 hidden"></p>

        @if (! $hasLocation)
            <p class="text-xs text-amber-600">Izinkan lokasi atau masukkan titik koordinat manual di bawah untuk mulai mencari.</p>
            <div class="grid grid-cols-2 gap-2">
                <input type="number" step="any" name="manual_lat" placeholder="Latitude manual" class="px-2 py-1.5 border border-gray-300 rounded-lg text-xs" @if(!is_numeric($latitude)) value="{{ old('manual_lat') }}" @endif>
                <input type="number" step="any" name="manual_lng" placeholder="Longitude manual" class="px-2 py-1.5 border border-gray-300 rounded-lg text-xs">
            </div>
        @endif
    </form>

    @if ($hasLocation)
        {{-- ponytail: Leaflet loaded from CDN here; bundle + offline cache via Vite plugin in PWA task. --}}
        <div id="map" class="rounded-xl border border-gray-200 overflow-hidden h-56 bg-gray-100"></div>
    @endif

    {{-- UMKM cards --}}
    <div class="space-y-3">
        @forelse ($data as $umkm)
            <a href="{{ route('app.conversations.show', $umkm->user_id) }}"
               class="block bg-white rounded-xl border border-gray-200 shadow-sm p-4 active:bg-gray-50" data-lat="{{ $umkm->latitude }}" data-lng="{{ $umkm->longitude }}">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-semibold text-gray-800">{{ $umkm->store_name }}</h3>
                        @if (! empty($umkm->description))
                            <p class="text-sm text-gray-500 line-clamp-2 mt-0.5">{{ $umkm->description }}</p>
                        @endif
                    </div>
                    <span class="shrink-0 ml-3 text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-full">{{ number_format($umkm->distance, 1) }} km</span>
                </div>
                <div class="flex items-center gap-3 mt-2 text-xs text-gray-400">
                    <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full {{ $umkm->is_open ? 'bg-emerald-500' : 'bg-gray-300' }}"></span>{{ $umkm->is_open ? 'Buka' : 'Tutup' }}</span>
                    @if (! empty($umkm->category))<span class="capitalize">{{ $umkm->category }}</span>@endif
                </div>
            </a>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 text-center text-sm text-gray-500">
                @if ($hasLocation)
                    Belum ada UMKM terbuka dalam radius {{ $radius }} km.
                @else
                    Aktifkan lokasi untuk melihat UMKM terdekat.
                @endif
            </div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('discovery-form');
    var latInput = document.getElementById('latitude');
    var lngInput = document.getElementById('longitude');
    var btn = document.getElementById('locate-btn');
    var status = document.getElementById('locate-status');

    // ponytail: vanilla JS, no Alpine needed for a one-button geolocation flow.
    btn && btn.addEventListener('click', function () {
        if (!navigator.geolocation) {
            status.textContent = 'Browser tidak mendukung lokasi. Isi koordinat manual.';
            status.classList.remove('hidden');
            return;
        }
        status.textContent = 'Mengambil lokasi...';
        status.classList.remove('hidden');
        navigator.geolocation.getCurrentPosition(function (pos) {
            latInput.value = pos.coords.latitude;
            lngInput.value = pos.coords.longitude;
            form.submit();
        }, function () {
            status.textContent = 'Izin lokasi ditolak. Isi koordinat manual lalu tekan Cari.';
        }, { enableHighAccuracy: true, timeout: 8000 });
    });

    @if ($hasLocation && count($data) > 0)
    // ponytail: Leaflet from CDN; swap for bundled asset in PWA task.
    var s = document.createElement('script');
    s.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    s.onload = function () {
        var first = document.querySelector('[data-lat]');
        var lat = parseFloat(first.dataset.lat), lng = parseFloat(first.dataset.lng);
        var map = L.map('map').setView([lat, lng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
        document.querySelectorAll('[data-lat]').forEach(function (el) {
            var mlat = parseFloat(el.dataset.lat), mlng = parseFloat(el.dataset.lng);
            if (!isNaN(mlat) && !isNaN(mlng)) L.marker([mlat, mlng]).addTo(map).bindPopup(el.querySelector('h3').textContent);
        });
    };
    document.head.appendChild(s);
    var link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    document.head.appendChild(link);
    @endif
})();
</script>
@endpush
