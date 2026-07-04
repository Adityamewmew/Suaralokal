@php
    use App\Constants\UserConst;
    $user = Auth::user();
@endphp

@if ($user && in_array($user->role, [UserConst::ROLE_OJEK_ADMIN, UserConst::ROLE_SUPERADMIN]))
    @php
        $isActive = request()->routeIs('admin.bangjek_orders.*');
        $activeClass = 'bg-linear-to-r from-blue-50 to-indigo-50 text-blue-700 border-blue-200 dark:from-blue-900/20 dark:to-indigo-900/20 dark:text-blue-400 dark:border-blue-800';
        $inactiveClass = 'text-gray-700 hover:bg-linear-to-r hover:from-gray-100 hover:to-gray-50 border-transparent hover:border-gray-200 dark:text-neutral-300 dark:hover:from-neutral-700/50 dark:hover:to-neutral-800/50 dark:hover:border-neutral-600';
    @endphp
    <li class="mb-1.5">
        <a navigate
            class="group flex items-center gap-x-3 py-2.5 px-3.5 {{ $isActive ? $activeClass : $inactiveClass }} text-sm font-semibold rounded-xl border transition-all duration-200"
            href="{{ route('admin.bangjek_orders.index') }}">
            <span class="relative flex items-center justify-center size-5">
                🛵
            </span>
            <span class="relative">Antrean Bangjek</span>
        </a>
    </li>

    @php
        $isActiveSettlement = request()->routeIs('admin.settlements.*');
    @endphp
    <li class="mb-1.5">
        <a navigate
            class="group flex items-center gap-x-3 py-2.5 px-3.5 {{ $isActiveSettlement ? $activeClass : $inactiveClass }} text-sm font-semibold rounded-xl border transition-all duration-200"
            href="{{ route('admin.settlements.index') }}">
            <span class="relative flex items-center justify-center size-5">
                💰
            </span>
            <span class="relative">Reimburse Talangan</span>
        </a>
    </li>
@endif

@if (!empty($sidebarMenus['utama']))
    @foreach ($sidebarMenus['utama'] as $menu)
        @include('_admin._layout.sidebar._menu_item', ['menu' => $menu])
    @endforeach
@else
    @if (!($user && in_array($user->role, [UserConst::ROLE_OJEK_ADMIN, UserConst::ROLE_SUPERADMIN])))
        {{-- Fallback: no menus configured for this role --}}
        <li class="px-3 py-4">
            <p class="text-xs text-gray-400 dark:text-neutral-500 text-center">Tidak ada menu tersedia.</p>
        </li>
    @endif
@endif