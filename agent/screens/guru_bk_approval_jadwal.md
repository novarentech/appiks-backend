# UI Spec: Guru BK - Catat Hasil Konseling (Form Resolusi Kasus)
**Figma Node IDs:** `4113:33961`, `4113:34205` (After - Siswa Approve Jadwal), `4113:34449` (ConfirmationModal - Catat Hasil Konseling), `4123:34538` (Fill Perlu Rujukan Profesional), `4123:34835` (Fill Bukan Kondisi Kritis)

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Dialog Form Modal & Updated Detail Case Profile
- **Visible Fields (`Catatan Hasil Konseling Modal`):**
  - `modal_title` (String) -> `Catat Hasil Konseling`
  - `method_label` (String) -> `Metode Konseling*`
  - `method_options` (Array of Strings) -> `["Tatap muka", "Online/Daring"]`
  - `notes_label` (String) -> `Catatan*`
  - `notes_placeholder` (String) -> `Tuliskan hasil observasi, kondisi emosional siswa, respons selama sesi, dan evaluasi Guru BK...`
  - `resolution_label` (String) -> `Status Resolusi Insiden *`
  - `resolution_options` (Array of Objects) -> Opsi status resolusi:
    - `value` (String) -> Key status (`non_critical`, `need_referral`, `non_priority`).
    - `label` (String) -> `Bukan Kondisi Kritis(Red Zone)`, `Perlu Rujukan Profesional/Psikolog`, `Bukan Kondisi Prioritas`.
    - `info_text` (String) -> Keterangan implikasi status (`Status zona merah (kritis) akan dicabut dari monitoring aktif.`).

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Metode Konseling* | Dropdown Select | Required, Enum: `tatap_muka`, `online` | `counseling_method` |
| Catatan* | Text Area | Required, Min 30, Max 2000 chars | `counseling_notes` |
| Status Resolusi Insiden * | Dropdown Select | Required, Enum: `non_critical`, `need_referral`, `non_priority` | `resolution_status` |
| Simpan Hasil Konseling | Submit Button | Required confirmation | N/A |
| Batal | Button | Closes dialog modal | N/A |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Submit Counseling Results & Resolution (Klik "Simpan Hasil Konseling")
  - **HTTP Method & Type:** `POST` `/api/v1/guru-bk/cases/{id}/notes`
  - **Behavior Logic:** Menyimpan catatan sesi konseling dan metode pertemuan di tabel `counseling_sessions`. Backend mengupdate `handling_status` kasus menjadi `sudah_ditanggapi`. `[Architect Note]`.

---

### [Architect Note]
1. **Encrypted Clinical Notes (Medical Confidentiality):** Kolom `counseling_notes` pada tabel `counseling_sessions` menyimpan informasi klinis yang sangat rahasia. Kolom ini harus dienkripsi secara at-rest menggunakan enkripsi AES-256-GCM.
2. **Resolution State Transition Hook:** Backend mengevaluasi `resolution_status` saat penyimpanan:
   - Jika `non_critical` atau `non_priority`: Mengubah status insiden di tabel `cases` menjadi `diselesaikan` (clear).
   - Jika `need_referral`: Mengubah status insiden menjadi `rujukan_aktif`, secara otomatis membuka workflow pembuatan surat rujukan psikolog, dan membuka integrasi data terbatas bagi psikolog mitra yang ditunjuk.
