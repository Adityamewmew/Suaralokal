<?php

namespace App\Constants;

class DatabaseConst
{
    const SQL_READ = 'mysql_read';

    public static function USER(): string
    {
        return 'users';
    }

    public static function UMKM_PROFILE(): string
    {
        return 'umkm_profiles';
    }

    public static function CONVERSATION(): string
    {
        return 'conversations';
    }

    public static function MESSAGE(): string
    {
        return 'messages';
    }

    public static function ORDER(): string
    {
        return 'orders';
    }

    public static function ORDER_ITEM(): string
    {
        return 'order_items';
    }

    public static function SETTLEMENT(): string
    {
        return 'settlements';
    }

    public static function SIDEBAR_MENU(): string
    {
        return 'sidebar_menus';
    }

    public static function SIDEBAR_MENU_ACCESS(): string
    {
        return 'sidebar_menu_accesses';
    }

    public static function SIDEBAR_MENU_GROUP(): string
    {
        return 'sidebar_menu_groups';
    }

    public static function DB_CORE(): string
    {
        return config('database.connections.'.config('database.default').'.database', 'default');
    }
}
