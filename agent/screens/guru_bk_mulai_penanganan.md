# UI Spec: Guru BK - Mulai Penanganan Kasus
**Figma Node IDs:** `4108:33264` (Mulai Penanganan Kasus?), `4108:33805` (ConfirmationModal), `4082:31987` (After - Mulai Penanganan Kasus)

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Action Confirmation Modal & Detail Case View State
- **Visible Fields (`ConfirmationModal`):**
  - `modal_title` (String) -> `Mulai Penanganan Kasus?`
  - `confirmation_warning` (String) -> `Apakah Anda yakin ingin memulai penanganan untuk kasus ini? Tindakan ini akan menghentikan timer SLA kritis.`

- **Visible Fields (`After - Mulai Penanganan`):**
  - `handling_status` (String / Enum: `sedang_ditangani`) -> Status penanganan kasus terbaru pada kartu identitas siswa (`Sedang Ditangani`).
  - `case_alert_category` (String) -> `Tindakan Segera Diperlukan`.
  - `action_buttons_visible` (Object):
    - `show_ajukan_pertemuan` (Boolean) -> `true` (Tombol "Ajukan Pertemuan Konseling" aktif).
    - `show_rujuk_psikolog` (Boolean) -> `true` (Tombol "Rujuk ke Psikolog" aktif).
  - `curhat_title` (String) -> `Mau Bunuh Diri Euy`
  - `curhat_content` (String / Encrypted) -> `"Saya merasa sudah tidak kuat lagi menghadapi semua ini. Saya ingin b**** d***..."` `[Architect Note]`.
  - `detected_keywords` (Array) -> `["bunuh diri", "tidak ingin hidup", "menyakiti diri"]`.

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Ya, Mulai Tangani | Submit Button (Modal) | Required UUID | `case_id` |
| Batal | Button (Modal) | Closes modal dialog | N/A |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Initiate Case Handling (Klik "Ya, Mulai Tangani" pada modal)
  - **HTTP Method & Type:** `POST` `/api/v1/guru-bk/cases/{id}/start`
  - **Behavior Logic:** Mengubah status kasus dari `belum_ditinjau` menjadi `sedang_ditangani`. Menghentikan penghitungan waktu mundur SLA kritis harian pada database, mencatat `handling_started_at = now()`, serta merekam audit log aktivitas kasus.

- [ ] **Action:** Open Counseling Consultation Schedule (Klik "Ajukan Pertemuan Konseling")
  - **HTTP Method & Type:** `GET` `/api/v1/guru-bk/cases/{id}/schedule-form`
  - **Behavior Logic:** Mengarahkan Guru BK ke screen pembuatan janji pertemuan konseling tatap muka/online dengan siswa.

---

### [Architect Note]
1. **SLA Timer Stop Trigger:** Aksi ini krusial. Begitu status berubah menjadi `sedang_ditangani`, backend harus mengunci/menghentikan counter SLA agar kasus tidak tereskalasi ke Kepala Sekolah. Selisih waktu antara laporan masuk (`trigger_time`) dan penanganan dimulai (`handling_started_at`) dicatat sebagai metrik performa Guru BK.
2. **High-Risk Case Security Check:** Konten curhat bertema self-harm/suicide (`"Saya ingin b**** d***"`) harus ditandai sebagai data terenkripsi sangat rahasia. Akses membaca data ini dicatat khusus di database audit trail (`access_logs`) untuk mencegah kebocoran data sensitif siswa.
