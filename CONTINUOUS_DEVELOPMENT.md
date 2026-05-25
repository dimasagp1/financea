# FinanceHBT - Continuous Development Documentation

Dokumen ini memuat panduan komprehensif mengenai **Alur Sistem**, **Rules (Aturan Pengembangan)**, dan **Responsibility (Tanggung Jawab)** untuk memastikan kelancaran dan konsistensi dalam *continuous development* proyek FinanceHBT.

---

## 1. ALUR SISTEM (SYSTEM FLOW)

Sistem FinanceHBT dirancang untuk memonitor, mengelola, dan memproyeksikan anggaran keuangan (budgeting) perusahaan. Alur utamanya terpusat pada hierarki anggaran dari level global hingga operasional departemen.

### 1.1 Hierarki dan Alur Budgeting
1. **Fiscal Year (Tahun Fiskal)**: Semua data anggaran terikat pada tahun fiskal aktif yang dikelola oleh *Superadmin*.
2. **Global Monthly Budget**: Tim FAT menetapkan pagu anggaran global bulanan untuk perusahaan.
3. **Department Allocation (Rasio & Pagu Departemen)**: Anggaran global dialokasikan ke masing-masing departemen berdasarkan persentase (rasio) atau override manual bulanan.
4. **Category Monitoring**: Di dalam departemen, dana dibagi lagi ke berbagai *Budget Categories* (Kategori Anggaran).
5. **Expenses (Pengeluaran)**: Transaksi harian (expenses) dicatat dan mengurangi sisa anggaran pada kategori terkait.
6. **Odoo Integration**: Terdapat fitur integrasi pengeluaran dari sistem Odoo, baik via sinkronisasi API/Webhook maupun import file Excel.

### 1.2 Alur Role & Otorisasi
- **Superadmin**: Mengatur konfigurasi sistem dasar, tahun fiskal, dan manajemen pengguna (User & Hak Akses).
- **FAT (Finance & Accounting Team)**: Mengelola master departemen, kategori anggaran, rasio budget, alokasi budget global bulanan, forecast, dan laporan keseluruhan. Mengatur sinkronisasi dengan Odoo.
- **Manager**: Memonitor performa dan penyerapan anggaran pada beberapa departemen di bawah naungannya.
- **Departemen**: Memonitor dashboard khusus departemennya sendiri untuk melihat realisasi penggunaan anggaran vs limit yang diberikan.

---

## 2. RULES (ATURAN PENGEMBANGAN)

Untuk menjaga *codebase* yang sehat, terstruktur, dan mudah dipelihara dalam jangka panjang, pengembang (developer) **wajib** mengikuti aturan-aturan berikut:

### 2.1 Arsitektur & Struktur Code (Laravel MVC)
- **Thin Controllers, Fat Models / Services**: Jaga agar controller tetap ringkas. Logika bisnis yang kompleks (seperti kalkulasi sisa budget, sinkronisasi Odoo) harus dipindah ke kelas *Service* atau direpresentasikan sebagai method di *Model*.
- **Middleware & Akses**: Lindungi semua route baru dengan *Role Middleware* yang sudah disediakan (`role:superadmin`, `role:fat`, `role:manager`, `role:departemen`). Jangan lakukan pengecekan role secara *hard-coded* di dalam fungsi controller kecuali untuk otorisasi spesifik *resource* (misalnya menggunakan *Policies*).
- **Form Requests (Validasi)**: Selalu gunakan custom `FormRequest` class untuk memvalidasi input dari user. Hindari melakukan `$request->validate()` secara langsung di dalam controller untuk menjaga kebersihan controller.

### 2.2 Database & Migrasi
- **Skema Bertahap**: Jangan mengubah atau menghapus file migration lama yang sudah di-execute di *production*. Buat file migration baru (`php artisan make:migration`) untuk menambah/mengubah kolom.
- **Foreign Keys & Constraints**: Pastikan integritas relasional dijaga menggunakan Foreign Key dan strategi *cascade/restrict* di migration.
- **Logging**: Gunakan tabel `activity_log` (Spatie Activitylog) untuk mencatat setiap perubahan data krusial (Insert/Update/Delete pada budget atau expenses).

### 2.3 Konvensi UI/UX (Blade & CSS)
- **Reusable Components**: Pecah UI yang sering digunakan (seperti modal konfirmasi, alert, form input) menjadi komponen Blade terpisah.
- **Styling**: Jangan menambahkan styling CSS *inline* yang masif di dalam file `.blade.php`. Manfaatkan utility class yang ada. Pastikan desain tetap konsisten, responsif, dan memberikan *feedback* visual saat proses berjalan (loading state).

---

## 3. RESPONSIBILITY (TANGGUNG JAWAB)

Tanggung jawab dibagi berdasarkan peran dalam sistem dan peran tim pengembang *continuous development*.

### 3.1 Tanggung Jawab Sistem (Berdasarkan Role)
- **Superadmin**: Bertanggung jawab penuh terhadap akses kontrol pengguna dan sinkronisasi *Settings* & *Fiscal Years*. Kesalahan di level ini dapat memutus alur pencatatan anggaran tahunan.
- **FAT**: Bertanggung jawab memastikan akurasi data `Global Budget`, persentase rasio tiap departemen, dan sinkronisasi *Expenses* Odoo agar *dashboard monitoring* departemen menampilkan sisa dana yang riil.
- **Manager / Head**: Mengambil keputusan manajerial dan *budget control* dari hasil *dashboard monitoring*.
- **Staff Departemen**: Bertanggung jawab menginput aktivitas spesifik (jika diotorisasi) dan mengontrol pengeluaran di departemennya agar tidak melebihi *budget limit*.

### 3.2 Tanggung Jawab Developer / Tim IT
- **Code Review**: Setiap penambahan fitur (misal modul *forecast* baru atau algoritma sinkronisasi Odoo) wajib melewati proses ulasan, memeriksa potensi masalah N+1 Query pada *Eloquent*, dan performa *query*.
- **Testing**: Saat mengembangkan atau mengubah fitur *Budgeting*, developer harus memastikan limitasi sistem (mencegah *negative budget* kecuali diperbolehkan, dan limit rasio 100%) tetap berjalan sempurna.
- **Maintainability**: Mendokumentasikan setiap command Artisan baru (seperti `php artisan odoo:sync`), penjadwalan *Cron Job* di `Console/Kernel.php`, dan endpoint API.
- **Data Security**: Mencegah celah *mass assignment* dengan mendefinisikan `$fillable` yang tepat pada *Models*, serta melindungi data finansial dari akses yang tidak sah.

> **Penting**: Selalu perbarui dokumen ini jika terdapat penambahan modul agar Developer selanjutnya memiliki peta pengembangan yang jelas.
