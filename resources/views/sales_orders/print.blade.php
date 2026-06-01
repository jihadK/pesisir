<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $so->isPaid() ? 'Invoice' : 'Proforma' }} {{ $so->so_number }}</title>
    <style>
        @page { size: A4; margin: 12mm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #eceef2; }
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            color: #222;
            font-size: 12px;
            line-height: 1.4;
        }
        /* Lembar dokumen seukuran A4 (210mm × 297mm). */
        .sheet {
            position: relative;
            width: 210mm;
            min-height: 297mm;
            margin: 16px auto;
            padding: 14mm 12mm 12mm;
            background: #fff;
            box-shadow: 0 4px 16px rgba(0,0,0,.08);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #1976d2;
            padding-bottom: 10px;
            margin-bottom: 14px;
            gap: 16px;
        }
        .header .brand { display: flex; align-items: flex-start; gap: 12px; flex: 1; }
        .header .brand img { height: 56px; width: auto; flex-shrink: 0; }
        .header h1 { margin: 0; color: #1976d2; font-size: 20px; line-height: 1.2; }
        .header .tagline { font-size: 11px; color: #666; }
        .header .store-meta { font-size: 11px; color: #555; margin-top: 4px; line-height: 1.45; }
        .header .store-meta div { display: flex; gap: 4px; }
        .header .doc-info { text-align: right; font-size: 11px; flex-shrink: 0; min-width: 130px; }
        .doc-info strong { color: #1976d2; font-size: 13px; }
        .meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 14px;
            padding: 10px 12px;
            background: #f5f7fb;
            border-radius: 6px;
        }
        .meta h3 {
            margin: 0 0 4px;
            font-size: 11px;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th { background: #1976d2; color: #fff; padding: 7px 8px; text-align: left; font-size: 11px; }
        td { padding: 7px 8px; border-bottom: 1px solid #eee; vertical-align: top; }
        .text-end { text-align: right; }
        .totals { margin-left: auto; width: 260px; }
        .totals td { border: none; padding: 4px 8px; }
        .totals .total-row {
            border-top: 2px solid #1976d2;
            font-size: 15px;
            font-weight: bold;
            color: #1976d2;
        }
        .footer-note {
            margin-top: 18px;
            padding: 10px;
            text-align: center;
            color: #777;
            font-size: 11px;
            border-top: 1px dashed #ddd;
        }
        .signoff { margin-top: 20px; font-size: 12px; }
        .signoff .label { color: #555; }
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

        /* Toolbar */
        .toolbar {
            max-width: 210mm;
            margin: 12px auto 0;
            padding: 0 8px;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
        .btn {
            padding: 8px 14px;
            background: #1976d2;
            color: #fff;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            border: none;
            cursor: pointer;
        }
        .btn-wa { background: #25D366; }
        .btn-wa:hover { background: #128C7E; }
        .btn-wa[disabled] { background: #aaa; cursor: not-allowed; }
        .btn-img { background: #9c27b0; }
        .btn-img:hover { background: #7b1fa2; }
        .btn-img[disabled] { background: #aaa; cursor: not-allowed; }
        .btn-dl { background: #ef6c00; }
        .btn-dl:hover { background: #e65100; }

        @media print {
            html, body { background: #fff; }
            .sheet { box-shadow: none; margin: 0; padding: 0; width: auto; min-height: 0; }
            .no-print { display: none !important; }
        }

        /* Mobile: kompres tampilan biar lembar A4 muat di layar tanpa ruang kosong besar. */
        @media (max-width: 820px) {
            .sheet {
                width: 100%;
                min-height: 0;
                margin: 8px 0;
                padding: 14px 14px 18px;
                box-shadow: none;
            }
            .toolbar { padding: 0 12px; }
            .header { flex-direction: column; align-items: flex-start; gap: 8px; }
            .header .doc-info { text-align: left; min-width: 0; }
            .meta { grid-template-columns: 1fr; gap: 8px; }
            .lunas-stamp { font-size: 38px; padding: 8px 24px; letter-spacing: 6px; }
            table { font-size: 11px; }
            th, td { padding: 6px; }
            .totals { width: 100%; }
        }
    </style>
</head>
<body>

@php
    $isCustomerView = request()->routeIs('sales_orders.public-print');

    // Normalize nomor HP toko dari .env: +62/62 di depan diganti 0.
    $rawStorePhone = (string) config('app.store_phone', '');
    $digits = preg_replace('/\D+/', '', $rawStorePhone);
    $storePhone = '';
    if ($digits !== '') {
        if (str_starts_with($digits, '62')) {
            $storePhone = '0' . substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $storePhone = $digits;
        } else {
            $storePhone = $digits;
        }
    }

    // Alamat toko = alamat warehouse utama (WH-LAMONGAN). Fallback ke warehouse
    // yang dipakai SO kalau WH-LAMONGAN belum punya alamat.
    $storeAddress = \App\Models\Warehouse::where('code', 'WH-LAMONGAN')->value('address')
        ?: $so->warehouse?->address;
@endphp

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

        // URL viewer QRIS (ada tombol download). Pakai kalau payment method punya
        // gambar QRIS — supaya customer bisa unduh, bukan cuma lihat gambar mentah.
        $qrisViewerUrl = ($pm && $pm->qris_image_url)
            ? route('payment_methods.qris-view', ['paymentMethod' => $pm->id])
            : null;

        // Build WA message sesuai format UAT
        $isPaid = $so->isPaid();
        $lines = [];
        $lines[] = "Halo Bapak/Ibu {$so->customer->name} 🙏";
        $lines[] = "";
        $lines[] = $isPaid
            ? "Terima kasih atas pesanan Anda. Berikut detail pemesanan anda :"
            : "Terima kasih atas pesanan Anda. Berikut detail tagihan:";
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

        // Sudah lunas → tidak perlu instruksi bayar lagi.
        if (! $isPaid) {
            if ($isQris) {
                $lines[] = "Pembayaran: QRIS";
                $lines[] = "";
                if ($qrisViewerUrl) {
                    $lines[] = "Gambar QRIS dapat langsung diunduh di sini: {$qrisViewerUrl}";
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
                if ($qrisViewerUrl) {
                    $lines[] = "Bisa via QRIS — unduh gambarnya di sini: {$qrisViewerUrl}";
                } else {
                    $lines[] = "Disarankan membawa uang pas saat pengambilan.";
                }
            }
            $lines[] = "";
        }

        $lines[] = $isPaid
            ? "Invoice pelunasan dapat dilihat pada link berikut: {$docUrl}"
            : "Kuitansi tagihan dapat dilihat pada link berikut: {$docUrl}";
        $lines[] = "";
        $lines[] = $isPaid
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
@else
{{-- Customer view: tampilkan tombol Download {{ $docLabel }} + Print. --}}
<div class="toolbar no-print">
    <button id="btn_download_img" class="btn btn-dl" onclick="downloadAsImage()">
        <span style="font-size:16px">⬇️</span> Download {{ $so->isPaid() ? 'Invoice' : 'Kuitansi' }}
    </button>
    <button class="btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
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

    const target = document.querySelector('.sheet') || document.body;

    html2canvas(target, {
        scale: 2,              // 2x = lebih tajam (mirip Retina)
        backgroundColor: '#ffffff',
        useCORS: true,
        logging: false,
    }).then(canvas => {
        if (toolbar) toolbar.style.display = '';

        const isPaid = {{ $so->isPaid() ? 'true' : 'false' }};
        const baseName = (isPaid ? 'Invoice-' : 'Kuitansi-') + '{{ $so->so_number }}';
        const link = document.createElement('a');
        link.download = baseName.replace(/\//g, '-') + '.png';
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

<div class="sheet">

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
            <div class="store-meta">
                @if($storeAddress)
                    <div>📍 <span>{{ $storeAddress }}</span></div>
                @endif
                @if($storePhone)
                    <div>📞 <span>{{ $storePhone }}</span></div>
                @endif
            </div>
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
    <div style="margin-top:14px;padding:10px;background:#f5f5f5;border-radius:4px;font-size:12px">
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

</div>{{-- /.sheet --}}

</body>
</html>
