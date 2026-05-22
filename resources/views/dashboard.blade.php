@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('breadcrumb')
    <li class="breadcrumb-item text-gray-900">Dashboard</li>
@endsection

@section('content')
@php
    $periodLabels = ['daily' => 'Harian (30 hari)', 'weekly' => 'Mingguan (12 minggu)', 'monthly' => 'Bulanan (12 bulan)'];
    $activeLabel  = $periodLabels[$period];
@endphp

{{-- ===== Header with period selector ===== --}}
<div class="d-flex flex-wrap justify-content-between align-items-center mb-5">
    <div>
        <h2 class="fw-bolder mb-1">Ringkasan Operasional</h2>
        <span class="text-muted fs-7">Periode aktif: <strong>{{ $activeLabel }}</strong></span>
    </div>
    <div class="btn-group" role="group">
        @foreach($periodLabels as $key => $label)
            <a href="{{ url()->current() }}?period={{ $key }}"
               class="btn btn-sm {{ $period === $key ? 'btn-primary' : 'btn-light' }}">
                {{ ucfirst($key === 'daily' ? 'Harian' : ($key === 'weekly' ? 'Mingguan' : 'Bulanan')) }}
            </a>
        @endforeach
    </div>
</div>

{{-- ===== Summary cards (Period) ===== --}}
<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="card card-flush h-100 shadow-sm" style="background: linear-gradient(135deg,#fff5f0,#ffd5b8); border:0;">
            <div class="card-body d-flex align-items-center">
                <div class="symbol symbol-50px me-4">
                    <span class="symbol-label bg-danger bg-opacity-25 text-danger">
                        <i class="ki-outline ki-purchase fs-2x text-danger"></i>
                    </span>
                </div>
                <div class="flex-grow-1">
                    <div class="text-muted fs-7 fw-semibold text-uppercase">Total HPP</div>
                    <div class="fs-2 fw-bolder text-dark">Rp {{ number_format((float)$periodSummary['hpp'], 0, ',', '.') }}</div>
                    <div class="fs-8 text-muted">Modal pembelian — {{ $activeLabel }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-flush h-100 shadow-sm" style="background: linear-gradient(135deg,#e7f4ff,#bcd8ff); border:0;">
            <div class="card-body d-flex align-items-center">
                <div class="symbol symbol-50px me-4">
                    <span class="symbol-label bg-primary bg-opacity-25 text-primary">
                        <i class="ki-outline ki-handcart fs-2x text-primary"></i>
                    </span>
                </div>
                <div class="flex-grow-1">
                    <div class="text-muted fs-7 fw-semibold text-uppercase">Total Penjualan</div>
                    <div class="fs-2 fw-bolder text-dark">Rp {{ number_format((float)$periodSummary['sales'], 0, ',', '.') }}</div>
                    <div class="fs-8 text-muted">{{ number_format($periodSummary['count'], 0, ',', '.') }} order terbayar</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        @php
            $pos = $periodSummary['profit'] >= 0;
            $grad = $pos ? 'linear-gradient(135deg,#e8fbef,#a4e7bb)' : 'linear-gradient(135deg,#fdecea,#f7b8b1)';
            $col  = $pos ? 'text-success' : 'text-danger';
            $bg   = $pos ? 'bg-success'  : 'bg-danger';
        @endphp
        <div class="card card-flush h-100 shadow-sm" style="background: {{ $grad }}; border:0;">
            <div class="card-body d-flex align-items-center">
                <div class="symbol symbol-50px me-4">
                    <span class="symbol-label {{ $bg }} bg-opacity-25 {{ $col }}">
                        <i class="ki-outline ki-chart-line-up fs-2x {{ $col }}"></i>
                    </span>
                </div>
                <div class="flex-grow-1">
                    <div class="text-muted fs-7 fw-semibold text-uppercase">Laba Bersih</div>
                    <div class="fs-2 fw-bolder text-dark">Rp {{ number_format((float)$periodSummary['profit'], 0, ',', '.') }}</div>
                    <div class="fs-8 text-muted">Margin <span class="fw-bold {{ $col }}">{{ number_format($periodSummary['margin_pct'], 1, ',', '.') }}%</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== Chart ===== --}}
<div class="card mb-5 shadow-sm">
    <div class="card-header">
        <h3 class="card-title">Trend HPP, Penjualan, Laba Bersih ({{ $activeLabel }})</h3>
    </div>
    <div class="card-body">
        <div id="chart_trend" style="min-height:340px"></div>
    </div>
</div>

{{-- ===== Lifetime totals ===== --}}
<div class="row g-4 mb-5">
    <div class="col-12">
        <h4 class="fw-bolder mb-3"><i class="ki-outline ki-time fs-2 me-2"></i> Total Seluruh Transaksi</h4>
    </div>
    <div class="col-md-4">
        <div class="card card-flush h-100 shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="symbol symbol-50px me-4">
                    <span class="symbol-label bg-light-danger text-danger">
                        <i class="ki-outline ki-purchase fs-2x"></i>
                    </span>
                </div>
                <div class="flex-grow-1">
                    <div class="text-muted fs-7 fw-semibold text-uppercase">Total HPP (Lifetime)</div>
                    <div class="fs-2 fw-bolder text-dark">Rp {{ number_format((float)$lifetimeSummary['hpp'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-flush h-100 shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="symbol symbol-50px me-4">
                    <span class="symbol-label bg-light-primary text-primary">
                        <i class="ki-outline ki-handcart fs-2x"></i>
                    </span>
                </div>
                <div class="flex-grow-1">
                    <div class="text-muted fs-7 fw-semibold text-uppercase">Total Penjualan (Lifetime)</div>
                    <div class="fs-2 fw-bolder text-dark">Rp {{ number_format((float)$lifetimeSummary['sales'], 0, ',', '.') }}</div>
                    <div class="fs-8 text-muted">{{ number_format($lifetimeSummary['count'], 0, ',', '.') }} order terbayar</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        @php
            $posL = $lifetimeSummary['profit'] >= 0;
            $colL = $posL ? 'text-success' : 'text-danger';
            $bgL  = $posL ? 'bg-light-success' : 'bg-light-danger';
        @endphp
        <div class="card card-flush h-100 shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="symbol symbol-50px me-4">
                    <span class="symbol-label {{ $bgL }} {{ $colL }}">
                        <i class="ki-outline ki-chart-line-up fs-2x"></i>
                    </span>
                </div>
                <div class="flex-grow-1">
                    <div class="text-muted fs-7 fw-semibold text-uppercase">Laba Bersih (Lifetime)</div>
                    <div class="fs-2 fw-bolder text-dark">Rp {{ number_format((float)$lifetimeSummary['profit'], 0, ',', '.') }}</div>
                    <div class="fs-8 text-muted">Margin <span class="fw-bold {{ $colL }}">{{ number_format($lifetimeSummary['margin_pct'], 1, ',', '.') }}%</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== Bottom row: Stock low + Unpaid orders ===== --}}
<div class="row g-4">
    <div class="col-md-4">
        <a href="{{ route('products.index', ['stock_low' => 1]) }}" class="text-decoration-none">
            <div class="card card-flush h-100 shadow-sm" style="background: linear-gradient(135deg,#fff9e6,#ffe9a8); border:0; cursor:pointer; transition: transform .15s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform=''">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="symbol symbol-50px me-4">
                            <span class="symbol-label bg-warning bg-opacity-25 text-warning">
                                <i class="ki-outline ki-information-5 fs-2x text-warning"></i>
                            </span>
                        </div>
                        <div>
                            <div class="text-muted fs-7 fw-semibold text-uppercase">Stock di Bawah Minimum</div>
                            <div class="fs-1 fw-bolder text-dark">{{ number_format($stockLowCount, 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="text-end fs-7 fw-semibold text-warning">
                        Lihat Daftar Produk <i class="ki-outline ki-arrow-right fs-3"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md-8">
        <div class="card card-flush h-100 shadow-sm">
            <div class="card-header pt-5">
                <div class="card-title flex-column">
                    <h3 class="fw-bolder mb-1">Booking Order Belum Dibayar</h3>
                    <span class="text-muted fs-7">{{ number_format($unpaidCount, 0, ',', '.') }} order draft · Total Rp {{ number_format($unpaidTotal, 0, ',', '.') }}</span>
                </div>
                <div class="card-toolbar">
                    <a href="{{ route('sales_orders.index') }}?status=draft" class="btn btn-sm btn-light-primary">
                        Lihat Semua <i class="ki-outline ki-arrow-right fs-3"></i>
                    </a>
                </div>
            </div>
            <div class="card-body pt-3">
                <table class="table table-row-bordered align-middle gy-3">
                    <thead>
                        <tr class="fw-bold text-muted bg-light fs-7 text-uppercase">
                            <th class="ps-3">Tanggal</th>
                            <th>No. Order</th>
                            <th>Customer</th>
                            <th class="text-end pe-3">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($unpaidOrders as $o)
                            <tr>
                                <td class="ps-3 fs-7">{{ $o->order_date?->format('d M Y') }}</td>
                                <td>
                                    <a href="{{ route('sales_orders.show', $o) }}" class="text-primary fw-bold text-hover-primary">{{ $o->so_number }}</a>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $o->customer->name }}</div>
                                    <div class="text-muted fs-8">{{ $o->customer->code }}</div>
                                </td>
                                <td class="text-end pe-3 fw-bold">{{ number_format((float)$o->total_amount, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-5"><i class="ki-outline ki-check-circle fs-2x text-success me-2"></i> Semua order sudah dibayar 🎉</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof ApexCharts === 'undefined') {
        document.getElementById('chart_trend').innerHTML = '<div class="text-center text-muted py-10">Chart library tidak ter-load.</div>';
        return;
    }

    const chartData = @json($chart);

    const options = {
        chart: { type: 'area', height: 340, toolbar: { show: false }, animations: { speed: 250 } },
        colors: ['#F1416C', '#3E97FF', '#50CD89'],
        series: [
            { name: 'HPP', data: chartData.hpp },
            { name: 'Penjualan', data: chartData.sales },
            { name: 'Laba Bersih', data: chartData.profit },
        ],
        xaxis: {
            categories: chartData.labels,
            labels: { style: { fontSize: '11px', colors: '#7E8299' } },
            axisBorder: { show: false }, axisTicks: { show: false },
        },
        yaxis: {
            labels: {
                style: { colors: '#7E8299', fontSize: '11px' },
                formatter: v => 'Rp ' + Math.round(v).toLocaleString('id-ID'),
            },
        },
        stroke: { curve: 'smooth', width: 3 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 100] } },
        dataLabels: { enabled: false },
        legend: { position: 'top', horizontalAlign: 'right', fontSize: '13px' },
        grid: { borderColor: '#EFF2F5', strokeDashArray: 4 },
        tooltip: {
            y: { formatter: v => 'Rp ' + Math.round(v).toLocaleString('id-ID') },
            style: { fontSize: '13px' },
        },
        markers: { size: 4, hover: { size: 6 } },
    };

    new ApexCharts(document.getElementById('chart_trend'), options).render();
});
</script>
@endpush
@endsection
