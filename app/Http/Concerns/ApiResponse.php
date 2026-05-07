<?php

namespace App\Http\Concerns;

use App\Support\ResponseCode;
use Illuminate\Http\JsonResponse;

/**
 * Trait untuk respons JSON konsisten dengan format:
 *   { "resCode": "00", "resMsg": "success", "data": {...} }
 *
 * Pakai di Controller AJAX/API.
 */
trait ApiResponse
{
    protected function ok(mixed $data = null, string $msg = 'success', int $http = 200): JsonResponse
    {
        return response()->json([
            'resCode' => ResponseCode::SUCCESS,
            'resMsg'  => $msg,
            'data'    => $data,
        ], $http);
    }

    protected function fail(string $msg, string $code = ResponseCode::SERVER_ERROR, int $http = 400, mixed $data = null): JsonResponse
    {
        $payload = ['resCode' => $code, 'resMsg' => $msg];
        if ($data !== null) {
            $payload['data'] = $data;
        }
        return response()->json($payload, $http);
    }

    protected function failValidation(array $errors, string $msg = 'Validasi gagal'): JsonResponse
    {
        return $this->fail($msg, ResponseCode::VALIDATION_ERROR, 422, ['errors' => $errors]);
    }

    protected function failNotFound(string $msg = 'Data tidak ditemukan'): JsonResponse
    {
        return $this->fail($msg, ResponseCode::NOT_FOUND, 404);
    }

    protected function failForbidden(string $msg = 'Anda tidak punya akses'): JsonResponse
    {
        return $this->fail($msg, ResponseCode::FORBIDDEN, 403);
    }

    protected function failBusinessRule(string $msg): JsonResponse
    {
        return $this->fail($msg, ResponseCode::BUSINESS_RULE_FAILED, 422);
    }
}
