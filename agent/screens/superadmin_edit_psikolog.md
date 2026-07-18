# UI Spec: Superadmin - Edit Informasi Psikolog
**Figma Node ID:** `4284:33384`

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Edit Account Form (Pre-filled)
- **Visible Fields:**
  - `page_title` (String) -> `Edit Psikolog Mitra`
  - `page_subtitle` (String) -> `Perbarui informasi akun psikolog mitra penerima rujukan siswa`
  - `existing_data` (Object) -> Seluruh data profile psikolog yang sudah tersimpan:
    - `email` (String) -> `budi.santoso@klinik.id`
    - `fullname` (String) -> `Dr. Budi Santoso, M.Psi., Psikolog`
    - `str_number` (String) -> `STR-PSI-00201`
    - `expertise_tags` (Array) -> `["Depresi", "PTSD", "Trauma", "Anxiety", "Anak & Remaja"]`
    - `health_facility` (String) -> `Puskesmas Gejayan`
    - `phone_number` (String) -> `0912345678910`

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Email | Text Input | Required, Valid Email, Unique except current id | `email` |
| Nama Lengkap | Text Input | Required, Max 255 | `fullname` |
| Nomor STR | Text Input | Required, Unique except current id | `str_number` |
| Keahlian | Tags checklist | Optional Array | `expertise_tags` |
| Tempat Praktik / Faskes | Text Input | Required | `health_facility` |
| Nomor Telepon | Text Input | Required, Numeric | `phone_number` |
| Harga Dasar (Opsional) | Numeric Input | Optional, Numeric | `base_rate` |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Update Psychologist Mitra (Klik Tombol "Simpan Perubahan")
  - **HTTP Method & Type:** `PUT` `/api/v1/superadmin/psychologists/{id}`
  - **Behavior Logic:** Melakukan validasi input. Meng-update record pada tabel `users` dan `psychologist_profiles`, lalu mencatat perubahan ke log audit.

---

### [Architect Note]
1. **Audit Logs for Sensitive Information Changes:** Setiap perubahan data sensitif psikolog (seperti nomor STR, email, faskes, atau nomor telepon) wajib direkam ke dalam tabel `audit_logs` untuk melacak aktivitas modifikasi data pihak ketiga oleh Superadmin.
