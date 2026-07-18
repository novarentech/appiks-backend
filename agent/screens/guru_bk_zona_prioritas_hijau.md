# UI Spec: Guru BK - Detail Kasus Ringan (Zona Hijau / Curhatan Aman)
**Figma Node IDs:** `4168:2632` (Zona Prioritas / Kuning), `4167:1117` (After Klik Bukan Prioritas), `4219:1630` (Zona Hijau - Aman)

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Case Detail Profile with Safety Category & Action Buttons
- **Visible Fields:**
  - `case_id` (UUID) -> ID kasus.
  - `safety_zone` (String / Enum: `aman`) -> Kategori zona keparahan (`Aman`).
  - `alert_message` (String) -> Penanda status (`Curhatan Aman`).
  - `student_identity` (Object) -> Detail profil siswa:
    - `fullname` (String) -> `Alex Allan`
    - `nis` (String) -> `TRD-64851`
    - `class_name` (String) -> `X IPA 1`
    - `trigger_time` (String) -> `08/27/2025 08:00 AM`
    - `review_status` (String / Enum: `belum_ditanggapi`, `sudah_ditanggapi`) -> Status tanggapan Guru BK (`Belum Ditanggapi`).
  - `curhat_transcript` (Object) -> Transkrip curhat siswa:
    - `topic_title` (String) -> `Lelah dengan tugas sekolah`
    - `content` (String / Encrypted) -> `"Akhir-akhir ini tugas sekolah terasa cukup banyak dan bikin capek..."` `[Architect Note]`.

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Balas Curhat | Action Button | Only active if `review_status == 'belum_ditanggapi'` | N/A |
| Bukan Kondisi Prioritas | Action Button | Only active if case is yellow zone | N/A |
| Rujuk ke Psikolog | Action Button | Optional, active for escalation | N/A |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Load Green Case Details (Saat Guru BK membuka halaman ini)
  - **HTTP Method & Type:** `GET` `/api/v1/guru-bk/cases/{id}`
  - **Behavior Logic:** Mengambil rincian curhat siswa kategori aman. Akses dibatasi pada Guru BK kelas pengampu.

---

### [Architect Note]
1. **Low-Risk Case SLA Policies:** Berbeda dengan zona kritis yang memberlakukan SLA 2 jam, kasus kategori `Aman` (Zona Hijau) memiliki target SLA peninjauan yang lebih longgar (misal 24 jam) dan tidak memicu notifikasi eskalasi darurat otomatis ke Kepala Sekolah.
2. **Confidentiality:** Data curhat tetap dienkripsi di database meskipun sentimen/risikonya berkategori `Aman`.
