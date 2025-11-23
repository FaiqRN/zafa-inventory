# Refactor Menu Retur Barang

## Perubahan yang Dilakukan

### Konsep Baru
Menu retur barang telah direfactor agar lebih sederhana dan mudah digunakan, mengikuti pola menu pengiriman barang:

1. **Data Otomatis Muncul**: Setiap pengiriman dengan status "terkirim" otomatis muncul di daftar retur
2. **Tidak Ada Tombol Tambah**: User tidak perlu membuat retur baru, cukup klik detail untuk mengisi data
3. **Tampilan Lebih Sederhana**: Tabel hanya menampilkan kolom penting

### Perubahan File

#### 1. Controller (`app/Http/Controllers/ReturController.php`)

**Method yang Dihapus:**
- `getPengiriman()` - tidak diperlukan lagi
- `destroy()` - tidak ada fitur hapus

**Method yang Diubah:**
- `index()` - menghapus parameter `$barang`
- `getData()` - sekarang menampilkan data dari tabel `pengiriman` (bukan `retur`)
  - Menampilkan semua pengiriman dengan status "terkirim"
  - Menampilkan badge "Belum Diisi" jika belum ada data retur
- `show($nomerPengiriman)` - mengambil data berdasarkan nomor pengiriman (bukan ID retur)
  - Menampilkan semua barang dalam pengiriman tersebut
  - Menampilkan data retur jika sudah ada
- `store()` - menyimpan data retur untuk semua barang dalam 1 pengiriman sekaligus
  - Menghapus data retur lama sebelum menyimpan yang baru
  - Validasi per item barang

#### 2. View Index (`resources/views/retur/index.blade.php`)

**Perubahan:**
- Menghapus tombol "Tambah Retur"
- Menghapus filter barang (hanya toko dan tanggal)
- Menyederhanakan tabel menjadi 6 kolom:
  - No
  - No. Pengiriman
  - Tanggal Pengiriman
  - Tanggal Retur (badge "Belum Diisi" jika kosong)
  - Toko
  - Aksi (hanya tombol Detail)
- Menghapus semua modal (tambah, detail, hapus)
- JavaScript lebih sederhana, hanya untuk DataTables dan filter

#### 3. View Detail (`resources/views/retur/show_ajax.blade.php`)

**File Baru:**
- Modal yang menampilkan informasi pengiriman
- Tabel berisi semua barang dalam pengiriman
- Form untuk mengisi data retur per barang:
  - Tanggal Retur
  - Jumlah Retur (dengan validasi max = jumlah kirim)
  - Total Terjual (auto calculate)
  - Kondisi
  - Keterangan
- Auto-calculate total terjual saat jumlah retur berubah
- Simpan semua data retur sekaligus dalam 1 form

#### 4. Routes (`routes/web.php`)

**Perubahan:**
- Menghapus route `get-pengiriman`
- Menghapus route `destroy`
- Mengubah parameter `show` dari `{id}` menjadi `{nomerPengiriman}`

#### 5. JavaScript (`public/js/retur.js`)

**Perubahan:**
- File dikosongkan karena semua JavaScript sudah inline di view
- Lebih mudah maintenance karena logic ada di satu tempat

### Alur Kerja Baru

1. **User membuka menu Retur Barang**
   - Sistem menampilkan semua pengiriman dengan status "terkirim"
   - Kolom "Tanggal Retur" menampilkan tanggal jika sudah diisi, atau badge "Belum Diisi"

2. **User klik tombol Detail**
   - Modal terbuka menampilkan informasi pengiriman
   - Tabel menampilkan semua barang dalam pengiriman tersebut
   - Jika sudah ada data retur, form terisi otomatis

3. **User mengisi/edit data retur**
   - Isi tanggal retur untuk semua barang (default: hari ini)
   - Isi jumlah retur per barang (0 jika tidak ada retur)
   - Total terjual otomatis terhitung
   - Pilih kondisi barang
   - Isi keterangan (opsional)

4. **User klik Simpan**
   - Sistem menyimpan data retur untuk semua barang sekaligus
   - Data retur lama (jika ada) akan diganti dengan yang baru
   - Modal tertutup dan tabel refresh

### Keuntungan Refactor

1. **Lebih Sederhana**: User tidak perlu mencari pengiriman yang bisa diretur
2. **Lebih Cepat**: Semua barang dalam 1 pengiriman diisi sekaligus
3. **Lebih Konsisten**: Mengikuti pola menu pengiriman
4. **Lebih Mudah Maintenance**: Code lebih sedikit dan terstruktur
5. **User Friendly**: Tidak ada step-by-step yang membingungkan

### Catatan Penting

- Data retur dapat diubah kapan saja (tidak ada fitur hapus, hanya update)
- Setiap pengiriman hanya memiliki 1 set data retur
- Validasi: jumlah retur tidak boleh melebihi jumlah kirim
- Export masih berfungsi normal dengan filter yang sama
