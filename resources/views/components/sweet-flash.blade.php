{{--
    Auto-render SweetAlert2 dari session flash.
    Pakai di akhir layout master, sebelum @stack('scripts').

    Sumber flash:
      1. session('flash') = ['resCode'=>..., 'resMsg'=>..., 'title'=>..., 'icon'=>...]  (format baru via Flash helper)
      2. session('success') / session('error')                                          (format lama, fallback)
      3. $errors->any() (validation errors)
--}}

@php
    $flash = session('flash');
    $legacySuccess = session('success');
    $legacyError = session('error');
    $hasValidationErrors = isset($errors) && $errors->any();
@endphp

@if ($flash || $legacySuccess || $legacyError || $hasValidationErrors)
<script>
document.addEventListener('DOMContentLoaded', function () {
    @if ($flash)
        // ----- Flash format baru: {resCode, resMsg, title, icon} -----
        @if (\App\Support\ResponseCode::isSuccess($flash['resCode']))
            // SUCCESS → toast kanan atas (non-blocking)
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: '{{ $flash['icon'] ?? 'success' }}',
                title: @json($flash['resMsg']),
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                showCloseButton: true
            });
        @else
            // ERROR → modal tengah dengan kode error
            Swal.fire({
                icon: '{{ $flash['icon'] ?? 'error' }}',
                title: @json($flash['title'] ?? 'Gagal'),
                html: `<div>${@json($flash['resMsg'])}</div><div class="text-muted fs-7 mt-3">Kode: <code>{{ $flash['resCode'] }}</code></div>`,
                confirmButtonText: 'OK',
                customClass: { confirmButton: 'btn btn-primary' },
                buttonsStyling: false
            });
        @endif
    @endif

    @if ($legacySuccess && !$flash)
        Swal.fire({
            toast: true, position: 'top-end', icon: 'success',
            title: @json($legacySuccess),
            showConfirmButton: false, timer: 3000, timerProgressBar: true, showCloseButton: true
        });
    @endif

    @if ($legacyError && !$flash)
        Swal.fire({
            icon: 'error', title: 'Gagal',
            html: `<div>${@json($legacyError)}</div><div class="text-muted fs-7 mt-3">Kode: <code>{{ \App\Support\ResponseCode::SERVER_ERROR }}</code></div>`,
            confirmButtonText: 'OK',
            customClass: { confirmButton: 'btn btn-primary' }, buttonsStyling: false
        });
    @endif

    @if ($hasValidationErrors)
        Swal.fire({
            icon: 'warning',
            title: 'Validasi Gagal',
            html: `<ul class="text-start mb-0">{!! collect($errors->all())->map(fn($e) => '<li>'.e($e).'</li>')->implode('') !!}</ul><div class="text-muted fs-7 mt-3">Kode: <code>{{ \App\Support\ResponseCode::VALIDATION_ERROR }}</code></div>`,
            confirmButtonText: 'OK',
            customClass: { confirmButton: 'btn btn-warning' }, buttonsStyling: false
        });
    @endif
});
</script>
@endif
