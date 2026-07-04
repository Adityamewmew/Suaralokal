<?php

namespace App\Constants;

class UserConst
{
    // Legacy access_type constants (kept for existing admin screens)
    const SUPERADMIN = 1;

    const DEFAULT_PASSWORD = '$2y$12$2pV4WiD9nLczb381xpk20uGq4NnaVhUocp5aciksw5BhcgxkiKDh2';

    // SuaraLokal role constants
    const ROLE_SUPERADMIN = 'superadmin';

    const ROLE_PENGGUNA = 'pengguna';

    const ROLE_UMKM = 'umkm';

    const ROLE_OJEK_ADMIN = 'ojek_admin';

    const ROLE_DRIVER = 'driver';

    public static function getAccessTypes(): array
    {
        return [
            self::SUPERADMIN => 'Super Admin',
        ];
    }

    public static function getAppAccessTypes(): array
    {
        return self::getAccessTypes();
    }

    /**
     * Get all SuaraLokal roles.
     */
    public static function getRoles(): array
    {
        return [
            self::ROLE_SUPERADMIN => 'Super Admin',
            self::ROLE_PENGGUNA => 'Pengguna',
            self::ROLE_UMKM => 'UMKM',
            self::ROLE_OJEK_ADMIN => 'Admin Bangjek',
            self::ROLE_DRIVER => 'Driver',
        ];
    }
}
