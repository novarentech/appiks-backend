# UI Spec: Superadmin - List Manajemen Psikolog
**Figma Node ID:** `4190:34052`

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Data Table with Pagination
- **Visible Fields:**
  - `psychologists` (Array of Objects) -> Baris data psikolog:
    - `id` (UUID) -> ID Psikolog
    - `fullname` (String) -> Nama lengkap + gelar (`Dr. Maya Putri, M.Psi., Psikolog`).
    - `email` (String) -> Alamat email (`maya.putri@gondokusuman.id`).
    - `health_facility` (String) -> Faskes tempat praktik (`Puskesmas Gondokusuman II`).
    - `str_number` (String) -> Nomor STR Psikolog (`STR-PSI-00192`).
    - `target_client` (String) -> Sasaran klien (`Siswa`).
    - `is_active` (Boolean) -> Status aktif akun.
  - `pagination` (Object) -> Data halaman (`Page 1 of 2`, `Previous`, `Next`).

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Search | Text Input | Optional | `search` |
| View Detail (`eye`) | Icon Button | Redirects to detail page | N/A |
| Suspend Account (`user-x`) | Icon Button | Triggers status toggle | N/A |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Get Psychologists List (Saat halaman dibuka)
  - **HTTP Method & Type:** `GET` `/api/v1/superadmin/psychologists`
  - **Behavior Logic:** Mengambil daftar psikolog mitra terdaftar untuk dikelola.

- [ ] **Action:** Toggle Account Status (Klik Icon "user-x")
  - **HTTP Method & Type:** `PATCH` `/api/v1/superadmin/psychologists/{id}/status`
  - **Behavior Logic:** Mengubah status `is_active` dari `true` ke `false` (atau sebaliknya). Jika dinonaktifkan, akun psikolog ditutup dari sistem dan seluruh reservasi aktif dibatalkan. `[Architect Note]`.

---

### [Architect Note]
1. **Psychologist Suspension & Schedule Cancellation Hook:** Ketika superadmin menonaktifkan akun psikolog (`is_active = false`), backend harus membatalkan semua jadwal konsultasi aktif (`booking_schedules`) yang dikaitkan dengan psikolog tersebut dan mengirimkan notifikasi ke Guru BK serta Siswa terkait untuk penjadwalan ulang.
