# UI Spec: Siswa - Pusat Aktivitas (Tab Rujukan Psikolog)
**Figma Node IDs:** `3957:35853`, `3968:32622`

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Card List with Tabbed Navigation (`Pusat Aktivitas`)
- **Visible Fields:**
  - `page_title` (String) -> Judul halaman (`Pusat Aktivitas`).
  - `page_subtitle` (String) -> Deskripsi halaman (`Pantau perkembangan layanan bimbingan konselingmu`).
  - `tabs_badge_counts` (Object) -> Jumlah item aktif pada masing-masing tab (`Curhatan (5)`, `Rujukan Psikolog (5)`).
  - `referrals_list` (Array of Objects) -> Daftar kartu rujukan psikolog:
    - `referral_id` (UUID) -> ID unik rujukan.
    - `title` (String) -> Judul rujukan (`Rujukan ke Psikolog Mitra`).
    - `description` (String) -> Keterangan singkat (`Rujukan konsultasi dengan psikolog mitra`).
    - `status` (String / Enum: `butuh_persetujuan`, `setuju_perlu_rujukan`, `setuju_cukup_bk`, `ditolak_siswa`, `terkonfirmasi`) -> Badge status rujukan (`Butuh Persetujuan`) `[Architect Note]`.
    - `psychologist_name` (String) -> Nama psikolog rujukan (`Dr. Sarah Wijaya, M.Psi., Psikolog`).
    - `facility_name` (String) -> Nama institusi/fasilitas kesehatan mitra (`Puskesmas Kecamatan Menteng`).
    - `referring_counselor_name` (String) -> Nama Guru BK yang merujuk (`Guru BK : Sri Wahyuni, S.Pd, M.Pd`).
    - `referral_reason` (String / Encrypted) -> Catatan medis/klinis alasan rujukan (`Alasan Rujukan: Berdasarkan asesmen, siswa memerlukan pendampingan...`) `[Architect Note]`.
    - `created_at_formatted` (String) -> Tanggal pembuatan rujukan (`Dibuat pada : 13 Mei 2026, 14:00 WIB`).
    - `can_review` (Boolean) -> Flag penentu apakah tombol "Review Persetujuan" harus ditampilkan/aktif.

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Tab Selection | Navigation Tab | Enum: `jadwal_konseling`, `curhatan`, `rujukan_psikolog` | `active_tab` (Query Param) |
| Accordion Toggle | Link Button (`Lihat Detail` / `Sembunyikan`) | Client-side UI state toggle / Optional lazy load | N/A |
| Review Persetujuan Action | Action Button | Only enabled if `can_review == true` (`status == 'butuh_persetujuan'`) | `referral_id` |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Switch Tab Pusat Aktivitas (Klik Tab "Jadwal Konseling", "Curhatan", atau "Rujukan Psikolog")
  - **HTTP Method & Type:** `GET` `/api/v1/siswa/pusat-aktivitas?tab=rujukan_psikolog`
  - **Behavior Logic:** Mengambil daftar item sesuai tab yang dipilih beserta jumlah badge count terbaru untuk semua tab.

- [ ] **Action:** Expand Referral Detail (Klik "Lihat Detail" jika tidak dimuat secara default)
  - **HTTP Method & Type:** `GET` `/api/v1/siswa/rujukan-psikolog/{id}/detail`
  - **Behavior Logic:** Mengambil rincian `referral_reason` yang didekripsi khusus untuk sesi pengguna saat itu.

- [ ] **Action:** Proceed to Consent Review (Klik Tombol "Review Persetujuan")
  - **HTTP Method & Type:** `GET` `/api/v1/siswa/rujukan-psikolog/{id}/consent-form`
  - **Behavior Logic:** Memvalidasi kepemilikan data rujukan dan status saat ini (harus `butuh_persetujuan`). Jika valid, mengembalikan data spesifik dokumen persetujuan (informed consent) yang akan di-review oleh siswa atau orang tua.

---

### [Architect Note]
1. **Referral State Machine Logic:** Status rujukan (`status`) memiliki alur transisi state machine yang ketat di backend. Tombol aksi `Review Persetujuan` hanya boleh dirender atau dapat diklik apabila `status === 'butuh_persetujuan'`. Setelah siswa melakukan submit persetujuan/penolakan pada screen berikutnya, status ini harus terkunci dari perubahan lebih lanjut oleh siswa.
2. **Clinical Privacy & Encryption at Rest:** Field `referral_reason` berisi catatan klinis/psikologis hasil asesmen Guru BK. Sesuai standar kepatuhan privasi medis/psikologi, kolom `referral_reason` di database (`referrals` table) disarankan menggunakan enkripsi tingkat kolom (at-rest encryption menggunakan AES-256-GCM / Laravel Encrypter). Data hanya didekripsi pada saat serialisasi API response untuk user bersangkutan (Siswa pemilik data, Guru BK pembuat, atau Psikolog penerima).
