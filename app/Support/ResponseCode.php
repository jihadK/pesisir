<?php

namespace App\Support;

/**
 * Standar kode response aplikasi.
 *
 * Konvensi:
 *  - "00" = SUCCESS
 *  - selain "00" = ERROR (deskripsi di resMsg)
 */
final class ResponseCode
{
    public const SUCCESS               = '00';
    public const VALIDATION_ERROR      = '01';
    public const NOT_FOUND             = '02';
    public const UNAUTHENTICATED       = '03';
    public const FORBIDDEN             = '04';
    public const BUSINESS_RULE_FAILED  = '05';
    public const DUPLICATE             = '06';
    public const DEPENDENCY_CONFLICT   = '07';
    public const RATE_LIMITED          = '08';
    public const SERVER_ERROR          = '99';

    public static function isSuccess(string $code): bool
    {
        return $code === self::SUCCESS;
    }
}
