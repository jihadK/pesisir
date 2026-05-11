@extends('layouts.app')

@section('title', 'Catat Pembayaran')
@section('page_title', 'Catat Pembayaran')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Invoicing</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('payments.index') }}" class="text-muted">Payment</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Baru</li>
@endsection

@section('content')
<form method="POST" action="{{ route('payments.store') }}">
    @csrf
    <div class="row">
        <div class="col-md-9">
            <div class="card mb-5">
                <div class="card-header"><h3 class="card-title">Detail Pembayaran</h3></div>
                <div class="card-body">
                    <div class="row mb-4">
                        <label class="col-form-label col-md-3 fw-semibold required">Customer</label>
                        <div class="col-md-9">
                            <select name="customer_id" id="pay_customer" class="form-select form-select-solid @error('customer_id') is-invalid @enderror"
                                    data-control="select2" data-placeholder="Pilih customer..." required>
                                <option value=""></option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}" @selected(old('customer_id', $preInvoice?->customer_id)==$c->id)>{{ $c->code }} — {{ $c->name }}</option>
                                @endforeach
                            </select>
                            @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <label class="col-form-label col-md-3 fw-semibold required">Tanggal Bayar</label>
                        <div class="col-md-3">
                            <input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}"
                                   class="form-control form-control-solid @error('payment_date') is-invalid @enderror" required />
                        </div>
                        <label class="col-form-label col-md-3 fw-semibold required">Metode</label>
                        <div class="col-md-3">
                            <select name="payment_method_id" class="form-select form-select-solid @error('payment_method_id') is-invalid @enderror"
                                    data-control="select2" required>
                                <option value="">Pilih...</option>
                                @foreach($paymentMethods as $pm)
                                    <option value="{{ $pm->id }}" @selected(old('payment_method_id')==$pm->id)>{{ $pm->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <label class="col-form-label col-md-3 fw-semibold required">Jumlah</label>
                        <div class="col-md-9">
                            <div class="input-group" style="max-width:300px">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="amount" id="pay_amount" value="{{ old('amount') }}"
                                       class="form-control form-control-solid text-end @error('amount') is-invalid @enderror" required />
                            </div>
                            @error('amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <label class="col-form-label col-md-3 fw-semibold">Nomor Referensi</label>
                        <div class="col-md-9">
                            <input type="text" name="reference_no" value="{{ old('reference_no') }}"
                                   class="form-control form-control-solid" maxlength="50" placeholder="Mis. nomor transfer / kode QRIS" />
                        </div>
                    </div>

                    <div class="row mb-4">
                        <label class="col-form-label col-md-3 fw-semibold">Status</label>
                        <div class="col-md-3">
                            <select name="status" class="form-select form-select-solid">
                                <option value="cleared" @selected(old('status','cleared')=='cleared')>Cleared (sudah masuk)</option>
                                <option value="pending" @selected(old('status')=='pending')>Pending (belum konfirmasi)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <label class="col-form-label col-md-3 fw-semibold">Catatan</label>
                        <div class="col-md-9">
                            <textarea name="notes" rows="2" class="form-control form-control-solid">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-5">
                <div class="card-header">
                    <h3 class="card-title">Alokasi ke Invoice</h3>
                    <div class="card-toolbar">
                        <button type="button" class="btn btn-sm btn-light-primary" id="btn_auto_alloc">
                            <i class="ki-outline ki-magic-stick fs-3"></i> Auto-Alokasi
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-row-bordered align-middle gy-2">
                            <thead>
                                <tr class="fw-bold text-muted bg-light fs-7">
                                    <th class="ps-4"><input type="checkbox" id="chk_all" /></th>
                                    <th>No. Invoice</th>
                                    <th>Tgl Invoice</th>
                                    <th>Jatuh Tempo</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Sudah Dibayar</th>
                                    <th class="text-end">Sisa</th>
                                    <th class="text-end pe-4">Alokasi (Rp)</th>
                                </tr>
                            </thead>
                            <tbody id="invoices_body">
                                <tr><td colspan="8" class="text-center text-muted py-8">Pilih customer dulu untuk menampilkan invoice outstanding.</td></tr>
                            </tbody>
                            <tfoot class="fw-bold">
                                <tr>
                                    <td colspan="7" class="text-end">Total Alokasi</td>
                                    <td class="text-end pe-4 fs-5 text-primary" id="ft_alloc">Rp 0</td>
                                </tr>
                                <tr>
                                    <td colspan="7" class="text-end">Jumlah Pembayaran</td>
                                    <td class="text-end pe-4 fs-5" id="ft_paid">Rp 0</td>
                                </tr>
                                <tr id="row_remaining">
                                    <td colspan="7" class="text-end">Selisih</td>
                                    <td class="text-end pe-4 fs-5 fw-bold" id="ft_remaining">Rp 0</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @error('allocations')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card" style="position:sticky; top:80px">
                <div class="card-header"><h3 class="card-title">Aksi</h3></div>
                <div class="card-body d-flex flex-column gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ki-outline ki-check fs-2"></i> Simpan Pembayaran
                    </button>
                    <a href="{{ route('payments.index') }}" class="btn btn-light">Batal</a>
                </div>
            </div>

            <div class="alert alert-light-info mt-5 fs-7">
                <strong>Tips:</strong>
                <ul class="ps-3 mb-0 mt-2">
                    <li>Total alokasi harus = jumlah pembayaran</li>
                    <li>Auto-Alokasi: alokasi ke invoice terlama dulu sampai habis</li>
                    <li>Bisa pilih beberapa invoice (DP + pelunasan bareng)</li>
                </ul>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
const OUTSTANDING_URL = "{{ route('payments.outstanding-invoices') }}";
const PRE_INVOICE_ID = {{ $preInvoice?->id ?? 'null' }};

function fmtRp(v) { return 'Rp ' + Math.round(parseFloat(v)||0).toLocaleString('id-ID'); }
function unmask(s) { return parseFloat((s||'0').toString().replace(/\./g,'').replace(',','.')) || 0; }
function maskRupiah(el) {
    if (typeof Inputmask === 'undefined') return;
    Inputmask({ alias:'numeric', groupSeparator:'.', radixPoint:',', digits:0, allowMinus:false, removeMaskOnSubmit:false }).mask(el);
}

async function loadInvoices(customerId) {
    const tbody = document.getElementById('invoices_body');
    if (! customerId) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-8">Pilih customer dulu.</td></tr>';
        return;
    }
    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-8"><span class="spinner-border spinner-border-sm"></span> Loading...</td></tr>';

    const res = await fetch(`${OUTSTANDING_URL}?customer_id=${customerId}`, {headers:{'Accept':'application/json'}});
    const json = await res.json();
    const invs = json.data.invoices || [];

    if (! invs.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-8">Tidak ada invoice outstanding untuk customer ini.</td></tr>';
        recalcTotals();
        return;
    }

    tbody.innerHTML = invs.map((inv, idx) => {
        const isPre = (inv.id == PRE_INVOICE_ID);
        return `
        <tr data-outstanding="${inv.outstanding}">
            <td class="ps-4">
                <input type="checkbox" class="inv-chk" data-idx="${idx}" ${isPre ? 'checked' : ''} />
                <input type="hidden" name="allocations[${idx}][invoice_id]" value="${inv.id}" />
            </td>
            <td><span class="fw-bold text-primary">${inv.invoice_number}</span></td>
            <td class="fs-7">${inv.invoice_date}</td>
            <td class="fs-7">${inv.due_date ?? '—'}</td>
            <td class="text-end">${fmtRp(inv.total_amount)}</td>
            <td class="text-end text-success">${fmtRp(inv.paid_amount)}</td>
            <td class="text-end fw-bold text-danger">${fmtRp(inv.outstanding)}</td>
            <td class="text-end pe-4">
                <div class="input-group input-group-sm" style="max-width:160px;margin-left:auto">
                    <span class="input-group-text">Rp</span>
                    <input type="text" name="allocations[${idx}][amount]" class="form-control form-control-sm form-control-solid text-end alloc-input"
                           value="${isPre ? Math.round(inv.outstanding).toLocaleString('id-ID') : ''}" />
                </div>
            </td>
        </tr>`;
    }).join('');

    // Mask & event
    document.querySelectorAll('.alloc-input').forEach(el => {
        maskRupiah(el);
        el.addEventListener('input', recalcTotals);
    });
    document.querySelectorAll('.inv-chk').forEach(chk => {
        chk.addEventListener('change', function () {
            const tr = this.closest('tr');
            const allocEl = tr.querySelector('.alloc-input');
            if (this.checked) {
                if (! allocEl.value) {
                    allocEl.value = Math.round(parseFloat(tr.dataset.outstanding)).toLocaleString('id-ID');
                }
            } else {
                allocEl.value = '';
            }
            recalcTotals();
        });
    });

    recalcTotals();
}

function recalcTotals() {
    let total = 0;
    document.querySelectorAll('.alloc-input').forEach(el => total += unmask(el.value));
    const paidAmount = unmask(document.getElementById('pay_amount').value);
    const remaining = paidAmount - total;

    document.getElementById('ft_alloc').textContent = fmtRp(total);
    document.getElementById('ft_paid').textContent = fmtRp(paidAmount);
    const remEl = document.getElementById('ft_remaining');
    remEl.textContent = fmtRp(Math.abs(remaining));
    remEl.classList.remove('text-success','text-danger','text-muted');
    if (Math.abs(remaining) < 0.5) remEl.classList.add('text-success');
    else remEl.classList.add('text-danger');
}

function autoAllocate() {
    let remaining = unmask(document.getElementById('pay_amount').value);
    if (remaining <= 0) {
        alert('Isi jumlah pembayaran dulu.');
        return;
    }
    document.querySelectorAll('#invoices_body tr').forEach(tr => {
        const outstanding = parseFloat(tr.dataset.outstanding) || 0;
        const chk = tr.querySelector('.inv-chk');
        const inp = tr.querySelector('.alloc-input');
        if (! chk || ! inp) return;

        if (remaining <= 0) {
            chk.checked = false; inp.value = '';
        } else {
            const take = Math.min(remaining, outstanding);
            chk.checked = true;
            inp.value = Math.round(take).toLocaleString('id-ID');
            remaining -= take;
        }
    });
    recalcTotals();
}

document.addEventListener('DOMContentLoaded', () => {
    maskRupiah(document.getElementById('pay_amount'));
    document.getElementById('pay_amount').addEventListener('input', recalcTotals);

    const $ = window.jQuery;
    $('#pay_customer').on('change', function () { loadInvoices(this.value); });
    document.getElementById('btn_auto_alloc').addEventListener('click', autoAllocate);

    // Pre-load kalau customer sudah ke-set (dari preInvoice)
    if ($('#pay_customer').val()) {
        $('#pay_customer').trigger('change');
    }
});
</script>
@endpush
@endsection
