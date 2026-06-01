<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentMethod\StorePaymentMethodRequest;
use App\Http\Requests\PaymentMethod\UpdatePaymentMethodRequest;
use App\Models\PaymentMethod;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PaymentMethodController extends Controller
{
    public function index(): View
    {
        return view('payment_methods.index', [
            'methods' => PaymentMethod::ordered()->get(),
        ]);
    }

    public function create(): View
    {
        return view('payment_methods.create', [
            'method' => new PaymentMethod(['is_active' => true, 'display_order' => 100]),
            'types'  => PaymentMethod::typeLabels(),
        ]);
    }

    public function store(StorePaymentMethodRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if ($request->hasFile('qris_image')) {
            $data['qris_image_url'] = $this->uploadQris($request->file('qris_image'), $data['code']);
        }
        unset($data['qris_image']);

        $method = PaymentMethod::create($data);

        return redirect()
            ->route('payment_methods.index')
            ->with('flash', Flash::ok("Metode pembayaran '{$method->name}' berhasil ditambahkan.", 'Berhasil Disimpan'));
    }

    public function edit(PaymentMethod $paymentMethod): View
    {
        return view('payment_methods.edit', [
            'method' => $paymentMethod,
            'types'  => PaymentMethod::typeLabels(),
        ]);
    }

    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $data = $request->validated();

        if ($request->boolean('remove_qris')) {
            $this->deleteQris($paymentMethod->qris_image_url);
            $data['qris_image_url'] = null;
        }
        if ($request->hasFile('qris_image')) {
            $newUrl = $this->uploadQris($request->file('qris_image'), $data['code']);
            if ($newUrl) {
                $this->deleteQris($paymentMethod->qris_image_url);
                $data['qris_image_url'] = $newUrl;
            }
        }
        unset($data['qris_image'], $data['remove_qris']);

        $paymentMethod->update($data);

        return redirect()
            ->route('payment_methods.index')
            ->with('flash', Flash::ok("Metode pembayaran '{$paymentMethod->name}' berhasil diperbarui.", 'Berhasil Diperbarui'));
    }

    /**
     * Halaman publik viewer QRIS — menampilkan gambar + tombol Download.
     * Dipakai sebagai link QRIS di pesan WhatsApp ke customer.
     */
    public function qrisView(PaymentMethod $paymentMethod): View
    {
        abort_unless($paymentMethod->qris_image_url, 404, 'QRIS belum diupload.');
        return view('payment_methods.qris_view', ['method' => $paymentMethod]);
    }

    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        $name = $paymentMethod->name;
        $this->deleteQris($paymentMethod->qris_image_url);
        $paymentMethod->delete();

        return redirect()
            ->route('payment_methods.index')
            ->with('flash', Flash::ok("Metode pembayaran '{$name}' berhasil dihapus.", 'Berhasil Dihapus'));
    }

    private function uploadQris(\Illuminate\Http\UploadedFile $file, string $code): ?string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        $filename = Str::slug($code) . '-' . Str::random(6) . '.' . $ext;
        $path = $file->storeAs('public/payment', $filename);
        return $path ? '/storage/payment/' . $filename : null;
    }

    private function deleteQris(?string $url): void
    {
        if (! $url) return;
        if (! str_starts_with($url, '/storage/payment/')) return;
        $relative = 'public/' . substr($url, strlen('/storage/'));
        if (Storage::exists($relative)) {
            Storage::delete($relative);
        }
    }
}
