<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogPortalVisit
{
    /**
     * Catat satu baris visit log untuk halaman portal yang dibuka pengunjung.
     * Hanya GET, hanya request HTML (skip JSON / asset). Kalau insert gagal
     * (mis. tabel belum migrate), error di-swallow supaya tidak merusak page.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && ! $request->expectsJson()) {
            try {
                DB::table('tbh_visit_logs')->insert([
                    'path'       => substr($request->path() === '' ? '/' : '/' . $request->path(), 0, 255),
                    'ip'         => $request->ip(),
                    'ua_hash'    => substr(hash('sha256', (string) $request->userAgent()), 0, 64),
                    'session_id' => $request->hasSession() ? substr($request->session()->getId(), 0, 64) : null,
                    'referer'    => substr((string) $request->headers->get('referer'), 0, 500) ?: null,
                    'created_at' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('LogPortalVisit insert failed: ' . $e->getMessage());
            }
        }

        return $response;
    }
}
