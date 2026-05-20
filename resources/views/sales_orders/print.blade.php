<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $so->isPaid() ? 'Invoice' : 'Proforma' }} {{ $so->so_number }}</title>
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
        .signoff { margin-top: 24px; font-size: 12px; }
        .signoff .label { color:#555; }
        .signoff .brand { font-weight: bold; color: #1976d2; margin-top: 28px; }
        .lunas-stamp {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-18deg);
            border: 5px double #2e7d32;
            color: #2e7d32;
            padding: 10px 40px;
            font-weight: bold;
            font-size: 56px;
            letter-spacing: 10px;
            opacity: 0.55;
            background: rgba(232, 245, 233, 0.25);
            border-radius: 8px;
            text-transform: uppercase;
            pointer-events: none;
            z-index: 5;
            white-space: nowrap;
        }
        body { position: relative; }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
        }
        .toolbar { text-align: right; margin-bottom: 12px; display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap; }
        .btn { padding: 8px 14px; background:#1976d2; color: white; border-radius:4px; text-decoration:none; display:inline-flex; align-items:center; gap:6px; font-size:13px; border:none; cursor:pointer; }
        .btn-wa { background:#25D366; }
        .btn-wa:hover { background:#128C7E; }
        .btn-wa[disabled] { background:#aaa; cursor:not-allowed; }
        .btn-img { background:#9c27b0; }
        .btn-img:hover { background:#7b1fa2; }
        .btn-img[disabled] { background:#aaa; cursor:not-allowed; }
    </style>
</head>
<body>

@php $isCustomerView = request()->routeIs('sales_orders.public-print'); @endphp

@if(! $isCustomerView)
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

        // Detect QR / Bank from chosen payment method
        $pm = $so->paymentMethod;
        $isQris = $pm && ($pm->qris_image_url || stripos($pm->name, 'qr') !== false);
        $isBank = $pm && $pm->type === \App\Models\PaymentMethod::TYPE_TRANSFER;

        // Document link (Proforma sebelum paid, Invoice sesudah)
        $docLabel = $so->isPaid() ? 'Invoice' : 'Proforma';
        // Public signed URL — customer bisa buka tanpa login, anti-tebak (HMAC), no expiry
        $docUrl = \Illuminate\Support\Facades\URL::signedRoute('sales_orders.public-print', ['salesOrder' => $so->id]);
        $qrisUrl = $pm && $pm->qris_image_url ? $pm->qris_image_display_url : null;

        // Build WA message sesuai format UAT
        $lines = [];
        $lines[] = "Halo Bapak/Ibu {$so->customer->name} 🙏";
        $lines[] = "";
        $lines[] = "Terima kasih atas pesanan Anda. Berikut detail tagihan:";
        $lines[] = "";
        $lines[] = "No. Order: {$so->so_number}";
        $lines[] = "Tanggal: " . $so->order_date->format('d M Y');
        $lines[] = "";
        $lines[] = "Items:";
        foreach ($so->items as $it) {
            $q = (float) $it->quantity;
            $qF = floor($q) == $q ? number_format($q, 0, ',', '.') : number_format($q, 3, ',', '.');
            $lines[] = "* {$it->product->name} — {$qF} {$it->uom->code} × Rp " . number_format((float)$it->unit_price, 0, ',', '.');
        }
        $lines[] = "";
        $lines[] = "TOTAL: Rp " . number_format((float)$so->total_amount, 0, ',', '.');
        $lines[] = "";
        if ($isQris) {
            $lines[] = "Pembayaran: QRIS";
            $lines[] = "";
            if ($qrisUrl) {
                $lines[] = "Gambar QRIS dapat langsung diunduh di sini: {$qrisUrl}";
            } else {
                $lines[] = "Gambar QRIS dapat dilihat pada link kuitansi di bawah.";
            }
        } elseif ($isBank) {
            $lines[] = "Pembayaran: Transfer Bank";
            $lines[] = "";
            $lines[] = "Mohon transfer ke rekening berikut:";
            $lines[] = "{$pm->bank_name} {$pm->account_no}";
            $lines[] = "a.n. {$pm->account_holder}";
        } else {
            $lines[] = "Pembayaran: Belum ditentukan";
            $lines[] = "";
            if ($qrisUrl) {
                $lines[] = "Bisa via QRIS — unduh gambarnya di sini: {$qrisUrl}";
            } else {
                $lines[] = "Disarankan membawa uang pas saat pengambilan.";
            }
        }
        $lines[] = "";
        $lines[] = "Kuitansi tagihan dapat dilihat pada link berikut: {$docUrl}";
        $lines[] = "";
        $lines[] = $so->isPaid()
            ? "Terima kasih atas pembayaran Anda. Invoice resmi terlampir di link di atas 🙏"
            : "Mohon konfirmasi setelah pembayaran, invoice akan dikirimkan setelah konfirmasi pembayaran. Terima kasih 🙏";
        $lines[] = "";
        $lines[] = "Love,";
        $lines[] = "Pesisir Fresh Fish";

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

    <button id="btn_download_img" class="btn btn-img" onclick="downloadAsImage()">
        <span style="font-size:16px">🖼️</span> Download Gambar
    </button>
    <button class="btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
    <a href="{{ route('sales_orders.show', $so) }}" class="btn" style="background:#666">← Kembali</a>
</div>
@endif

<!-- html2canvas dari CDN, load di-defer agar tidak block render -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" defer></script>
<script>
function downloadAsImage() {
    const btn = document.getElementById('btn_download_img');
    if (typeof html2canvas === 'undefined') {
        alert('Library belum siap, coba lagi beberapa detik.');
        return;
    }
    btn.disabled = true;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '⏳ Membuat gambar...';

    // Sembunyikan toolbar dulu supaya tidak ikut di-capture
    const toolbar = document.querySelector('.toolbar');
    if (toolbar) toolbar.style.display = 'none';

    html2canvas(document.body, {
        scale: 2,              // 2x = lebih tajam (mirip Retina)
        backgroundColor: '#ffffff',
        useCORS: true,
        logging: false,
    }).then(canvas => {
        if (toolbar) toolbar.style.display = '';

        const link = document.createElement('a');
        link.download = 'Proforma-{{ $so->so_number }}'.replace(/\//g, '-') + '.png';
        link.href = canvas.toDataURL('image/png');
        link.click();

        btn.disabled = false;
        btn.innerHTML = originalHtml;
    }).catch(err => {
        if (toolbar) toolbar.style.display = '';
        console.error(err);
        alert('Gagal membuat gambar: ' + (err.message || err));
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
}
</script>

@if($so->isPaid())
    <div class="lunas-stamp">LUNAS</div>
@endif

<div class="header">
    <div class="brand">
        <img src="{{ asset('storage/logo/logo-pesisir-web.png') }}" alt="Logo" onerror="this.style.display='none'" />
        <div>
            <h1>{{ config('app.name', 'Pesisir Fresh Fish') }}</h1>
            <div class="tagline">Ikan Segar dari Laut Pesisir</div>
            <div class="tagline">{{ $so->isPaid() ? 'INVOICE (Lunas)' : 'Tagihan / Proforma Invoice' }}</div>
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
        <div>Status: <strong>{{ $so->status_label }}</strong></div>
        @if($so->delivery_date)<div>Tgl Kirim: <strong>{{ $so->delivery_date->format('d M Y') }}</strong></div>@endif
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
    @if((float)$so->packing_cost > 0)
        <tr><td>Biaya Packing</td><td class="text-end">Rp {{ number_format((float)$so->packing_cost, 0, ',', '.') }}</td></tr>
    @endif
    @if((float)$so->other_cost_amount > 0)
        <tr><td>Biaya Lain-lain{{ $so->other_cost_desc ? ' ('.$so->other_cost_desc.')' : '' }}</td><td class="text-end">Rp {{ number_format((float)$so->other_cost_amount, 0, ',', '.') }}</td></tr>
    @endif
    <tr class="total-row"><td>TOTAL</td><td class="text-end">Rp {{ number_format((float)$so->total_amount, 0, ',', '.') }}</td></tr>
</table>

{{-- Informasi pembayaran sengaja di-hide; sudah dicantumkan di pesan WhatsApp. --}}

@if($so->notes)
    <div style="margin-top:16px;padding:10px;background:#f5f5f5;border-radius:4px;font-size:12px">
        <strong>Catatan:</strong> {{ $so->notes }}
    </div>
@endif

<div class="signoff">
    <div class="label">Hormat kami,</div>
    <div class="brand">Pesisir Fresh Fish</div>
</div>

<div class="footer-note">
    @if($so->isPaid())
        Terima kasih atas pembayaran Anda 🙏
    @else
        Mohon konfirmasi pembayaran setelah transfer.<br>
        Terima kasih atas pesanan Anda 🙏
    @endif
</div>

</body>
</html>
