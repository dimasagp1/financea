# Integrasi Pengecekan Anggaran (Finance API)

Dokumen ini berisi rancangan dan persyaratan teknis untuk mengintegrasikan aplikasi Finance (`financehbt`) dengan aplikasi Procurement (`procurementbaru`) guna melakukan pengecekan ketersediaan anggaran sebelum *Purchase Request* (PR) diajukan.

## Aturan Bisnis yang Disepakati
1. **Hard Block:** Jika anggaran tidak mencukupi, API akan merespon `is_allowed: false`. Aplikasi Procurement **wajib memblokir** tombol *Submit* dan menampilkan *Alert* error.
2. **Sinkronisasi ID Departemen:** Karena ID Departemen saat ini berbeda antara Finance dan Procurement, disepakati bahwa Procurement yang akan menyesuaikan/memetakan (mapping) ID tersebut di pengaturannya. Sehingga, saat Procurement menembak API Finance, nilai `department_id` yang dikirim adalah **ID milik Finance**.
3. **Fase Pengerjaan:** API dibuat di Finance terlebih dahulu. Setelah selesai, dokumentasi *Requirement Payload*-nya akan diberikan untuk diimplementasikan di aplikasi Procurement.

---

## 1. Pembuatan API (Aplikasi Finance `financehbt`)

**Endpoint yang akan dibuat:**
- URL: `POST /api/budget/check`
- Keamanan: Dilindungi dengan middleware API Key (`X-API-KEY`).
- Logika: Menerima request -> Cek `MonthlyBudget` -> Hitung `Expense` -> Bandingkan dengan `requested_amount` -> Return JSON.

---

## 2. Dokumen Persyaratan (Requirement) untuk Aplikasi Procurement

Nantinya, setelah API di atas selesai diimplementasikan di aplikasi Finance, tim/developer Procurement perlu menambahkan kode pemanggilan API berikut di **`PurchaseRequestController.php`** (aplikasi Procurement) tepat pada fungsi `submit`:

**URL Target:** `http://[url-finance]/api/budget/check`
**Method:** `POST`
**Headers:**
- `Accept: application/json`
- `X-API-KEY: [API_KEY_YANG_AKAN_DISEPAKATI]`

**Body / Payload (JSON):**
```json
{
    "department_id": 3, 
    "month": "2026-05", 
    "requested_amount": 15000000 
}
```
*(Catatan: `department_id` 3 ini adalah ID departemen yang sudah di-mapping agar sesuai dengan database Finance).*

**Contoh Response Sukses (Anggaran Cukup -> Boleh Submit):**
```json
{
    "status": "success",
    "is_allowed": true,
    "budget_limit": 50000000,
    "current_usage": 10000000,
    "requested_amount": 15000000,
    "remaining_budget": 25000000,
    "message": "Anggaran mencukupi."
}
```

**Contoh Response Gagal (Over Budget -> Hard Block / Ditolak):**
```json
{
    "status": "success",
    "is_allowed": false,
    "budget_limit": 50000000,
    "current_usage": 45000000,
    "requested_amount": 15000000,
    "remaining_budget": -10000000,
    "message": "Batasan anggaran terlampaui. Total PR melebihi sisa anggaran bulan ini."
}
```
