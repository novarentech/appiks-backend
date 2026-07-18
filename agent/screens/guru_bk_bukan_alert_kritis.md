# UI Spec: Guru BK - Tandai Bukan Alert Kritis / Bukan Kondisi Prioritas
**Figma Node IDs:** `4108:33510`, `4168:2029` (Bukan Alert Kritis Detail), `4108:33756`, `4168:2238` (ConfirmationModal), `4170:2985` (After - Tandai Bukan Kritis)

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Dialog Form Modal & Detail Resolved Case Card
- **Visible Fields (`ConfirmationModal`):**
  - `modal_title` (String) -> Judul dialog (`Bukan Kondisi Kritis` / `Bukan Kondisi Prioritas`).
  - `input_label` (String) -> Label input alasan (`Alasan *`).
  - `input_placeholder` (String) -> `Jelaskan alasan pencabutan status ...`

- **Visible Fields (`After - Tandai Bukan Kritis`):**
  - `handling_status` (String / Enum: `diselesaikan`) -> Status peninjauan kasus terbaru (`Diselesaikan`).
  - `resolution_details` (Object):
    - `resolution_status` (String) -> Status resolusi akhir (`Bukan Kondisi Kritis`).
    - `resolution_reason` (String) -> Alasan pencabutan status (`Siswa sangat kooperatif saat konseling. Tidak ditemukan tanda-tanda distress psikologis...`).

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Alasan * | Text Area / Input | Required, Min 20, Max 1000 characters | `justification_reason` |
| Tandai Bukan Kondisi Kritis | Submit Button | Submits reason and resolves case alert | N/A |
| Batal | Button | Closes modal | N/A |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Resolve Case Alert (Klik "Tandai Bukan Kondisi Kritis" / "Tandai Bukan Kondisi Prioritas")
  - **HTTP Method & Type:** `POST` `/api/v1/guru-bk/cases/{id}/resolve-non-critical`
  - **Behavior Logic:** Mengubah status kasus menjadi `diselesaikan` dan menghentikan seluruh alert. Menyimpan data justifikasi Guru BK di tabel `case_resolutions` untuk keperluan audit Kepala Sekolah. Kategori zona emosional siswa diturunkan kembali ke database log.

---

### [Architect Note]
1. **Compulsory Audit Justification Validation:** Backend wajib memvalidasi input `justification_reason` agar tidak berupa teks kosong atau terlalu pendek (min 20 karakter) guna memastikan Guru BK memberikan alasan profesional yang dapat dipertanggungjawabkan dalam menurunkan urgensi kasus.
2. **Escalation Notification Clean-up:** Jika kasus ini sebelumnya memicu alert berkala kepada pihak sekolah, proses resolusi ini harus langsung menghentikan (clean-up) antrean job notifikasi eskalasi yang terjadwal.
