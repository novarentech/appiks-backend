# UI Spec: Siswa - Review Persetujuan Akses Data & Persetujuan Berhasil
**Figma Node IDs:** `3960:36283` (Review Persetujuan), `3960:36861` (Persetujuan Berhasil)

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Informed Consent Form Card & Success Feedback State
- **Visible Fields (`Review Persetujuan`):**
  - `header_title` (String) -> Judul dokumen persetujuan (`Persetujuan Akses Data`).
  - `header_description` (String) -> Penjelasan singkat (`Untuk melanjutkan rujukan konseling, silakan pilih data yang ingin dibagikan...`).
  - `target_psychologist` (Object) -> Detail psikolog mitra penerima akses:
    - `psychologist_name` (String) -> Nama psikolog (`Dr. Sarah Wijaya, M.Psi., Psikolog`).
    - `facility_name` (String) -> Nama fasilitas kesehatan (`Puskesmas Kecamatan Menteng`).
  - `data_sharing_options` (Array of Objects) -> Daftar opsi data yang dapat dipilih siswa (`Data yang Akan Dibagikan`):
    - `key` (String / Enum: `share_mood_30_days`, `share_red_zone_curhat`, `share_bk_assessment`) -> Key identifikasi opsi.
    - `label` (String) -> Judul opsi (`Riwayat mood 30 hari terakhir`).
    - `description` (String) -> Keterangan opsi (`Data aktivitas dan pola mood Anda dalam 30 hari terakhir`).
    - `is_default_checked` (Boolean) -> Status centang default pada UI.
  - `privacy_disclaimer` (String) -> Pernyataan jaminan kerahasiaan (`Data yang Anda bagikan akan digunakan untuk menyiapkan...`).

- **Visible Fields (`Persetujuan Berhasil`):**
  - `success_title` (String) -> Judul konfirmasi (`Persetujuan Berhasil!`).
  - `success_message` (String) -> Pesan sukses (`Data Anda telah berhasil dibagikan kepada psikolog...`).
  - `next_step_guide` (Object) -> Panduan langkah berikutnya (`Langkah Selanjutnya`, `Anda akan diarahkan ke halaman pemilihan jadwal konsultasi...`).

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Riwayat mood 30 hari terakhir | Checkbox | Optional Boolean | `share_mood_30_days` |
| Kutipan teks curhat yang memicu Red Zone | Checkbox | Optional Boolean | `share_red_zone_curhat` |
| Catatan asesmen Guru BK | Checkbox | Optional Boolean | `share_bk_assessment` |
| Setuju dan Lanjutkan | Submit Button | Required (Minimal 1 opsi dicentang atau sesuai kebijakan sekolah) | `action = 'approve'` |
| Tidak Setuju | Decline Button | Required | `action = 'decline'` |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Submit Consent Approval (Klik Tombol "Setuju dan Lanjutkan")
  - **HTTP Method & Type:** `POST` `/api/v1/siswa/rujukan-psikolog/{id}/consent`
  - **Behavior Logic:** Menyimpan log persetujuan granular (audit trail) pada tabel `data_access_consents` mencakup `referral_id`, array opsi data yang dicentang, timestamp, dan device info siswa. Mengubah status rujukan menjadi `setuju_perlu_rujukan` serta membuka izin (scope API) untuk Psikolog membaca data spesifik sesuai centang siswa. Setelah sukses, API mengarahkan ke state `Persetujuan Berhasil!`.

- [ ] **Action:** Decline Consent (Klik Tombol "Tidak Setuju")
  - **HTTP Method & Type:** `POST` `/api/v1/siswa/rujukan-psikolog/{id}/consent-decline`
  - **Behavior Logic:** Mengubah status rujukan menjadi `ditolak_siswa`. Backend membatalkan proses pembukaan akses data ke psikolog mitra dan mengirimkan notifikasi kepada Guru BK pengaju bahwa siswa menolak rujukan.

- [ ] **Action:** Proceed to Schedule Selection (Klik Tombol "Lanjut ke Pemilihan Jadwal")
  - **HTTP Method & Type:** `GET` `/api/v1/siswa/rujukan-psikolog/{id}/available-schedules`
  - **Behavior Logic:** Memicu navigasi ke screen pemilihan waktu konsultasi dengan psikolog mitra.

---

### [Architect Note]
1. **Granular Informed Consent & RBAC/ABAC Scope:** Pilihan centang (`share_mood_30_days`, `share_red_zone_curhat`, `share_bk_assessment`) harus menciptakan *Access Control List (ACL) / Dynamic Scopes* yang dipaksakan pada middleware API Psikolog. Jika Psikolog memanggil endpoint GET riwayat mood siswa, middleware wajib mengecek apakah `share_mood_30_days == true` pada tabel `data_access_consents` untuk rujukan terkait.
2. **Automatic PII & Clinical Masking:** Khusus opsi `share_red_zone_curhat` dengan keterangan "disamarkan", backend wajib menerapkan filter/masking pada saat menyajikan teks curhat ke Psikolog. Nama pihak ketiga (teman sekelas, guru, orang tua) yang tercantum di dalam teks curhat harus secara otomatis disamarkan (misal: `[Disamarkan demi privasi]`) atau disajikan dalam bentuk ringkasan klinis yang tidak mengekspos identitas langsung sesuai etika psikologi.
3. **Immutable Consent Audit Trail:** Log persetujuan atau penolakan data ini bersifat permanen hukum medis (immutable audit log). Record di tabel persetujuan tidak boleh diubah atau dihapus via API standard, melainkan hanya bisa direkam status barunya jika terjadi pencabutan izin (revocation) di masa depan.
