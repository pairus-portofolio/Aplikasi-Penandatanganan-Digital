<?php

namespace App\Enums;

class RoleEnum
{
    const TU = 'Tata Usaha';
    const KOORDINATOR_PRODI = 'Koordinator Program Studi';
    const DOSEN = 'Dosen';
    
    const KAJUR = 'Ketua Jurusan';
    const SEKJUR = 'Sekretaris Jurusan';
    
    const ADMIN = 'Admin';

    // ID Constants
    const ID_TU = 1;
    const ID_KOORDINATOR_PRODI = 2;
    const ID_DOSEN = 3;
    const ID_KAJUR = 4;
    const ID_SEKJUR = 5;
    const ID_ADMIN = 6;

    public static function getKoordinatorRoles(): array
    {
        return [self::KOORDINATOR_PRODI];
    }

    public static function getKajurSekjurRoles(): array
    {
        return [
            self::KAJUR,
            self::SEKJUR,
        ];
    }

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