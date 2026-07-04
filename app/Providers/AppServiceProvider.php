<?php

namespace App\Providers;

use App\Usecase\Admin\SidebarMenuUsecase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Blaze\Blaze;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blaze::optimize()->in(
            resource_path('views/components'),
            fold: true,
        );

        View::composer('_admin._layout.sidebar.*', function ($view) {
            if (! Auth::check()) {
                return;
            }

            $usecase = app(SidebarMenuUsecase::class);
            $accessType = (int) (Auth::user()->access_type ?? 0);
            $groups = $usecase->getGroupKeys();
            $sidebarMenus = [];

            foreach ($groups as $group) {
                $result = $usecase->getMenusForSidebar($accessType, $group);
                $sidebarMenus[$group] = $result['data'] ?? [];
            }

            $view->with('sidebarMenus', $sidebarMenus);
        });
    }
}
