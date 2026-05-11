<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Jalan {{ $do->do_number }}</title>
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
        .meta h3 { margin: 0 0 6px 0; font-size: 12px; color: #555; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th { background: #1976d2; color: white; padding: 8px; text-align: left; font-size: 12px; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        .text-end { text-align: right; }
        .signatures { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 30px; margin-top: 40px; padding-top: 20px; }
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
    <a href="{{ route('delivery_orders.show', $do) }}" class="btn" style="background:#666">← Kembali</a>
</div>

<div class="header">
    <div class="brand">
        <img src="{{ asset('storage/logo/logo-pesisir-web.png') }}" alt="Logo" onerror="this.style.display='none'" />
        <div>
            <h1>{{ config('app.name', 'Pesisir Fresh Fish') }}</h1>
            <div class="tagline">Ikan Segar dari Laut Pesisir</div>
            <div class="tagline"><strong>SURAT JALAN</strong></div>
        </div>
    </div>
    <div class="doc-info">
        <div><strong>{{ $do->do_number }}</strong></div>
        <div>{{ $do->delivery_date?->format('d M Y') }}</div>
        @if($do->salesOrder)
            <div style="font-size:11px;color:#666">Ref: {{ $do->salesOrder->so_number }}</div>
        @endif
    </div>
</div>

<div class="meta">
    <div>
        <h3>Kirim ke</h3>
        <div><strong>{{ $do->customer->name }}</strong></div>
        <div>{{ $do->customer->code }}</div>
        @if($do->customer->phone)<div>📞 {{ $do->customer->phone }}</div>@endif
        @if($do->customer->address)<div style="font-size:11px;color:#666;margin-top:2px">{{ $do->customer->address }}</div>@endif
    </div>
    <div>
        <h3>Pengiriman</h3>
        <div>Dari: <strong>{{ $do->warehouse->name }}</strong></div>
        @if($do->driver_name)<div>Driver: <strong>{{ $do->driver_name }}</strong></div>@endif
        @if($do->vehicle_no)<div>Kendaraan: <strong>{{ $do->vehicle_no }}</strong></div>@endif
        <div>Status: <strong>{{ $do->status_label }}</strong></div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:5%">No</th>
            <th style="width:60%">Item</th>
            <th class="text-end" style="width:15%">Qty</th>
            <th style="width:20%">Batch</th>
        </tr>
    </thead>
    <tbody>
    @foreach($do->items as $i => $item)
        @php $q = (float)$item->quantity; $qF = floor($q)==$q ? number_format($q,0,',','.') : number_format($q,3,',','.'); @endphp
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>
                <div><strong>{{ $item->product->name }}</strong></div>
                <div style="font-size:11px;color:#666">{{ $item->product->sku }}</div>
                @if($item->product->pack_content_label || $item->product->pack_weight_label)
                    <div style="font-size:11px;color:#888;margin-top:2px">
                        {{ $item->product->pack_content_label }}{{ $item->product->pack_content_label && $item->product->pack_weight_label ? ' · ' : '' }}{{ $item->product->pack_weight_label }}
                    </div>
                @endif
            </td>
            <td class="text-end fw-bold">{{ $qF }} {{ $item->uom->code }}</td>
            <td style="font-size:11px">{{ $item->batch?->batch_number ?? '—' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

@if($do->notes)
    <div style="margin-top:16px;padding:10px;background:#f5f5f5;border-radius:4px;font-size:12px">
        <strong>Catatan:</strong> {{ $do->notes }}
    </div>
@endif

<div class="signatures">
    <div class="sign-box">
        <div class="role">Driver / Pengirim</div>
        <div class="line">{{ $do->driver_name ?? '_______________________' }}</div>
    </div>
    <div class="sign-box">
        <div class="role">Bagian Gudang</div>
        <div class="line">_______________________</div>
    </div>
    <div class="sign-box">
        <div class="role">Penerima</div>
        <div class="line">{{ $do->received_by_name ?? '_______________________' }}</div>
    </div>
</div>

</body>
</html>
