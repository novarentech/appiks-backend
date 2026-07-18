# UI Spec: Siswa - Beranda (Dashboard Siswa)
**Figma Node ID:** `3956:31557`

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Grid / Card List & Interactive Widgets
- **Visible Fields:**
  - `student_name` (String) -> Keterangan visual sapaan di Figma (`Halo, Marsha Bilqiis!`).
  - `current_mood_checkin` (Object / Nullable) -> Status check-in emosi harian terbaru (`Mood Check-in Hari Ini : Gembira`).
  - `safety_status` (String / Enum: `aman`, `prioritas`, `kritis`) -> Indikator status keamanan emosional/psikologis siswa (`Status Kamu : Aman`) `[Architect Note]`.
  - `quote_of_the_day` (Object) -> Kutipan harian motivasi (`Quote of the Day`, teks kutipan & penulis) `[Architect Note]`.
  - `streak_count` (Integer) -> Jumlah hari berturut-turut siswa melakukan mood check-in (`7 hari !`) `[Architect Note]`.
  - `streak_message` (String) -> Pesan motivasi konsistensi (`Konsistensi adalah kunci untuk memahami pola emosionalmu`).
  - `notifications` (Array of Objects) -> Daftar notifikasi terbaru (`Notifikasi`).
  - `upcoming_counseling_schedules` (Array of Objects) -> Jadwal konseling terdekat (`Jadwal Konseling`):
    - `schedule_id` (UUID) -> ID jadwal konseling.
    - `topic_summary` (String) -> Ringkasan topik masalah (`Masalah dengan Teman Sekelas`).
    - `counselor_name` (String) -> Nama Guru BK / Psikolog (`Guru BK : Sri Wahyuni, S.Pd, M.Pd`).
    - `created_at_formatted` (String) -> Waktu pembuatan pengajuan (`Dibuat oleh Anda pada : 08/30/2025 10:00 AM`).
    - `status` (String / Enum: `selesai`, `terkonfirmasi`, `menunggu_konfirmasi`) -> Status jadwal (`Selesai`).
  - `recent_curhat_status` (Array of Objects) -> Status pengajuan/curhat terakhir (`Status Curhatmu`):
    - `curhat_id` (UUID) -> ID curhat/rujukan.
    - `topic_title` (String) -> Topik curhat (`Konflik dengan Orang Tua`).
    - `counselor_name` (String) -> Nama Guru BK / penanggung jawab.
    - `submitted_at_formatted` (String) -> Waktu pengajuan (`Dibuat oleh Anda pada : 08/27/2025 10:00 AM`).
    - `reply_status` (String / Enum: `dibalas`, `menunggu`, `ditindaklanjuti`) -> Status balasan (`Dibalas`).
  - `mood_calendar_highlights` (Array of Objects) -> Data riwayat kalender mood harian (`Kalender Mood`, riwayat tanggal `Rabu 19 Agustus 2025`, jam `10.00 WIB`, status `Terkonfirmasi`, konselor `Bu Nurul`).

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Mood Check-in Option | Interactive Icon / Radio Button | Required, Enum: `gembira`, `sedih`, `cemas`, `marah`, `netral` | `mood_type` |
| Mood Note (Optional) | Text Area | Optional, Max 1000 chars | `mood_note` |
| Refresh Quote Trigger | Button / Icon Click | Optional, Rate-limited | N/A (GET trigger) |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Submit Daily Mood Check-in (Klik Icon Mood pada widget "Bagaimana perasaanmu hari ini?")
  - **HTTP Method & Type:** `POST` `/api/v1/siswa/mood-checkin`
  - **Behavior Logic:** Menyimpan submission mood harian untuk tanggal hari ini. Backend memperbarui status `current_mood_checkin`, mengevaluasi ulang `safety_status` (jika ada indikator krisis), meng-increment `streak_count` harian jika belum check-in hari ini, dan mencatat log di `mood_calendar_highlights`.

- [ ] **Action:** Refresh Quote of the Day (Klik Tombol Refresh / Icon Putar pada Quote Card)
  - **HTTP Method & Type:** `GET` `/api/v1/siswa/quote/random`
  - **Behavior Logic:** Mengambil kutipan motivasi acak baru yang berbeda dari kutipan saat ini. Backend menerapkan rate-limiting (misal maksimal 5 kali refresh per menit) untuk mencegah abuse.

- [ ] **Action:** Expand Task Detail (Klik Accordion / Tombol "Lihat Detail" pada card Jadwal atau Curhat)
  - **HTTP Method & Type:** `GET` `/api/v1/siswa/counseling/{id}/summary` atau `GET` `/api/v1/siswa/curhat/{id}/summary`
  - **Behavior Logic:** Mengambil rincian detail catatan sesi atau balasan curhat jika belum dimuat secara lengkap di payload awal beranda.

---

### [Architect Note]
1. **Streak Calculation Logic:** `streak_count` harus dihitung secara runut di backend berdasarkan `created_at` pada tabel `mood_checkins`. Jika jarak antara check-in hari ini dan check-in terakhir melebihi 1 hari kalender (berdasarkan zona waktu lokal siswa `Asia/Jakarta`), maka `streak_count` di-reset ke angka 1 saat check-in baru dilakukan.
2. **Clinical Triage & Safety Status Evaluation (`safety_status`):** Status `Aman` tidak boleh di-hardcode. Backend harus memiliki service/event listener yang mengevaluasi kata kunci risiko tinggi (self-harm, bullying berat, kekerasan) dari catatan curhat/mood atau skor asesmen secara real-time. Jika terdeteksi risiko, sistem secara otomatis merubah status menjadi `Prioritas` (Yellow Zone) atau `Kritis` (Red Zone) dan memicu alert ke dashboard Guru BK.
3. **Quote Retrieval & Caching:** Untuk performa beranda yang optimal, `quote_of_the_day` sebaiknya di-cache di Redis/memory selama 24 jam per user atau global, kecuali jika user secara eksplisit menekan tombol refresh.
