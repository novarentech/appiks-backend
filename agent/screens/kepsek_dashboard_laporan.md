# UI Spec: Kepsek - Dashboard Monitoring & Laporan SLA
**Figma Node ID:** `4491:28059`

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Dashboard Card List with SLA Alerts
- **Visible Fields:**
  - `escalated_cases` (Array of Objects) -> Daftar kasus eskalasi yang mengalami keterlambatan respon:
    - `case_code` (String) -> Kode kasus tersamar (`Kasus #RZ-00123`, `Kasus #RZ-00122`) `[Architect Note]`.
    - `trigger_date` (String) -> `05/28/2025`
    - `trigger_time` (String) -> `10:00`
    - `counselor_name` (String) -> `Guru BK : Sri Wahyuni, S.Pd, M.Pd`
    - `handling_status` (String / Enum: `sedang_ditangani`, `dirujuk_psikolog`, `diselesaikan`) -> Status penanganan kasus (`Sedang Ditangani` / `Dirujuk ke Psikolog` / `Diselesaikan`).
    - `sla_breach_alert` (Boolean) -> Penanda status keterlambatan respon (`true` untuk `Melebihi SLA RESPON`).
    - `bk_initial_notes` (String) -> `Catatan Awal Guru BK : Berdasarkan asesmen, siswa memerlukan pendampingan`
    - `created_at_formatted` (String) -> `Dibuat pada : 23 Mei 2026, 14:00 WIB`

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Lihat Detail | Button | Read-only access to case timeline | N/A |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Get Escalated Cases List (Saat Kepala Sekolah membuka dashboard)
  - **HTTP Method & Type:** `GET` `/api/v1/headteacher/cases/escalated`
  - **Behavior Logic:** Mengambil daftar kasus kritis yang mengalami keterlambatan penanganan (`sla_breach_alert == true`) untuk dimonitor Kepala Sekolah. `[Architect Note]`.

---

### [Architect Note]
1. **Student Anonymity (Case Code masking):** Demi kepatuhan perlindungan identitas anak, data nama lengkap siswa disamarkan menjadi `case_code` (misal: `#RZ-00123`) di dashboard Kepala Sekolah. Kepala Sekolah hanya memonitor performa respon Guru BK dan eskalasi penanganan, bukan catatan klinis identitas langsung siswa.
2. **Read-only Principal Authorization:** Akun Kepala Sekolah dibatasi hanya memiliki hak akses read-only (GET) dan dilarang memanipulasi status, catatan klinis, atau jadwal konsultasi.
