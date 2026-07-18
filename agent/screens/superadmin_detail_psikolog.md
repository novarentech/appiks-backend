# UI Spec: Superadmin - Detail Informasi Psikolog
**Figma Node ID:** `4194:36012`

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Detail Form Card / Read-only Account Profile
- **Visible Fields:**
  - `psychologist_id` (UUID) -> ID Psikolog
  - `login_information` (Object):
    - `email` (String) -> `budi.santoso@klinik.id`
  - `profile_information` (Object):
    - `fullname` (String) -> `Dr. Budi Santoso, M.Psi., Psikolog`
    - `str_number` (String) -> `STR-PSI-00201`
    - `expertise_tags` (Array of Strings) -> Keahlian psikolog (`["Depresi", "PTSD", "Trauma", "Anxiety", "Anak & Remaja"]`).
    - `health_facility` (String) -> Faskes tempat praktik (`Puskesmas Gejayan`).
    - `phone_number` (String) -> Nomor WhatsApp/Hp aktif.

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Edit Data | Trigger Button | Redirects to edit page | N/A |
| Generate Password | Action Button | Generates strong random temporary password | N/A |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Get Psychologist Profile Detail (Saat halaman dibuka)
  - **HTTP Method & Type:** `GET` `/api/v1/superadmin/psychologists/{id}`
  - **Behavior Logic:** Mengambil seluruh profil detail akun psikolog mitra untuk ditampilkan.

- [ ] **Action:** Generate Temporary Password (Klik "Generate Password")
  - **HTTP Method & Type:** `POST` `/api/v1/superadmin/psychologists/{id}/generate-password`
  - **Behavior Logic:** Membuat password acak baru yang kuat, menyimpannya secara ter-hash aman (bcrypt/argon2) di database, dan mengirimkan password mentah secara sekali pakai (one-time) via email/notifikasi kepada psikolog terkait. `[Architect Note]`.

---

### [Architect Note]
1. **One-Time Temporary Password Security:** Password yang di-generate via `Generate Password` wajib memiliki kekuatan minimal 12 karakter dengan campuran huruf besar, kecil, angka, dan karakter spesial. Password dilarang keras disimpan dalam bentuk plaintext di database, dan wajib menggunakan hashing bcrypt/argon2.
