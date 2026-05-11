<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
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
        .totals .paid-row { color: #2e7d32; }
        .totals .due-row { font-size: 17px; font-weight: bold; color: #d32f2f; border-top: 2px solid #d32f2f; padding-top: 6px; }
        .stamp { margin-top: 18px; padding: 12px 16px; border-radius: 6px; text-align: center; font-weight: bold; font-size: 24px; letter-spacing: 4px; }
        .stamp-paid { background: #e8f5e9; color: #2e7d32; border: 2px dashed #2e7d32; }
        .stamp-due  { background: #ffebee; color: #d32f2f; border: 2px dashed #d32f2f; }
        @media print { body { padding: 0; } .no-print { display: none !important; } }
        .toolbar { text-align: right; margin-bottom: 12px; }
        .btn { padding: 8px 14px; background:#1976d2; color: white; border-radius:4px; text-decoration:none; display:inline-block; font-size:13px; border:none; cursor:pointer; margin-left:6px; }
    </style>
</head>
<body>

<div class="toolbar no-print">
    <button class="btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
    <a href="{{ route('invoices.show', $invoice) }}" class="btn" style="background:#666">← Kembali</a>
</div>

@php
    $outstanding = (float) $invoice->total_amount - (float) $invoice->paid_amount;
@endphp

<div class="header">
    <div class="brand">
        <img src="{{ asset('storage/logo/logo-pesisir-web.png') }}" alt="Logo" onerror="this.style.display='none'" />
        <div>
            <h1>{{ config('app.name', 'Pesisir Fresh Fish') }}</h1>
            <div class="tagline">Ikan Segar dari Laut Pesisir</div>
            <div class="tagline"><strong>INVOICE</strong></div>
        </div>
    </div>
    <div class="doc-info">
        <div><strong>{{ $invoice->invoice_number }}</strong></div>
        <div>Tgl: {{ $invoice->invoice_date?->format('d M Y') }}</div>
        <div>Jatuh tempo: <strong>{{ $invoice->due_date?->format('d M Y') }}</strong></div>
    </div>
</div>

<div class="meta">
    <div>
        <h3>Customer</h3>
        <div><strong>{{ $invoice->customer->name }}</strong></div>
        <div>{{ $invoice->customer->code }}</div>
        @if($invoice->customer->phone)<div>📞 {{ $invoice->customer->phone }}</div>@endif
        @if($invoice->customer->address)<div style="font-size:11px;color:#666;margin-top:2px">{{ $invoice->customer->address }}</div>@endif
    </div>
    <div>
        <h3>Referensi</h3>
        @if($invoice->salesOrder)<div>SO: <strong>{{ $invoice->salesOrder->so_number }}</strong></div>@endif
        @if($invoice->deliveryOrder)<div>DO: <strong>{{ $invoice->deliveryOrder->do_number }}</strong></div>@endif
        <div>Term: <strong>{{ $invoice->payment_terms_days }} hari</strong></div>
        <div>Status: <strong>{{ $invoice->status_label }}</strong></div>
    </div>
</div>

<table class="items">
    <thead>
        <tr>
            <th style="width:50%">Item</th>
            <th class="text-end" style="width:15%">Qty</th>
            <th class="text-end" style="width:15%">Harga</th>
            <th class="text-end" style="width:20%">Subtotal</th>
        </tr>
    </thead>
    <tbody>
    @foreach($invoice->items as $item)
        @php $q = (float)$item->quantity; $qF = floor($q)==$q ? number_format($q,0,',','.') : number_format($q,3,',','.'); @endphp
        <tr>
            <td>
                <div><strong>{{ $item->product->name }}</strong></div>
                <div style="font-size:11px;color:#666">{{ $item->product->sku }}</div>
            </td>
            <td class="text-end">{{ $qF }} {{ $item->uom->code }}</td>
            <td class="text-end">Rp {{ number_format((float)$item->unit_price, 0, ',', '.') }}</td>
            <td class="text-end">Rp {{ number_format((float)$item->subtotal, 0, ',', '.') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="totals">
    <tr><td>Subtotal</td><td class="text-end">Rp {{ number_format((float)$invoice->subtotal, 0, ',', '.') }}</td></tr>
    @if((float)$invoice->discount_amount > 0)
        <tr><td>Diskon</td><td class="text-end">−Rp {{ number_format((float)$invoice->discount_amount, 0, ',', '.') }}</td></tr>
    @endif
    @if((float)$invoice->shipping_cost > 0)
        <tr><td>Ongkir</td><td class="text-end">Rp {{ number_format((float)$invoice->shipping_cost, 0, ',', '.') }}</td></tr>
    @endif
    <tr class="total-row"><td>TOTAL</td><td class="text-end">Rp {{ number_format((float)$invoice->total_amount, 0, ',', '.') }}</td></tr>
    @if((float)$invoice->paid_amount > 0)
        <tr class="paid-row"><td>Sudah Dibayar</td><td class="text-end">Rp {{ number_format((float)$invoice->paid_amount, 0, ',', '.') }}</td></tr>
    @endif
    @if($outstanding > 0)
        <tr class="due-row"><td>SISA TAGIHAN</td><td class="text-end">Rp {{ number_format($outstanding, 0, ',', '.') }}</td></tr>
    @endif
</table>

@if($invoice->status === \App\Models\Invoice::STATUS_PAID)
    <div class="stamp stamp-paid">✓ LUNAS</div>
@elseif($outstanding > 0 && $invoice->due_date && $invoice->due_date->isPast())
    <div class="stamp stamp-due">⚠ OVERDUE</div>
@endif

@if($invoice->payments->isNotEmpty())
    <div style="margin-top:16px;font-size:11px;color:#666">
        <strong>Riwayat Pembayaran:</strong>
        <ul style="margin:4px 0;padding-left:18px">
        @foreach($invoice->payments as $pay)
            <li>{{ \Illuminate\Support\Carbon::parse($pay->payment_date)->format('d M Y') }} — {{ $pay->paymentMethod->name }} — Rp {{ number_format((float)$pay->pivot->allocated_amount, 0, ',', '.') }} ({{ $pay->payment_number }})</li>
        @endforeach
        </ul>
    </div>
@endif

@if($invoice->notes)
    <div style="margin-top:16px;padding:10px;background:#f5f5f5;border-radius:4px;font-size:12px">
        <strong>Catatan:</strong> {{ $invoice->notes }}
    </div>
@endif

<div style="margin-top:30px;text-align:center;font-size:11px;color:#888;border-top:1px dashed #ddd;padding-top:12px">
    Terima kasih atas pembelian Anda. Mohon konfirmasi pembayaran setelah transfer 🙏
</div>

</body>
</html>
