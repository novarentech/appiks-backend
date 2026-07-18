# UI Spec: Guru BK - Notifikasi & Respon Jadwal Siswa (Persetujuan / Penolakan)
**Figma Node IDs:** `4176:33802` (Tampilan Notif Konseling Siswa), `4182:1567` (Jadwal Ditolak Siswa), `4183:1813` (Menunggu Persetujuan Siswa)

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Detail Case Profile with Proposed Schedule Detail & Feedback Status
- **Visible Fields:**
  - `case_id` (UUID) -> ID kasus.
  - `handling_status` (String / Enum: `menunggu_konfirmasi_siswa`, `jadwal_ditolak_siswa`) -> Status peninjauan kasus (`Menunggu Persetujuan Siswa` / `Jadwal Ditolak Siswa`).
  - `proposed_schedule` (Object) -> Detail jadwal yang diajukan oleh Guru BK:
    - `booking_id` (UUID) -> ID booking terkait.
    - `date_formatted` (String) -> `08/28/2025`
    - `time_formatted` (String) -> `10:00 AM`
    - `room_name` (String) -> `Ruang BK 1`
    - `additional_notes` (String) -> `Sesi konseling dijadwalkan sebagai tindak lanjut...`
  - `reschedule_button_visible` (Boolean) -> Menentukan apakah tombol "Ajukan Jadwal Konseling Kembali" harus ditampilkan (`handling_status == 'jadwal_ditolak_siswa'`).

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Ajukan Jadwal Konseling Kembali | Action Button | Only active if status is `jadwal_ditolak_siswa` | N/A |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Get Proposed Booking Details (Saat Guru BK membuka notifikasi ini)
  - **HTTP Method & Type:** `GET` `/api/v1/guru-bk/bookings/{booking_id}`
  - **Behavior Logic:** Mengambil informasi jadwal konsultasi yang diajukan beserta status persetujuan siswa terkini.

---

### [Architect Note]
1. **Re-scheduling & Parent Booking History:** Aksi "Ajukan Jadwal Konseling Kembali" akan membuat usulan booking baru di database. Backend wajib menautkan record baru ini dengan `parent_booking_id` dari booking yang ditolak sebelumnya guna melacak riwayat negosiasi jadwal antara Guru BK dan Siswa.
