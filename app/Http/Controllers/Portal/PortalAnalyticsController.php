<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PortalAnalyticsController extends Controller
{
    /**
     * Catat lead (intent checkout via WhatsApp) dari customer portal.
     * Endpoint anonim, dipanggil JS via fetch keepalive sesaat sebelum
     * window.open(wa.me). Kalau gagal, JS tetap melanjutkan ke WA.
     */
    public function recordLead(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items'                 => ['required', 'array', 'min:1', 'max:100'],
            'items.*.name'          => ['required', 'string', 'max:255'],
            'items.*.qty'           => ['required', 'numeric', 'min:0'],
            'items.*.uom'           => ['nullable', 'string', 'max:20'],
            'items.*.price'         => ['required', 'numeric', 'min:0'],
            'total'                 => ['required', 'numeric', 'min:0'],
        ]);

        try {
            // Bersihkan items: hanya field yang dibutuhkan, batasi ukuran.
            $items = collect($data['items'])->map(fn ($i) => [
                'name'  => mb_substr((string) $i['name'], 0, 255),
                'qty'   => (float) $i['qty'],
                'uom'   => mb_substr((string) ($i['uom'] ?? ''), 0, 20),
                'price' => (float) $i['price'],
            ])->values()->all();

            DB::table('tbh_portal_leads')->insert([
                'items'        => json_encode($items, JSON_UNESCAPED_UNICODE),
                'item_count'   => count($items),
                'total_amount' => (float) $data['total'],
                'ip'           => $request->ip(),
                'ua_hash'      => substr(hash('sha256', (string) $request->userAgent()), 0, 64),
                'session_id'   => $request->hasSession() ? substr($request->session()->getId(), 0, 64) : null,
                'created_at'   => now(),
            ]);

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            Log::warning('recordLead insert failed: ' . $e->getMessage());
            return response()->json(['ok' => false], 200);
        }
    }
}
