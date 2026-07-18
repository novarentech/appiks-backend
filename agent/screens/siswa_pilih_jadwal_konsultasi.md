# UI Spec: Siswa - Pilih Jadwal Konsultasi (Pemilihan Slot Waktu & Konfirmasi Booking)
**Figma Node IDs:** `3961:36999`, `3961:37527` (Pilih Jadwal Konsultasi), `3961:37334` (ConfirmationModal)

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Time Slot Selection Grid & Modal Booking Confirmation
- **Visible Fields (`Pilih Jadwal Konsultasi`):**
  - `selected_date` (Date / YYYY-MM-DD) -> Tanggal yang dipilih dari screen sebelumnya.
  - `selected_date_formatted` (String) -> Format tanggal lokal (`Sabtu, 16 Mei 2026`).
  - `time_slots` (Array of Objects) -> Daftar slot waktu yang tersedia pada tanggal tersebut (`Pilih Waktu`):
    - `slot_id` (UUID) -> ID unik slot waktu.
    - `time_range` (String) -> Jam mulai - selesai (`09:00 - 10:00 WIB`, `13:00 - 14:00 WIB`, `15:00 - 16:00 WIB`).
    - `is_available` (Boolean) -> Flag ketersediaan slot.

- **Visible Fields (`ConfirmationModal - 3961:37334`):**
  - `modal_title` (String) -> `Konfirmasi Jadwal Konsultasi`
  - `summary_psychologist_name` (String) -> `Dr. Sarah Wijaya, M.Psi., Psikolog`
  - `summary_facility_name` (String) -> `Puskesmas Kecamatan Menteng`
  - `summary_date` (String) -> `Sabtu, 16 Mei 2026`
  - `summary_time` (String) -> `09:00 - 10:00 WIB`
  - `tentative_notice` (String) -> `Jadwal ini bersifat tentative dan menunggu konfirmasi`

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Time Slot Card | Selectable Item / Radio | Required UUID | `time_slot_id` |
| Ajukan Jadwal | Trigger Button | Opens Confirmation Modal | N/A |
| Batal (Modal) | Button | Closes modal | N/A |
| Konfirmasi Booking (Modal) | Submit Button | Required payload mapping | `referral_id`, `time_slot_id`, `date` |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Get Available Time Slots (Saat tanggal dipilih atau diganti)
  - **HTTP Method & Type:** `GET` `/api/v1/siswa/rujukan-psikolog/{id}/slots?date=2026-05-16`
  - **Behavior Logic:** Mengambil slot waktu harian psikolog untuk tanggal tertentu. Slot yang sudah dibooking oleh siswa lain dengan status aktif tidak dimuat atau ditandai `is_available: false`.

- [ ] **Action:** Confirm Booking (Klik Tombol "Konfirmasi Booking" pada Modal)
  - **HTTP Method & Type:** `POST` `/api/v1/siswa/bookings`
  - **Behavior Logic:** Membuat data booking di tabel `booking_schedules`. State diubah menjadi `menunggu_konfirmasi`. Backend secara otomatis memotong kuota slot waktu yang bersangkutan dan memicu timer 24 jam SLA.

---

### [Architect Note]
1. **Race Condition Prevention (Double Booking):** Saat pemanggilan endpoint POST `/api/v1/siswa/bookings`, backend wajib menerapkan lock mekanis (seperti database transaction `SELECT FOR UPDATE` atau Redis Distributed Lock pada resource key `lock:psychologist:{psychologist_id}:date:{date}:slot:{slot_id}`) untuk mencegah dua siswa melakukan booking pada slot waktu yang sama di milidetik yang sama.
2. **Timezone Uniformity:** Seluruh pencatatan waktu konsultasi di database menggunakan format UTC, tetapi disajikan dalam zona waktu lokal (WIB / `Asia/Jakarta`) di API Response untuk kenyamanan siswa.
