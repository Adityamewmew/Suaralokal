<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SuaraLokal')</title>

    {{-- PWA --}}
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#059669">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SuaraLokal">
    <link rel="apple-touch-icon" href="/images/logo-light.png">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Safe-area padding for notched devices */
        .safe-area-top { padding-top: env(safe-area-inset-top); }
        .safe-area-bottom { padding-bottom: env(safe-area-inset-bottom); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">

    {{-- Top Navigation Bar --}}
    <header class="bg-white border-b border-gray-200 sticky top-0 z-40 safe-area-top shadow-xs">
        <div class="max-w-lg mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                @if (!request()->routeIs('app.discovery') && auth()->check())
                    @if (auth()->user()->role === 'driver')
                        <a href="{{ route('driver.orders.index') }}" class="text-gray-500 hover:text-emerald-600 transition-colors mr-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        </a>
                    @else
                        <a href="{{ route('app.discovery') }}" class="text-gray-500 hover:text-emerald-600 transition-colors mr-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        </a>
                    @endif
                @endif
                <h1 class="text-lg font-bold text-emerald-600">SuaraLokal</h1>
            </div>
            <div class="flex items-center gap-3">
                @auth
                    <span class="text-sm text-gray-500">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-red-500 hover:text-red-700">Keluar</button>
                    </form>
                @endauth
            </div>
        </div>
    </header>

    {{-- Flash Messages --}}
    <div class="max-w-lg mx-auto px-4 w-full">
        @if (session('success'))
            <div class="mt-3 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mt-3 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                {{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mt-3 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    {{-- Main Content --}}
    <main class="flex-1 max-w-lg mx-auto px-4 py-4 w-full">
        @yield('content')
    </main>

    {{-- Bottom Navigation --}}
    @hasSection('hide_bottom_nav')
    @else
    <nav class="bg-white border-t border-gray-200 sticky bottom-0 z-40 pb-safe">
        <div class="max-w-lg mx-auto px-4 py-2 flex items-center justify-around">
            @if (auth()->user()?->role === 'pengguna')
                @php $isActive = request()->routeIs('app.discovery'); @endphp
                <a href="{{ route('app.discovery') ?? '#' }}" class="flex flex-col items-center text-xs transition-colors {{ $isActive ? 'text-emerald-600 font-semibold' : 'text-gray-500 hover:text-emerald-600' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <span class="mt-0.5">Cari</span>
                </a>
            @endif
            @if (auth()->user()?->role === 'umkm')
                @php $isActive = request()->routeIs('app.umkm.profile.edit'); @endphp
                <a href="{{ route('app.umkm.profile.edit') }}" class="flex flex-col items-center text-xs transition-colors {{ $isActive ? 'text-emerald-600 font-semibold' : 'text-gray-500 hover:text-emerald-600' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0h4"></path></svg>
                    <span class="mt-0.5">Profil Toko</span>
                </a>
            @endif
            @if (auth()->user()?->role === 'driver')
                @php $isActive = request()->routeIs('driver.orders.*'); @endphp
                <a href="{{ route('driver.orders.index') }}" class="flex flex-col items-center text-xs transition-colors {{ $isActive ? 'text-emerald-600 font-semibold' : 'text-gray-500 hover:text-emerald-600' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    <span class="mt-0.5">Pesanan</span>
                </a>
            @endif
        </div>
    </nav>
    @endif

    @stack('scripts')

    {{-- Service Worker Registration --}}
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('[SW] Registered:', reg.scope))
                    .catch(err => console.warn('[SW] Registration failed:', err));
            });
        }
    </script>
</body>
</html>

