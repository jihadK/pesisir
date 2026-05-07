{{--
    Helper JavaScript global untuk konfirmasi pakai SweetAlert2.
    Pakai di setiap layout (auth + app) supaya semua tombol bisa pakai.

    Cara pakai di view:
      <button data-sweet-confirm
              data-sweet-title="Yakin?"
              data-sweet-text="Tindakan ini tidak bisa dibatalkan."
              data-sweet-confirm-text="Ya, hapus"
              data-sweet-icon="warning"
              data-sweet-form="#kt_delete_form_42">Hapus</button>

      <form id="kt_delete_form_42" method="POST" action="...">@csrf @method('DELETE')</form>

    Atau buat form dengan attribute langsung:
      <form data-sweet-confirm data-sweet-text="Yakin..." method="POST" action="...">
--}}

<script>
(function () {
    'use strict';

    /**
     * Show SweetAlert2 confirmation. Returns Promise.
     */
    window.sweetConfirm = function (opts) {
        opts = opts || {};
        return Swal.fire({
            icon: opts.icon || 'warning',
            title: opts.title || 'Apakah Anda yakin?',
            text: opts.text || '',
            html: opts.html || null,
            showCancelButton: true,
            confirmButtonText: opts.confirmText || 'Ya, lanjutkan',
            cancelButtonText: opts.cancelText || 'Batal',
            customClass: {
                confirmButton: opts.confirmClass || 'btn btn-primary me-2',
                cancelButton:  opts.cancelClass  || 'btn btn-light'
            },
            buttonsStyling: false,
            reverseButtons: true
        });
    };

    /**
     * Auto-bind buttons/forms dengan attribute data-sweet-confirm.
     */
    function bindSweetConfirm(scope) {
        scope = scope || document;

        // Buttons → submit referenced form on confirm
        scope.querySelectorAll('[data-sweet-confirm]').forEach(function (el) {
            if (el.dataset.sweetBound === '1') return;
            el.dataset.sweetBound = '1';

            // Handle FORM elements: intercept submit
            if (el.tagName === 'FORM') {
                el.addEventListener('submit', function (e) {
                    if (this.dataset.sweetConfirmed === '1') return; // sudah di-confirm
                    e.preventDefault();
                    var form = this;
                    sweetConfirm({
                        title:        form.dataset.sweetTitle,
                        text:         form.dataset.sweetText,
                        html:         form.dataset.sweetHtml,
                        icon:         form.dataset.sweetIcon,
                        confirmText:  form.dataset.sweetConfirmText,
                        confirmClass: form.dataset.sweetConfirmClass,
                    }).then(function (r) {
                        if (r.isConfirmed) {
                            form.dataset.sweetConfirmed = '1';
                            form.submit();
                        }
                    });
                });
                return;
            }

            // Buttons / links → handle click
            el.addEventListener('click', function (e) {
                e.preventDefault();
                var btn = this;
                sweetConfirm({
                    title:        btn.dataset.sweetTitle,
                    text:         btn.dataset.sweetText,
                    html:         btn.dataset.sweetHtml,
                    icon:         btn.dataset.sweetIcon,
                    confirmText:  btn.dataset.sweetConfirmText,
                    confirmClass: btn.dataset.sweetConfirmClass,
                }).then(function (r) {
                    if (! r.isConfirmed) return;
                    var formSel = btn.dataset.sweetForm;
                    if (formSel) {
                        var form = document.querySelector(formSel);
                        if (form) {
                            form.dataset.sweetConfirmed = '1';
                            form.submit();
                            return;
                        }
                    }
                    // Kalau button di dalam form, submit parent form
                    var parentForm = btn.closest('form');
                    if (parentForm) {
                        parentForm.dataset.sweetConfirmed = '1';
                        parentForm.submit();
                    }
                });
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () { bindSweetConfirm(); });
    // Re-bind setelah DataTable redraw (kalau perlu)
    window.bindSweetConfirm = bindSweetConfirm;
})();
</script>
