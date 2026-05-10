<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Proforma {{ $so->so_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; color: #222; margin: 0; padding: 24px; max-width: 720px; margin-left:auto; margin-right:auto; font-size: 13px; }
        .header { display:flex; justify-content:space-between; align-items:center; border-bottom: 3px solid #1976d2; padding-bottom: 12px; margin-bottom: 16px; gap: 16px; }
        .header .brand { display:flex; align-items:center; gap: 12px; }
        .header .brand img { height: 56px; width: auto; }
        .header h1 { margin: 0; color: #1976d2; font-size: 22px; }
        .header .tagline { font-size: 11px; color: #666; }
        .header .doc-info { text-align: right; font-size: 12px; }
        .doc-info strong { color: #1976d2; font-size: 14px; }
        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 16px; padding: 12px; background:#f5f7fb; border-radius: 6px; }
        .meta h3 { margin: 0 0 6px 0; font-size: 12px; color: #555; text-transform: uppercase; letter-spacing:0.5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th { background: #1976d2; color: white; padding: 8px; text-align: left; font-size: 12px; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        .text-end { text-align: right; }
        .totals { margin-left: auto; width: 280px; }
        .totals td { border: none; padding: 4px 8px; }
        .totals .total-row { border-top: 2px solid #1976d2; font-size: 16px; font-weight: bold; color: #1976d2; }
        .payment-section { margin-top: 24px; padding: 16px; background:#fff8e1; border-left: 4px solid #ff9800; border-radius:4px; }
        .payment-section h3 { margin: 0 0 10px 0; color: #e65100; font-size: 14px; }
        .pm-list { display: flex; flex-direction: column; gap: 8px; }
        .pm-item { padding: 8px 12px; background: white; border-radius: 4px; border-left: 3px solid transparent; }
        .pm-item.pm-chosen { border-left-color: #2e7d32; background: #e8f5e9; }
        .pm-item .pm-name { font-weight: bold; }
        .pm-item .pm-chosen-tag { display:inline-block; margin-left:6px; padding:2px 8px; background:#2e7d32; color:white; font-size:10px; border-radius:10px; vertical-align:middle; }
        .pm-item .pm-detail { font-size: 12px; color: #555; }
        .qris-img { max-width: 140px; display: block; margin: 6px 0; }
        .footer-note { margin-top: 20px; padding: 12px; text-align: center; color: #777; font-size: 11px; border-top: 1px dashed #ddd; }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
        }
        .toolbar { text-align: right; margin-bottom: 12px; display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap; }
        .btn { padding: 8px 14px; background:#1976d2; color: white; border-radius:4px; text-decoration:none; display:inline-flex; align-items:center; gap:6px; font-size:13px; border:none; cursor:pointer; }
        .btn-wa { background:#25D366; }
        .btn-wa:hover { background:#128C7E; }
        .btn-wa[disabled] { background:#aaa; cursor:not-allowed; }
    </style>
</head>
<body>

<div class="toolbar no-print">
    @php
        // Normalize Indonesian phone: strip non-digits, leading 0 → 62, leading +62 → 62
        $rawPhone = $so->customer->phone ?? '';
        $digitsOnly = preg_replace('/\D+/', '', $rawPhone);
        $waPhone = '';
        if ($digitsOnly) {
            if (str_starts_with($digitsOnly, '62')) $waPhone = $digitsOnly;
            elseif (str_starts_with($digitsOnly, '0')) $waPhone = '62' . substr($digitsOnly, 1);
            else $waPhone = '62' . $digitsOnly;
        }

        // Build pre-filled message
        $lines = [];
        $lines[] = "Halo Bapak/Ibu *{$so->customer->name}* 🙏";
        $lines[] = "";
        $lines[] = "Terima kasih atas pesanan Anda. Berikut detail tagihan:";
        $lines[] = "";
        $lines[] = "*No. Order:* {$so->so_number}";
        $lines[] = "*Tanggal:* " . $so->order_date->format('d M Y');
        $lines[] = "";
        $lines[] = "*Items:*";
        foreach ($so->items as $it) {
            $q = (float) $it->quantity;
            $qF = floor($q) == $q ? number_format($q, 0, ',', '.') : number_format($q, 3, ',', '.');
            $lines[] = "• {$it->product->name} — {$qF} {$it->uom->code} × Rp " . number_format((float)$it->unit_price, 0, ',', '.');
        }
        $lines[] = "";
        $lines[] = "*TOTAL: Rp " . number_format((float)$so->total_amount, 0, ',', '.') . "*";
        $lines[] = "";
        if ($so->paymentMethod) {
            $pm = $so->paymentMethod;
            $lines[] = "*Pembayaran:* {$pm->name}";
            if ($pm->bank_name && $pm->account_no) {
                $lines[] = "{$pm->bank_name} {$pm->account_no} a.n. {$pm->account_holder}";
            }
        } else {
            $lines[] = "Pembayaran bisa via Transfer Bank / QRIS / COD (lihat detail di PDF).";
        }
        $lines[] = "";
        $lines[] = "Detail lengkap & info rekening saya kirim via PDF ya 📎";
        $lines[] = "Mohon konfirmasi setelah pembayaran. Terima kasih 🙏";

        $waMessage = rawurlencode(implode("\n", $lines));
        $waUrl = $waPhone ? "https://wa.me/{$waPhone}?text={$waMessage}" : "https://wa.me/?text={$waMessage}";
    @endphp

    @if($waPhone)
        <a class="btn btn-wa" href="{{ $waUrl }}" target="_blank" rel="noopener">
            <span style="font-size:16px">💬</span> Kirim ke WhatsApp Customer
        </a>
    @else
        <button class="btn btn-wa" disabled title="Customer belum punya nomor HP di master data">
            <span style="font-size:16px">💬</span> WhatsApp (no. HP belum diisi)
        </button>
    @endif

    <button class="btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
    <a href="{{ route('sales_orders.show', $so) }}" class="btn" style="background:#666">← Kembali</a>
</div>

<div class="header">
    <div class="brand">
        <img src="{{ asset('storage/logo/logo-pesisir-web.png') }}" alt="Logo" onerror="this.style.display='none'" />
        <div>
            <h1>{{ config('app.name', 'Pesisir Fresh Fish') }}</h1>
            <div class="tagline">Ikan Segar dari Laut Pesisir</div>
            <div class="tagline">Tagihan / Proforma Invoice</div>
        </div>
    </div>
    <div class="doc-info">
        <div><strong>{{ $so->so_number }}</strong></div>
        <div>{{ $so->order_date?->format('d M Y') }}</div>
        @if($so->delivery_date)
            <div style="font-size:11px;color:#666">Kirim: {{ $so->delivery_date?->format('d M Y') }}</div>
        @endif
    </div>
</div>

<div class="meta">
    <div>
        <h3>Customer</h3>
        <div><strong>{{ $so->customer->name }}</strong></div>
        <div>{{ $so->customer->code }}</div>
        @if($so->customer->phone)<div>📞 {{ $so->customer->phone }}</div>@endif
        @if($so->customer->address)<div style="font-size:11px;color:#666;margin-top:2px">{{ $so->customer->address }}</div>@endif
    </div>
    <div>
        <h3>Detail</h3>
        <div>Warehouse: <strong>{{ $so->warehouse->name }}</strong></div>
        <div>Term: <strong>{{ $so->payment_terms_days }} hari</strong></div>
        <div>Status: <strong>{{ $so->status_label }}</strong></div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:50%">Item</th>
            <th class="text-end" style="width:15%">Qty</th>
            <th class="text-end" style="width:15%">Harga</th>
            <th class="text-end" style="width:20%">Subtotal</th>
        </tr>
    </thead>
    <tbody>
    @foreach($so->items as $item)
        @php $q = (float)$item->quantity; $qF = floor($q)==$q ? number_format($q,0,',','.') : number_format($q,3,',','.'); @endphp
        <tr>
            <td>
                <div><strong>{{ $item->product->name }}</strong></div>
                <div style="font-size:11px;color:#666">{{ $item->product->sku }}</div>
                @if($item->product->pack_content_label || $item->product->pack_weight_label)
                    <div style="font-size:11px;color:#888;margin-top:2px">
                        {{ $item->product->pack_content_label }}{{ $item->product->pack_content_label && $item->product->pack_weight_label ? ' · ' : '' }}{{ $item->product->pack_weight_label }}
                    </div>
                @endif
            </td>
            <td class="text-end">{{ $qF }} {{ $item->uom->code }}</td>
            <td class="text-end">Rp {{ number_format((float)$item->unit_price, 0, ',', '.') }}</td>
            <td class="text-end">Rp {{ number_format((float)$item->subtotal, 0, ',', '.') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="totals">
    <tr><td>Subtotal</td><td class="text-end">Rp {{ number_format((float)$so->subtotal, 0, ',', '.') }}</td></tr>
    @if((float)$so->discount_amount > 0)
        <tr><td>Diskon</td><td class="text-end">−Rp {{ number_format((float)$so->discount_amount, 0, ',', '.') }}</td></tr>
    @endif
    @if((float)$so->shipping_cost > 0)
        <tr><td>Ongkir</td><td class="text-end">Rp {{ number_format((float)$so->shipping_cost, 0, ',', '.') }}</td></tr>
    @endif
    <tr class="total-row"><td>TOTAL</td><td class="text-end">Rp {{ number_format((float)$so->total_amount, 0, ',', '.') }}</td></tr>
</table>

@if($paymentMethods->isNotEmpty())
<div class="payment-section">
    <h3>💳 Cara Pembayaran</h3>
    <div class="pm-list">
        @foreach($paymentMethods as $pm)
            @php $isChosen = $so->payment_method_id == $pm->id; @endphp
            <div class="pm-item {{ $isChosen ? 'pm-chosen' : '' }}">
                <div class="pm-name">
                    {{ $pm->name }}
                    @if($isChosen)<span class="pm-chosen-tag">✓ PILIHAN ANDA</span>@endif
                </div>
                @if($pm->bank_name || $pm->account_no)
                    <div class="pm-detail">{{ $pm->bank_name }} {{ $pm->account_no }} a.n. <strong>{{ $pm->account_holder }}</strong></div>
                @endif
                @if($pm->qris_image_display_url)
                    <img class="qris-img" src="{{ $pm->qris_image_display_url }}" alt="QRIS" />
                @endif
                @if($pm->description)
                    <div class="pm-detail">{{ $pm->description }}</div>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endif

@if($so->notes)
    <div style="margin-top:16px;padding:10px;background:#f5f5f5;border-radius:4px;font-size:12px">
        <strong>Catatan:</strong> {{ $so->notes }}
    </div>
@endif

<div class="footer-note">
    Mohon konfirmasi pembayaran setelah transfer.<br>
    Terima kasih atas pesanan Anda 🙏
</div>

</body>
</html>
