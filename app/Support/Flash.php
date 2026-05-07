<?php

namespace App\Support;

use Illuminate\Support\Facades\Session;

/**
 * Helper flash session message yang akan di-render via SweetAlert2.
 * Format konsisten:
 *   { resCode: '00'|'<error_code>', resMsg: '...', title?: '...' }
 *
 * Cara pakai di controller:
 *   return back()->with('flash', Flash::ok('Data tersimpan.'));
 *   return back()->with('flash', Flash::err('Tidak boleh dihapus', ResponseCode::BUSINESS_RULE_FAILED));
 */
final class Flash
{
    public static function ok(string $msg, ?string $title = 'Berhasil'): array
    {
        return [
            'resCode' => ResponseCode::SUCCESS,
            'resMsg'  => $msg,
            'title'   => $title,
            'icon'    => 'success',
        ];
    }

    public static function err(string $msg, string $code = ResponseCode::SERVER_ERROR, ?string $title = 'Gagal'): array
    {
        return [
            'resCode' => $code,
            'resMsg'  => $msg,
            'title'   => $title,
            'icon'    => 'error',
        ];
    }

    public static function info(string $msg, ?string $title = 'Info'): array
    {
        return [
            'resCode' => ResponseCode::SUCCESS,
            'resMsg'  => $msg,
            'title'   => $title,
            'icon'    => 'info',
        ];
    }

    public static function warning(string $msg, ?string $title = 'Perhatian'): array
    {
        return [
            'resCode' => ResponseCode::SERVER_ERROR,
            'resMsg'  => $msg,
            'title'   => $title,
            'icon'    => 'warning',
        ];
    }
}
