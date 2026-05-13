<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>PO {{ $po->po_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; color: #222; margin: 0; padding: 24px; max-width: 720px; margin-left:auto; margin-right:auto; font-size: 13px; }
        .header { display:flex; justify-content:space-between; align-items:center; border-bottom: 3px solid #1976d2; padding-bottom: 12px; margin-bottom: 16px; gap: 16px; }
        .header .brand { display:flex; align-items:center; gap: 12px; }
        .header .brand img { height: 56px; }
        .header h1 { margin: 0; color: #1976d2; font-size: 22px; }
        .header .tagline { font-size: 11px; color: #666; }
        .header .doc-info { text-align: right; font-size: 12px; }
        .doc-info strong { color: #1976d2; font-size: 15px; }
        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 16px; padding: 12px; background:#f5f7fb; border-radius: 6px; }
        .meta h3 { margin: 0 0 6px 0; font-size: 12px; color: #555; text-transform: uppercase; letter-spacing:0.5px; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.items th { background: #1976d2; color: white; padding: 8px; text-align: left; font-size: 12px; }
        table.items td { padding: 8px; border-bottom: 1px solid #eee; }
        .text-end { text-align: right; }
        .totals { margin-left: auto; width: 320px; border-collapse:collapse; }
        .totals td { padding: 5px 8px; }
        .totals .total-row { border-top: 2px solid #1976d2; font-size: 16px; font-weight: bold; color: #1976d2; }
        .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 40px; padding-top: 20px; }
        .sign-box { text-align: center; font-size: 12px; }
        .sign-box .line { border-top: 1px solid #333; margin-top: 70px; padding-top: 4px; }
        .sign-box .role { color: #666; }
        @media print { body { padding: 0; } .no-print { display: none !important; } }
        .toolbar { text-align: right; margin-bottom: 12px; }
        .btn { padding: 8px 14px; background:#1976d2; color: white; border-radius:4px; text-decoration:none; display:inline-block; font-size:13px; border:none; cursor:pointer; margin-left:6px; }
    </style>
</head>
<body>

<div class="toolbar no-print">
    <button class="btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
    <a href="{{ route('purchase_orders.show', $po) }}" class="btn" style="background:#666">← Kembali</a>
</div>

<div class="header">
    <div class="brand">
        <img src="{{ asset('storage/logo/logo-pesisir-web.png') }}" alt="Logo" onerror="this.style.display='none'" />
        <div>
            <h1>{{ config('app.name', 'Pesisir Fresh Fish') }}</h1>
            <div class="tagline">Ikan Segar dari Laut Pesisir</div>
            <div class="tagline"><strong>PURCHASE ORDER</strong></div>
        </div>
    </div>
    <div class="doc-info">
        <div><strong>{{ $po->po_number }}</strong></div>
        <div>Tgl: {{ $po->po_date?->format('d M Y') }}</div>
        @if($po->expected_date)<div>Exp: <strong>{{ $po->expected_date->format('d M Y') }}</strong></div>@endif
    </div>
</div>

<div class="meta">
    <div>
        <h3>Supplier</h3>
        <div><strong>{{ $po->supplier->name }}</strong></div>
        <div>{{ $po->supplier->code }}</div>
        @if($po->supplier->contact_person)<div>{{ $po->supplier->contact_person }}</div>@endif
        @if($po->supplier->phone)<div>📞 {{ $po->supplier->phone }}</div>@endif
        @if($po->supplier->address)<div style="font-size:11px;color:#666;margin-top:2px">{{ $po->supplier->address }}</div>@endif
    </div>
    <div>
        <h3>Kirim ke</h3>
        <div><strong>{{ $po->warehouse->name }}</strong></div>
        <div>{{ $po->warehouse->code }}</div>
        @if($po->warehouse->address)<div style="font-size:11px;color:#666;margin-top:2px">{{ $po->warehouse->address }}</div>@endif
        <div style="margin-top:6px">Status: <strong>{{ $po->status_label }}</strong></div>
    </div>
</div>

<table class="items">
    <thead>
        <tr>
            <th style="width:5%">No</th>
            <th style="width:42%">Sub-Kategori</th>
            <th class="text-end" style="width:14%">Qty (gram)</th>
            <th class="text-end" style="width:13%">Kg</th>
            <th class="text-end" style="width:13%">Harga/Kg</th>
            <th class="text-end" style="width:13%">Subtotal</th>
        </tr>
    </thead>
    <tbody>
    @foreach($po->items as $i => $item)
        @php $kg = (float)$item->qty_gram / 1000; @endphp
        <tr>
            <td>{{ $i + 1 }}</td>
            <td><strong>{{ $item->category->parent?->name }}{{ $item->category->parent ? ' › ' : '' }}{{ $item->category->name }}</strong></td>
            <td class="text-end">{{ number_format((float)$item->qty_gram, 0, ',', '.') }}</td>
            <td class="text-end">{{ rtrim(rtrim(number_format($kg, 3, ',', '.'), '0'), ',') }}</td>
            <td class="text-end">{{ number_format((float)$item->price_per_kg, 0, ',', '.') }}</td>
            <td class="text-end">{{ number_format((float)$item->subtotal, 0, ',', '.') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="totals">
    <tr class="total-row"><td>TOTAL</td><td class="text-end">Rp {{ number_format((float)$po->total_amount, 0, ',', '.') }}</td></tr>
</table>

@if($po->notes)
    <div style="margin-top:16px;padding:10px;background:#f5f5f5;border-radius:4px;font-size:12px">
        <strong>Catatan:</strong> {{ $po->notes }}
    </div>
@endif

<div class="signatures">
    <div class="sign-box">
        <div class="role">Hormat kami,</div>
        <div class="line">{{ $po->approvedBy?->full_name ?? $po->createdBy?->full_name ?? '_______________' }}</div>
        <div style="font-size:11px;color:#888">Pemesan</div>
    </div>
    <div class="sign-box">
        <div class="role">Diterima &amp; disetujui,</div>
        <div class="line">_______________</div>
        <div style="font-size:11px;color:#888">Supplier ({{ $po->supplier->name }})</div>
    </div>
</div>

</body>
</html>
