<?php

namespace App\Enums;

class DocumentStatusEnum
{
    const DITINJAU = 'Ditinjau';
    const PERLU_REVISI = 'Perlu Revisi';
    const DIPARAF = 'Diparaf';
    const DITANDATANGANI = 'Ditandatangani';

    public static function getAllStatuses(): array
    {
        return [
            self::DITINJAU,
            self::PERLU_REVISI,
            self::DIPARAF,
            self::DITANDATANGANI,
        ];
    }
}
