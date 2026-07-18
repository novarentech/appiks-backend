# UI Spec: Psikolog & Superadmin - Halaman Login
**Figma Node IDs:** `4403:27747` (Login Psikolog), `4286:34871` (Login Superadmin / Portal Psikolog Mitra)

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Auth Form Card
- **Visible Fields:**
  - `page_title` (String) -> `Masuk ke Akun` / `Login Psikolog Mitra`
  - `page_subtitle` (String) -> `Isi data dibawah ini untuk masuk ke akun Anda`
  - `access_notice` (String) -> `Portal ini hanya dapat diakses oleh Psikolog Mitra terdaftar. Untuk mendapatkan akses, hubungi administrator sekolah.`

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Email | Text Input | Required, Valid Email Format | `email` |
| Password | Password Input | Required, Min 8 chars | `password` |
| Remember Me | Checkbox | Optional Boolean | `remember_me` |
| Forgot password? | Link | Redirects to Reset Password page | N/A |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** User Login Submission (Klik Tombol "Masuk" / "Login")
  - **HTTP Method & Type:** `POST` `/api/v1/auth/login`
  - **Behavior Logic:** Otentikasi kredensial pengguna. Jika valid, backend mengeluarkan JWT (JSON Web Token) / Laravel Sanctum Token dan mengembalikan response sukses beserta detail `role` (`superadmin`, `psikolog`) untuk mengarahkan pengguna ke dashboard yang sesuai. `[Architect Note]`.

---

### [Architect Note]
1. **Brute-Force Protection & Rate Limiting:** Endpoint POST `/api/v1/auth/login` wajib menerapkan rate limiting (misalnya, maksimal 5 kali kegagalan login dalam 1 menit per kombinasi email/IP address). Jika limit terlewati, akun akan dikunci sementara (lockout) selama 15 menit.
2. **Secure Password Hashing:** Seluruh validasi pencocokan password menggunakan secure hash comparison (bcrypt atau argon2id).
