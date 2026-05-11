---
title: "Manual Book Aplikasi Pesisir Fresh Fish"
subtitle: "Panduan Penggunaan untuk Staf & Admin"
author: "Pesisir Fresh Fish"
date: "Mei 2026"
---

# Manual Book Aplikasi Pesisir Fresh Fish

**Versi:** 1.0 (Phase 5a)
**Tanggal:** Mei 2026
**URL Aplikasi:** https://pesisirfreshfish.web.id

---

# Daftar Isi

1. Pendahuluan
2. Cara Login & Navigasi
3. Master Data
4. Inventory (Stock)
5. Penjualan
6. Konfigurasi
7. Pertanyaan Umum (FAQ)

---

# 1. Pendahuluan

Aplikasi **Pesisir Fresh Fish** adalah sistem manajemen stok dan penjualan ikan segar berbasis web. Aplikasi ini membantu Anda:

- Mengelola data produk ikan dengan informasi pack lengkap (berat, isi, harga)
- Mencatat stok awal saat go-live & koreksi stok
- Membuat Sales Order (SO) dan mencetak Proforma Invoice untuk dikirim ke customer
- Mengirim tagihan via WhatsApp dengan satu klik
- Tracking riwayat mutasi stok per produk

Aplikasi ini berjalan di browser (Chrome, Firefox, Edge) di komputer/laptop. Tidak perlu install software tambahan.

---

# 2. Cara Login & Navigasi

## 2.1 Login

1. Buka browser, masuk ke `https://pesisirfreshfish.web.id`
2. Masukkan **Username** dan **Password** yang sudah dibagikan admin
3. Klik tombol **Login**

> Kalau lupa password, hubungi admin untuk reset.

## 2.2 Mengenal Layar Utama

Setelah login, Anda akan melihat 3 bagian utama:

1. **Sidebar kiri** — daftar menu sesuai hak akses Anda
2. **Header atas** — info user yang sedang login, tombol logout
3. **Konten tengah** — halaman yang sedang dibuka

## 2.3 Logout

Klik **foto profil** di pojok kanan atas → pilih **Logout**.

---

# 3. Master Data

Master Data adalah data dasar yang harus diisi dulu sebelum mencatat transaksi.

## 3.1 Produk

**Fungsi:** Mengelola katalog produk ikan yang Anda jual.

### Cara Menambah Produk Baru

1. Buka menu **Produk → Daftar Produk**
2. Klik tombol **+ Tambah Produk**
3. Isi form sesuai urutan:
   - **Sub-Kategori**: pilih jenis ikan (mis. Tuna, Dorang). Group otomatis ikut dari kategori induk.
   - **Grade**: pilih kualitas (A/B/C)
   - **SKU**: klik tombol **🔄 Generate** — kode otomatis (mis. `FISH-TUNA-A-001`)
   - **Nama Produk**: nama lengkap produk
   - **Spesifikasi Pack**:
     - Tipe isi: **Ekor** (ikan utuh) atau **Potong** (fillet)
     - Jumlah isi per pack: tetap atau range (mis. 4–5 potong)
     - Berat per pack: tetap atau range (mis. 200–215 g)
   - **Harga**:
     - Cost (HPP): harga beli/produksi
     - Margin %: target keuntungan (mis. 30%)
     - Harga Jual: **otomatis dihitung**, dibulatkan ke kelipatan 1.000
     - Untung Bersih: tampil otomatis di bawahnya
4. Klik **Simpan Produk**

### Cara Edit Produk

1. Cari produk di list, klik tombol **edit** (ikon pensil)
2. Ubah data yang perlu
3. **Catatan:** SKU tidak bisa diubah setelah dibuat (untuk menjaga konsistensi laporan)

### Cara Hapus Produk

Klik tombol **trash** di list. Produk hanya bisa dihapus kalau **belum ada transaksi** yang menyentuhnya.

## 3.2 Kategori

**Fungsi:** Mengelompokkan produk dalam hierarki 2 level: Group (mis. `FISH`) → Sub-Group (mis. `TUNA`).

### Cara Menambah Sub-Kategori Baru

1. Buka menu **Produk → Kategori**
2. Klik **+ Tambah Kategori**
3. Isi:
   - **Nama**: nama kategori (mis. "Kembung")
   - **Kode**: 4-10 huruf kapital (mis. "KEMB"). Dipakai untuk SKU produk
   - **Kategori Induk**: pilih group induk (mis. "Ikan Laut")
4. Klik **Simpan**

## 3.3 Grade Produk

**Fungsi:** Mengelola tingkatan kualitas ikan (Premium, Standar, dll).

Setiap grade punya:
- **Kode**: huruf singkat (A, B, C)
- **Nama**: deskripsi grade
- **Warna**: untuk badge visual

## 3.4 Satuan (UoM)

**Fungsi:** Master satuan unit (Pack, Kg, Pcs, Box). Default produk = "Pack".

## 3.5 Tier Harga

**Fungsi:** Tingkatan harga jual (Retail, Grosir, Reseller, Restoran). Dipakai untuk segmentasi customer.

## 3.6 Supplier

**Fungsi:** Daftar supplier (nelayan, pengepul, dll) tempat Anda beli ikan.

### Cara Menambah Supplier

1. Buka **Mitra → Supplier**
2. Klik **+ Tambah Supplier**
3. Isi: Kode, Nama, Kontak, Telepon, Email, Alamat, Bank, dll
4. Simpan

## 3.7 Customer

**Fungsi:** Daftar pelanggan yang membeli ikan dari Anda.

### Cara Menambah Customer

1. Buka **Mitra → Customer**
2. Klik **+ Tambah Customer**
3. Isi:
   - **Tipe**: individu/corporate/reseller/restoran/pasar
   - **Tier Harga**: default tier kalau order
   - **Kredit Limit**: maksimal piutang (Rp)
   - **Term Pembayaran**: berapa hari boleh nunda bayar (0 = cash)
   - **Nomor HP**: penting untuk kirim tagihan via WA

## 3.8 Warehouse

**Fungsi:** Master gudang penyimpanan (mis. Gudang Lamongan, Cold Storage A).

---

# 4. Inventory (Stock)

Bagian ini untuk mengelola stok masuk-keluar.

## 4.1 Stock Opening

**Fungsi:** Input saldo awal stok saat pertama kali pakai aplikasi (go-live).

### Aturan Penting

- **Sekali pakai per produk per gudang** — tidak bisa diulang
- Setelah ada transaksi (penjualan/koreksi), opening **tidak bisa lagi** untuk produk itu
- Untuk koreksi, pakai **Stock Adjustment**

### Cara Input Stock Opening

1. Buka **Stock → Stock Opening**
2. Klik **+ Stock Opening Baru**
3. Pilih **Warehouse**
4. Tambah baris item:
   - Pilih produk
   - Isi Qty
   - Isi Cost (harga pokok per pack)
   - Untuk ikan perishable: isi Production Date & Expiry Date (boleh dikosongkan — sistem auto-generate batch)
5. Tambah baris lagi (banyak produk sekaligus boleh)
6. Klik **Simpan**

## 4.2 Stock Adjustment

**Fungsi:** Koreksi stok rutin — barang rusak, hilang, expired, atau hasil opname.

### Cara Buat Adjustment

1. Buka **Stock → Stock Adjustment**
2. Klik **+ Adjustment Baru**
3. Pilih **Warehouse** dan **Produk**
4. (Opsional) Pilih **Batch** spesifik (untuk perishable)
5. Pilih **Tipe**: Tambah (+) atau Kurang (−)
6. Pilih **Alasan**:
   - **Rusak** — barang fisik rusak
   - **Expired** — kadaluarsa
   - **Hilang** — barang hilang
   - **Koreksi Lebih/Kurang** — hasil opname (hitung fisik beda dengan sistem)
   - **Lainnya** — alasan lain
7. Isi **Qty**
8. Isi **Catatan** (wajib min. 5 karakter, untuk audit)
9. Klik **Simpan**

> **Catatan keamanan:** Adjustment tidak bisa diubah/hapus setelah disimpan. Cek dua kali sebelum klik simpan.

## 4.3 Kartu Stok

**Fungsi:** Lihat riwayat mutasi stok per produk (read-only).

### Cara Pakai

1. Buka **Stock → Kartu Stok**
2. Cari produk yang ingin dilihat, klik **Lihat Kartu**
3. Halaman detail menampilkan:
   - **Saldo per Warehouse** (sidebar kiri)
   - **Ringkasan Periode** (Total Masuk, Keluar, Net)
   - **Riwayat Mutasi** lengkap dengan filter tanggal & warehouse

### Memahami "Jumlah Mutasi"

Jumlah mutasi = berapa kali ada transaksi (bukan jumlah qty). Contoh:
- 1× Stock Opening qty 100 = 1 mutasi
- 5× Penjualan, masing-masing 10 pack = 5 mutasi

---

# 5. Penjualan

## 5.1 Sales Order (SO)

**Fungsi:** Mencatat order dari customer. SO bisa dicetak sebagai Proforma Invoice untuk dikirim ke customer.

### Status SO

| Status | Arti |
|---|---|
| Draft | Order baru, masih bisa diedit. Stock **belum** di-reserve. |
| Confirmed | Order sudah valid. Stock **sudah** di-reserve untuk customer ini. |
| Partial Delivered | Sebagian sudah dikirim |
| Delivered | Sudah dikirim semua |
| Invoiced | Sudah di-invoice |
| Cancelled | Dibatalkan. Reserved stock kembali. |

### Cara Buat Sales Order

1. Buka **Sales → Sales Order**
2. Klik **+ SO Baru**
3. Isi header:
   - **Customer**: pilih dari daftar
   - **Warehouse**: gudang asal pengiriman
   - **Tanggal Order**: default hari ini
   - **Tanggal Kirim**: centang "**Sama dengan tanggal order**" kalau langsung kirim, atau pilih tanggal lain
   - **Term Pembayaran**: auto-fill dari customer, bisa diubah
   - **Metode Pembayaran**: pilih (TF-BCA, TF-BRI, TF-MANDIRI, QRIS, COD). Bisa kosong.
4. Tambah item:
   - Pilih produk dari dropdown (tampil info pack: "4–5 potong, 200–215 g")
   - Cek "**Tersedia: N**" di bawah qty (hijau = aman, merah = qty melebihi stok)
   - Isi Qty, Harga (auto-fill dari master), Disc %
5. Klik **Simpan SO (Draft)**
6. Setelah masuk halaman detail, klik **Confirm** untuk reserve stock & lock SO

### Cara Edit SO Draft

Hanya SO status **Draft** yang bisa diedit. Setelah confirmed, harus cancel dulu (stock kembali) baru bisa edit ulang.

### Cara Cancel SO

- SO Draft → langsung cancel, tidak ada efek stok
- SO Confirmed → cancel akan **release reserved stock** kembali

## 5.2 Cetak Proforma & Kirim ke Customer

### Cara Cetak Proforma (Tagihan)

1. Buka halaman detail SO yang sudah confirmed
2. Klik **🖨️ Cetak / Proforma**
3. Halaman cetak terbuka di tab baru, isinya:
   - Logo & nama perusahaan
   - Info customer & order
   - Daftar item dengan harga
   - Total
   - **Cara Pembayaran**: semua bank transfer + pilihan customer (highlight hijau dengan tag "✓ PILIHAN ANDA")
4. Klik **🖨️ Print / Save as PDF** untuk download sebagai PDF

### Cara Kirim Proforma via WhatsApp

1. Di halaman cetak Proforma, klik tombol **💬 Kirim ke WhatsApp Customer** (hijau)
2. WhatsApp Web terbuka dengan chat customer + teks tagihan pre-fill
3. Tekan **Enter** untuk kirim teks
4. Klik **📎 (Attach)** di WA → pilih PDF Proforma yang sudah disave
5. Kirim

> **Syarat:** Customer harus punya **nomor HP** di master data. Kalau belum, tombol WhatsApp jadi abu-abu.
>
> **Catatan:** Pesan terkirim dari nomor WhatsApp yang sedang login di WA Web di browser Anda. Pastikan login pakai nomor Pesisir (bukan nomor pribadi staf).

### Cara Ganti Metode Pembayaran

Kalau customer minta ganti metode (mis. awalnya QRIS, lalu mau transfer BCA):

1. Buka detail SO
2. Di section "Metode Pembayaran", klik tombol **Ganti**
3. Pilih metode baru, klik **Update**
4. Cetak ulang Proforma — info pembayaran sudah update

---

# 6. Konfigurasi

## 6.1 Metode Pembayaran

**Fungsi:** Mengelola daftar rekening bank, QRIS, dan COD yang muncul di Proforma.

### Cara Edit Info Rekening

1. Buka **Konfigurasi → Metode Pembayaran**
2. Cari metode yang ingin diubah, klik **edit**
3. Update info bank, nomor rekening, atau atas nama
4. Untuk QRIS: upload gambar QR code (PNG/JPG, maks 1MB)
5. **Urutan Tampil**: angka kecil tampil duluan di Proforma

### Cara Nonaktifkan Metode

Edit metode → uncheck **Aktif** → Simpan. Metode tidak akan muncul lagi di Proforma & dropdown SO.

---

# 7. Pertanyaan Umum (FAQ)

**Q: Kenapa saya tidak bisa Confirm SO? Muncul error "Stock tidak cukup"?**
A: Stock tersedia (Total − Reserved) kurang dari qty SO. Cek di Kartu Stok produk. Solusi: kurangi qty SO atau tambah stock dulu via Stock Opening/Adjustment.

**Q: Reserved stock itu apa?**
A: Stok yang sudah "diijonkan" untuk SO confirmed yang belum dikirim. Stok ini tidak bisa dipakai SO lain meski fisiknya masih di gudang.

**Q: Bagaimana cara hapus SO yang salah input?**
A: SO tidak bisa di-hapus, hanya bisa di-**cancel**. Status jadi "Cancelled" dan reserved stock kembali.

**Q: Kenapa tombol WhatsApp abu-abu?**
A: Customer belum punya nomor HP di master data. Edit Customer → isi field Phone → Save.

**Q: Logo / QRIS tidak muncul di Proforma?**
A: File belum di-upload ke server, atau path-nya salah. Hubungi admin.

**Q: Bagaimana cara backup data?**
A: Backup dilakukan otomatis oleh server (cron pg_dump harian). Hubungi admin untuk download backup.

**Q: Saya lupa password.**
A: Hubungi admin untuk reset. Admin masuk ke menu Manajemen User → cari user → klik Reset Password.

**Q: Bisa diakses dari HP?**
A: Bisa via browser HP (Chrome/Safari). Tampilan responsive, tapi pengalaman terbaik di laptop/desktop dengan layar lebih lebar.

---

# Penutup

Aplikasi ini terus dikembangkan. Phase berikutnya akan menambahkan:

- **Delivery Order (DO)** — surat jalan + stock otomatis berkurang
- **Invoice & Payment** — faktur resmi + tracking pembayaran
- **Purchase Order (PO)** — order ke supplier
- **Laporan** — penjualan, stok, profit, dll

Untuk bantuan teknis atau saran fitur, hubungi admin sistem.

**Selamat menggunakan!** 🐟
