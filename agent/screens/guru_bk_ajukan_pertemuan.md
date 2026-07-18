# UI Spec: Guru BK - Ajukan Pertemuan Konseling
**Figma Node IDs:** `4107:32362` (Ajukan Pertemuan Konseling Detail), `4107:32606`, `4108:32852` (ConfirmationModal - Form Ajukan Pertemuan)

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Form Modal Dialog
- **Visible Fields:**
  - `modal_title` (String) -> `Ajukan Pertemuan Konseling`
  - `tentative_notice` (String) -> `Jadwal ini bersifat tentative dan menunggu konfirmasi`
  - `room_options` (Array of Strings) -> Daftar pilihan ruangan konseling yang tersedia (`["Ruang BK 1", "Ruang BK 2", "Konseling Online"]`).

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Tanggal * | Date Input | Required, Date >= Today (Format: YYYY-MM-DD) | `booking_date` |
| Waktu * | Time Input | Required, Format: HH:MM | `booking_time` |
| Ruangan * | Dropdown Select | Required, Enum / Dynamic match | `room_id` / `room_name` |
| Catatan Tambahan (Opsional) | Text Area | Optional, Max 1000 characters | `additional_notes` |
| Konfirmasi | Submit Button | Triggers schedule proposal to student | N/A |
| Batal | Button | Closes modal | N/A |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Submit Counseling Proposal (Klik Tombol "Konfirmasi")
  - **HTTP Method & Type:** `POST` `/api/v1/guru-bk/cases/{id}/schedule-proposal`
  - **Behavior Logic:** Membuat pengajuan jadwal baru di tabel `booking_schedules` dengan status `menunggu_konfirmasi_siswa`. Backend melakukan verifikasi ketersediaan ruang dan jam kerja Guru BK. Mengirimkan push notification ke dashboard akun Siswa bersangkutan. `[Architect Note]`.

---

### [Architect Note]
1. **Schedule Collision & Double Booking Prevention:** Endpoint POST proposal wajib mengecek ketersediaan `room_id` dan Guru BK (`counselor_id`) pada slot tanggal/waktu yang dikirimkan. Jika bentrok dengan jadwal konseling terkonfirmasi lainnya, API harus mengembalikan error validasi `422 Unprocessable Entity` beserta detail schedule collision.
2. **Student Consent Workflow Initialization:** Pengajuan oleh Guru BK tidak langsung mengunci slot sebagai `terkonfirmasi`. Status awal adalah `menunggu_konfirmasi_siswa` dan secara otomatis memicu notifikasi inbox ke siswa untuk menyetujui, menjadwalkan ulang, atau menolak usulan pertemuan ini.
