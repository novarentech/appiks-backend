# UI Spec: Guru BK - Rujuk ke Psikolog Mitra
**Figma Node IDs:** `4108:32913` (Rujuk ke Psikolog Detail), `4108:33119` (ConfirmationModal - Form Rujukan), `4125:35493` (After - Ajukan Rujukan Konseling), `4125:35132` (After Klik Perlu Rujukan)

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Form Modal Dialog
- **Visible Fields:**
  - `modal_title` (String) -> `Rujuk ke Psikolog`
  - `psychologists_list` (Array of Objects) -> Daftar psikolog mitra yang aktif untuk dropdown `Psikolog Tujuan*`:
    - `psychologist_id` (UUID) -> ID psikolog.
    - `name_with_title` (String) -> Nama lengkap + gelar (`Dr. Sarah Wijaya, M.Psi., Psikolog`).
    - `facility_name` (String) -> Tempat praktik (`Puskesmas Kecamatan Menteng`).

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Psikolog Tujuan* | Dropdown Select | Required, Valid UUID | `psychologist_id` |
| Alasan Rujukan* | Text Area | Required, Min 30, Max 2000 chars | `referral_reason` |
| Catatan Tambahan (Opsional) | Text Area | Optional, Max 1000 chars | `additional_notes` |
| Kirim Rujukan | Submit Button | Required confirmation to trigger | N/A |
| Batal | Button | Closes modal | N/A |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Submit Psychologist Referral (Klik Tombol "Kirim Rujukan")
  - **HTTP Method & Type:** `POST` `/api/v1/guru-bk/cases/{id}/referral`
  - **Behavior Logic:** Membuat record rujukan baru di tabel `referrals` dengan status awal `butuh_persetujuan`. Backend merekam detail alasan rujukan dan catatan tambahan (yang dienkripsi). Status kasus/insiden siswa diubah menjadi `menunggu_persetujuan_siswa`. `[Architect Note]`.

---

### [Architect Note]
1. **Encrypted Referral Details:** Field `referral_reason` dan `additional_notes` wajib dienkripsi di database (`referrals` table) menggunakan AES-256-GCM.
2. **Referral State Transition Hook:** Pengajuan rujukan oleh Guru BK akan otomatis memicu status rujukan siswa menjadi `butuh_persetujuan`, membuat record baru pada `data_access_consents` (menghubungkan rujukan dengan daftar data yang disetujui dibagikan), dan memicu pengiriman notifikasi push ke aplikasi Siswa.
