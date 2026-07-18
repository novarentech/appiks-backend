# UI Spec: Guru BK - Detail Kasus Kritis (Zona Merah / Alert Kritis)
**Figma Node IDs:** `4049:32424`, `4106:641`

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Case Detail Profile with Alert Banner & Action Panel
- **Visible Fields:**
  - `case_id` (UUID) -> ID unik kasus.
  - `safety_zone` (String / Enum: `kritis`) -> Kategori zona keparahan (`Kritis`).
  - `sla_remaining_time` (String) -> Penanda waktu mundur penanganan (`47:12:09`) `[Architect Note]`.
  - `alert_message` (String) -> Keterangan urgensi (`Kasus ini memerlukan penanganan dan tindak lanjut segera.`).
  - `alert_status` (String) -> Label status alert (`Perlu Tindak Lanjut`).
  - `student_identity` (Object) -> Informasi profil lengkap siswa (`Identitas Siswa`):
    - `fullname` (String) -> Nama lengkap siswa (`Alex Allan`).
    - `nis` (String) -> Nomor Induk Siswa (`TRD-64851`).
    - `class_name` (String) -> Kelas (`X IPA 1`).
    - `trigger_time` (String) -> Waktu kasus dipicu oleh sistem (`08/27/2025 08:00 AM`).
    - `review_status` (String / Enum: `belum_ditinjau`, `sedang_ditangani`, `selesai`) -> Status peninjauan kasus saat ini (`Belum Ditinjau`).
  - `curhat_transcript` (Object) -> Transkrip teks curhat (`Transkrip Curhatan`):
    - `topic_title` (String) -> Topik/kategori utama curhat (`Stres Memikirkan Nilai Sekolah`).
    - `content` (String / Encrypted) -> Detail tulisan curhat siswa (`Saya akhir-akhir ini capek banget sama tugas sekolah...`) `[Architect Note]`.
  - `detected_keywords` (Array of Strings) -> Kata kunci sensitif yang memicu deteksi sistem AI (`Kata Kunci Terdeteksi`):
    - Contoh: `["stres", "malas"]` `[Architect Note]`.

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Mulai Tangani | Action Button | Only active if `review_status == 'belum_ditinjau'` | N/A |
| Bukan Kondisi Prioritas | Action Button | Triggers justification form modal | N/A |
| Rujuk ke Psikolog | Action Button | Only active if case is under active handling | N/A |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Get Case Details (Saat Guru BK membuka halaman ini)
  - **HTTP Method & Type:** `GET` `/api/v1/guru-bk/cases/{id}`
  - **Behavior Logic:** Mengambil rincian data kasus kritis, catatan identitas siswa, transkrip curhat (yang otomatis didekripsi di backend), dan sisa SLA timer. Mengubah `handling_status` kasus menjadi `sedang_ditinjau` (jika status sebelumnya `belum_ditinjau`).

- [ ] **Action:** Start Case Handling (Klik Tombol "Mulai Tangani")
  - **HTTP Method & Type:** `POST` `/api/v1/guru-bk/cases/{id}/start-handling`
  - **Behavior Logic:** Mengubah status kasus menjadi `sedang_ditangani` (Yellow/Prioritas status) dan menonaktifkan/menghentikan SLA timer kritis. Mencatat waktu mulai penanganan di database (`handling_started_at`).

---

### [Architect Note]
1. **NLP Keyword Extraction Logic:** Array `detected_keywords` diisi secara otomatis oleh engine NLP/Regex Parser di backend saat siswa mengirimkan curhat. Engine membandingkan teks curhat dengan kamus istilah risiko (seperti kecemasan, depresi, bullying). Setiap kecocokan disimpan ke tabel relasi `case_keywords` untuk divisualisasikan pada screen Guru BK ini.
2. **Clinical Privacy Constraints:** Karena teks curhat (`content`) bersifat klinis dan rahasia, backend wajib memvalidasi bahwa Guru BK yang memanggil API adalah Guru BK yang mengampu kelas siswa bersangkutan (atau memiliki hak akses setingkat koordinator BK/kepala sekolah).
3. **Downgrade Log Requirement:** Aksi mendowngrade tingkat urgensi kasus melalui tombol "Bukan Kondisi Prioritas" wajib mengirimkan payload justifikasi Guru BK dan mencatatnya ke tabel log audit (`case_downgrade_logs`) agar dapat direview secara eksternal oleh administrator atau Kepala Sekolah.
