<?php

namespace App\Enums;

class RoleEnum
{
    // Ubah TU menjadi Tata Usaha (tidak disingkat)
    const TU = 'Tata Usaha';
    
    const KOORDINATOR_PRODI = 'Koordinator Program Studi';
    const DOSEN = 'Dosen';
    const KAJUR = 'Kajur';
    const SEKJUR = 'Sekjur';
    const ADMIN = 'Admin';

    // ID Constants
    const ID_TU = 1;
    const ID_KOORDINATOR_PRODI = 2;
    const ID_DOSEN = 3;
    const ID_KAJUR = 4;
    const ID_SEKJUR = 5;
    const ID_ADMIN = 6;

    /**
     * Get Koordinator roles
     */
    public static function getKoordinatorRoles(): array
    {
        return [
            self::KOORDINATOR_PRODI,
        ];
    }

    /**
     * Get all Kajur/Sekjur roles
     */
    public static function getKajurSekjurRoles(): array
    {
        return [
            self::KAJUR,
            self::SEKJUR,
        ];
    }

    /**
     * Get all roles
     */
    public static function getAllRoles(): array
    {
        return [
            self::TU,
            self::KOORDINATOR_PRODI,
            self::DOSEN,
            self::KAJUR,
            self::SEKJUR,
            self::ADMIN,
        ];
    }
}