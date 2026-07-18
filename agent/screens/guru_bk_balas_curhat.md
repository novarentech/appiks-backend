# UI Spec: Guru BK - Balas Curhat & Lihat Balasan
**Figma Node IDs:** `4219:33309` (Balas Curhat Halaman), `4219:33563` (Balas Curhat Modal Panel), `4219:33825` (Lihat Balasan Halaman), `4219:34087` (Lihat Balasan Modal Panel), `4219:33598` (After Balas)

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Form Modal Dialog & Read-only Detail Card
- **Visible Fields (`Balas Curhat Modal`):**
  - `modal_title` (String) -> `Balas Curhat`
  - `curhat_details` (Object):
    - `topic_title` (String) -> `Lelah dengan tugas sekolah`
    - `content` (String / Encrypted) -> `"Akhir-akhir ini tugas sekolah terasa cukup banyak..."`
    - `student_name` (String) -> `Alex Allan`
    - `submitted_at_formatted` (String) -> `08/27/2025 08:00 AM`
  - `input_label` (String) -> `Tanggapan Anda`

- **Visible Fields (`Lihat Balasan Modal`):**
  - `modal_title` (String) -> `Lihat Balasan`
  - `reply_content` (String / Encrypted) -> Detail balasan yang telah dikirim oleh Guru BK `[Architect Note]`.

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Tanggapan Anda | Text Area | Required, Min 10, Max 2000 chars | `reply_content` |
| Kirim / Simpan | Submit Button | Required | N/A |
| Tutup / x | Button | Closes modal | N/A |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Submit Curhat Reply (Klik Tombol "Kirim")
  - **HTTP Method & Type:** `POST` `/api/v1/guru-bk/cases/{id}/replies`
  - **Behavior Logic:** Menyimpan balasan curhat di tabel `curhat_replies`. Backend mengubah `review_status` kasus menjadi `sudah_ditanggapi`, mengubah `reply_status` curhat menjadi `dibalas`, dan mengirim notifikasi push ke aplikasi Siswa. `[Architect Note]`.

- [ ] **Action:** Get Curhat Reply Detail (Saat klik "Lihat Balasan")
  - **HTTP Method & Type:** `GET` `/api/v1/guru-bk/cases/{id}/replies`
  - **Behavior Logic:** Mengambil data balasan curhat (yang otomatis didekripsi di backend).

---

### [Architect Note]
1. **Encrypted Counseling Response:** Kolom `reply_content` pada tabel `curhat_replies` wajib dienkripsi (AES-256-GCM) at-rest demi mematuhi asas kerahasiaan bimbingan konseling sekolah.
2. **Student Notification Hook:** Setelah balasan berhasil disimpan, backend secara otomatis memicu Firebase Cloud Messaging (FCM) push notification untuk akun Siswa pengirim agar mereka segera membaca tanggapan di Pusat Aktivitas.
