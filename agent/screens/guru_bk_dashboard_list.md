# UI Spec: Guru BK - Dashboard Utama (Daftar Kasus Siswa)
**Figma Node ID:** `4049:31831`

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Data Table with Pagination & Filters
- **Visible Fields:**
  - `cases_list` (Array of Objects) -> Baris tabel kasus siswa (`Tr`):
    - `case_id` (UUID) -> ID kasus.
    - `student_name` (String) -> Nama lengkap siswa (`Alex Allan`, `Anna Visconti`).
    - `student_avatar_initials` (String) -> Inisial nama untuk avatar (`AA`, `AV`).
    - `student_class` (String) -> Kelas siswa (`X IPA 1`).
    - `created_at_formatted` (String) -> Tanggal masuknya laporan/curhat (`08/27/2025 08:00 AM`).
    - `sla_countdown` (String / Format: `HH:MM:SS`) -> Sisa waktu penanganan kasus kritis (`01:58:10`) `[Architect Note]`.
    - `curhat_snippet` (String) -> Potongan teks curhat (`Saya merasa sudah tidak...`).
    - `read_status` (String / Enum: `belum_dibaca`, `dibaca`) -> Status baca pesan (`Belum dibaca`).
    - `safety_zone` (String / Enum: `kritis`, `prioritas`, `aman`) -> Kategori zona keparahan emosional (`Kritis` / `Prioritas` / `Aman`).
    - `handling_status` (String / Enum: `belum_ditinjau`, `sedang_ditangani`, `sudah_ditanggapi`) -> Status peninjauan Guru BK (`Belum ditinjau` / `Sedang Ditangani` / `Sudah Ditanggapi`).
  - `pagination` (Object) -> Data halaman:
    - `current_page` (Integer) -> `1`
    - `total_pages` (Integer) -> `2`
    - `has_previous` (Boolean) -> `false`
    - `has_next` (Boolean) -> `true`

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Search Siswa / Kelas | Text Input | Optional, Min 3 chars | `search` |
| Filter Kategori Zona | Dropdown Select | Optional, Enum: `all`, `kritis`, `prioritas`, `aman` | `zone` |
| Filter Status Penanganan | Dropdown Select | Optional, Enum: `all`, `belum_ditinjau`, `sedang_ditangani`, `sudah_ditanggapi` | `status` |
| Page Navigation | Button Click (`Previous` / `Next`) | Required Integer >= 1 | `page` |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Get Dashboard Cases List (Saat halaman dibuka atau filter berubah)
  - **HTTP Method & Type:** `GET` `/api/v1/guru-bk/cases`
  - **Behavior Logic:** Mengambil daftar kasus siswa terfilter. Kueri dioptimalkan agar kasus berzona `Kritis` diurutkan di paling atas (priority queue), diikuti `Prioritas` dan `Aman`.

- [ ] **Action:** Tinjau Kasus (Klik Tombol "Tinjau")
  - **HTTP Method & Type:** `GET` `/api/v1/guru-bk/cases/{id}`
  - **Behavior Logic:** Mengarahkan Guru BK ke halaman detail penanganan zona kasus terkait (misal detail zona merah jika kasus berzona `Kritis`). Otomatis mengubah `read_status` menjadi `dibaca` pada backend.

---

### [Architect Note]
1. **Critical Zone SLA Timer & Escalation Logic:** Khusus kasus dengan `safety_zone == 'kritis'`, backend memberlakukan batas waktu respon maksimal 2 jam dari timestamp laporan dibuat. Backend wajib mengirimkan parameter sisa waktu dalam detik (`remaining_sla_seconds`) untuk dirender secara real-time oleh countdown frontend. Apabila sisa waktu mencapai `<= 0` (kadaluwarsa) dan `handling_status` masih `belum_ditinjau`, backend harus otomatis memicu workflow eskalasi (misal mengirimkan SMS/WhatsApp darurat atau alert internal ke akun Kepala Sekolah).
2. **Priority Ordering Rule:** Sorting default harus menggunakan kriteria bertingkat: `safety_zone` (Kritis > Prioritas > Aman) kemudian `created_at` (terlama ke terbaru untuk kategori zona yang sama).
