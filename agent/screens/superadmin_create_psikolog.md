# UI Spec: Superadmin - Tambah Psikolog Mitra
**Figma Node IDs:** `4284:33533` (Tambah Psikolog), `4284:34692` (Tambah Psikolog - Validation Errors)

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Create Account Form
- **Visible Fields:**
  - `page_title` (String) -> `Tambah Psikolog Mitra`
  - `page_subtitle` (String) -> `Tambahkan akun psikolog mitra untuk menerima rujukan siswa`
  - `default_expertise_options` (Array of Strings) -> `["Depresi", "PTSD", "Trauma", "Anxiety", "Anak & Remaja"]`

- **Validation / Conditional States:**
  - `email_error` (String) -> Muncul jika email tidak valid (`Formal email tidak valid`).
  - `str_number_error` (String) -> Muncul jika nomor STR sudah ada di database (`STR sudah terdaftar`) `[Architect Note]`.

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Email | Text Input | Required, Valid Email Format, Unique | `email` |
| Kata Sandi | Password Text Input | Required, Min 8 chars | `password` |
| Nama Lengkap | Text Input | Required, Max 255 | `fullname` |
| Nomor STR | Text Input | Required, Unique, Format check | `str_number` |
| Keahlian | Tags Checklist / Select | Optional Array | `expertise_tags` |
| Tempat Praktik / Faskes | Text Input | Required | `health_facility` |
| Nomor Telepon | Text Input | Required, Numeric | `phone_number` |
| Harga Dasar (Opsional) | Numeric Input | Optional, Numeric | `base_rate` |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Create Psychologist Mitra (Klik Tombol "Tambah")
  - **HTTP Method & Type:** `POST` `/api/v1/superadmin/psychologists`
  - **Behavior Logic:** Melakukan validasi input. Jika lolos, membuat record baru di tabel `users` (sebagai role psikolog) dan `psychologist_profiles`, lalu mengirimkan email verifikasi aktivasi.

---

### [Architect Note]
1. **STR and Email Uniqueness Validation:** Backend wajib menerapkan validasi keunikan (`unique:psychologist_profiles,str_number` dan `unique:users,email`) untuk menghindari duplikasi registrasi akun psikolog mitra.
