# UI Spec: Siswa - Jadwal Berhasil Dibuat! (Confirmation & SLA Feedback)
**Figma Node ID:** `3964:31874`

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Success Feedback State & SLA Timeline Information
- **Visible Fields:**
  - `success_title` (String) -> Judul konfirmasi (`Jadwal Berhasil Dibuat!`).
  - `success_subtitle` (String) -> Keterangan singkat (`Data Anda telah berhasil dibagikan kepada psikolog dan jadwal telah diajukan`).
  - `sla_status_card` (Object) -> Kartu informasi batas waktu konfirmasi:
    - `title` (String) -> Judul status (`Menunggu Konfirmasi Psikolog`).
    - `sla_description` (String) -> Keterangan durasi (`Psikolog memiliki waktu 24 jam untuk mengonfirmasi jadwal pengajuan ini`).
    - `deadline_timestamp` (ISO-8601 Datetime) -> Waktu pasti kadaluwarsa slot pengajuan `[Architect Note]`.
  - `next_steps_info` (Object) -> Panduan tahapan selanjutnya (`Apa yang Terjadi Selanjutnya?`):
    - `step_1` (String) -> `Psikolog akan meninjau permintaan Anda`
    - `step_2` (String) -> `Sistem AI sedang menyiapkan ringkasan data Anda untuk membantu konseling` `[Architect Note]`
    - `step_3` (String) -> `Anda akan menerima notifikasi setelah jadwal dikonfirmasi`
    - `step_4` (String) -> `Jika tidak dikonfirmasi dalam 24 jam, slot akan dirilis kembali` `[Architect Note]`

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Kembali ke Beranda | Navigation Button | Client-side route jump (`/siswa/beranda`) | N/A |
| Kelola Persetujuan Data | Navigation Button | Client-side route jump (`/siswa/persetujuan-data`) | N/A |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Check Booking Status Summary (Dimuat saat halaman konfirmasi ini dibuka)
  - **HTTP Method & Type:** `GET` `/api/v1/siswa/booking-schedules/{schedule_id}/status`
  - **Behavior Logic:** Mengembalikan rincian jadwal yang baru dibuat beserta perhitungan sisa waktu SLA (`deadline_timestamp`).

---

### [Architect Note]
1. **SLA Countdown & Auto-Expiration Logic:** Saat siswa berhasil memilih jadwal, backend mencatat `status = 'menunggu_konfirmasi'` pada tabel `booking_schedules` dan menetapkan `deadline_timestamp = now() + 24 hours`. Backend harus memiliki Cron Job / Background Worker harian/jam-an yang mengecek jadwal dengan `deadline_timestamp <= now() && status == 'menunggu_konfirmasi'`. Jika terdeteksi, sistem otomatis mengubah status menjadi `expired/batal` dan merilis kembali slot waktu psikolog agar bisa di-booking orang lain.
2. **AI Summary Generation Background Job:** Sesuai poin "Sistem AI sedang menyiapkan ringkasan data Anda", segera setelah jadwal dibuat dan consent diberikan, event `BookingScheduleCreated` harus memicu *queued job* di backend. Worker AI akan membaca data mood 30 hari & curhat siswa (yang sudah disetujui untuk dibagikan), melakukan kompresi/ringkasan klinis (Clinical AI Summary), dan menyimpan hasilnya di tabel `clinical_summaries` khusus untuk dibaca Psikolog sebelum sesi dimulai.
