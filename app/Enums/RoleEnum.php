<?php

namespace App\Enums;

class RoleEnum
{
    const TU = 'TU';
    const KAPRODI_D3 = 'Kaprodi D3';
    const KAPRODI_D4 = 'Kaprodi D4';
    const KAJUR = 'Kajur';
    const SEKJUR = 'Sekjur';

    // ID Constants
    const ID_TU = 1;
    const ID_KAPRODI_D3 = 2;
    const ID_KAPRODI_D4 = 3;
    const ID_KAJUR = 4;
    const ID_SEKJUR = 5;

    /**
     * Get all Kaprodi roles
     */
    public static function getKaprodiRoles(): array
    {
        return [
            self::KAPRODI_D3,
            self::KAPRODI_D4,
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
            self::KAPRODI_D3,
            self::KAPRODI_D4,
            self::KAJUR,
            self::SEKJUR,
        ];
    }
}
