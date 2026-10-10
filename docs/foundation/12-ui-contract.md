# UI Contract — Figma ↔ Backend

<!-- verified: branch=dev commit=266f860 date=2026-10-09 scope=app/Enums,Figma file QIhkFbm9QlPweWuT7qIUyl -->
> **Terverifikasi terhadap:** `dev` @ `266f860` · 2026-10-09
> **Dua sumber:** sisi kode dari [`app/Enums/`](../../app/Enums/); sisi desain dari file Figma `QIhkFbm9QlPweWuT7qIUyl` ("lo-fi + hi-fi"), diambil lewat Figma MCP pada 2026-10-09.

Jembatan antara nilai yang tersimpan di database dan apa yang dibaca pengguna di layar. Dibutuhkan karena nilai enum di backend **bukan** copy UI — kadang mirip, kadang berbeda, dan beberapa label di desain tidak punya padanan sama sekali.

> **Catatan verifikasi.** Sisi kode bisa Anda cek sendiri di repo. Sisi desain **tidak bisa** — Figma berada di luar repo dan tidak ter-version bersama kode. Semua copy di bawah ini dikutip verbatim dari node teks Figma, termasuk salah tulisnya. Kalau desain berubah, dokumen ini ikut kadaluarsa tanpa sinyal apa pun dari git.

---

## Fakta platform

| Hal | Temuan |
|---|---|
| Jumlah file Figma | **Satu**, 6 canvas, ±580 screen |
| Ukuran frame | **Semuanya 1512px — desktop web**, termasuk seluruh screen siswa. Page design system dibuat di 1440px. |
| Desain mobile | **Tidak ada satu pun.** Tidak ditemukan frame berlebar 375/390/414/430. |
| Aplikasi psikolog terpisah | Tidak ada. Psikolog memakai web dashboard bersidebar, sama seperti role lain. |

**Ini bertentangan dengan dokumen lain.** [`agent/technical-reference.md`](../../agent/technical-reference.md) menyebut backend dikonsumsi web Next.js **dan aplikasi mobile**. Desain tidak memuat layar mobile apa pun. Kedua fakta dicatat; dokumen ini tidak memutuskan mana yang benar — tapi kalau ada klien mobile, UI-nya tidak berasal dari file Figma ini.

### Canvas

| Canvas | node-id | Isi | Pakai? |
|---|---|---|---|
| `hifi next dev` | `3946:31457` | **Terbaru.** Rujukan psikolog, consent, triage BK, Kepsek, Psikolog | ✅ sumber utama |
| `hifi` | `7:19378` | Lebih awal. Siswa (mood/angket/self-help/Cirrus), Admin TU, Kepsek, BK, Guru Wali, Superadmin | ✅ untuk area yang tidak ada di `next dev` |
| `design system` | `23:23` | Token, komponen, ikon | ✅ |
| `assets` | `0:1` | Aset | — |
| `draft` | `355:1250` | Draft | ❌ |
| `hi-fi 2 (draft)` | `2029:28535` | Draft | ❌ |

---

## Section otoritatif per role

Banyak role punya dua sampai tiga section duplikat (lama dan baru). **Aturan: node-id lebih besar = lebih baru.** Memakai section lama adalah cara termudah membangun fitur yang salah.

| Role | Pakai ini | Abaikan |
|---|---|---|
| Siswa | `SISWA #5609:34761` · `SISWA - CURHAT #5505:53468` | `#473:3028`, `SISWA #4049:31830`, `#4852:31662` |
| Guru BK | `GURU BK - Menu Curhatan Siswa New #4138:1117` · `NEWWWW #5505:53467` · `GURU BK, MENU SISWA, NEWWW #5470:39147` | `BK #1050:25215`, **`GURU BK - OLD VER #5444:5036`**, `Dashboard Lama #4815:28448` |
| Kepala Sekolah | `KEPSEK #4502:2615` | `ADMIN KEPSEK #1294:19706` |
| Psikolog | `Psikolog #5499:7907` · `Kelola Jadwal #5499:8801` | `Psikolog #4411:28487`, `#4842:31916` |
| Superadmin | `Superadmin #2696:37277` · `Superadmin - Manajement psikolog #4194:35396` | — |
| Admin TU | `ADMIN TU #1237:39774` | — |
| Guru Wali | `Guru Wali #1050:25216` | — (role dengan screen paling sedikit) |

---

## Struktur navigasi

**Siswa memakai top nav, bukan sidebar** — satu-satunya role yang begitu: `Beranda | Konten | Dark Mode | MN` (inisial avatar). Navigasi balik memakai breadcrumb teks ("Kembali ke Beranda", "Kembali ke Tool kit"). Di Pusat Aktivitas ada tab: `Jadwal Konseling (5) | Curhatan (5) | Rujukan Psikolog (5)`.

Semua role lain memakai sidebar kiri dengan item "Sections" dan tombol logout:

| Role | Item sidebar |
|---|---|
| Admin TU | `Dashboard · Kelola Akun · Kelola Konten · Sections · Sign Out` |
| Kepala Sekolah | `Dashboard · Data Sekolah · Monitoring Penangan · Sections · Sign Out` |
| Guru BK | `Dashboard · Data Siswa · Curhatan Siswa · Jadwal Konseling · Sections · Keluar` |
| Guru Wali | `Dashboard · Sections · Keluar` — akses Data Siswa lewat breadcrumb |
| Psikolog | `Dashboard · Rujukan Masuk · Jadwal Konseling · Sections · Sign Out` |
| Superadmin | `Dashboard · Kelola Sekolah · Kelola TU · Monitoring Sekolah · Kelola API · Sections · Sign Out` |

---

## Pemetaan label — bagian inti

### Zona NLP & prioritas

| Nilai di kode | Copy UI |
|---|---|
| `zone_status = 'Red Zone'` → `priority = 'tinggi'` | `KRITIS (RED ZONE)` · `Kritis` · `Prioritas : Tinggi` · `Kamu Memerlukan Perhatian Segera` |
| `zone_status = 'Yellow Zone'` → `priority = 'sedang'` | `Prioritas (YELLOW ZONE)` · `Prioritas` |
| `zone_status = 'No Trigger'` → `priority = 'rendah'` | `Aman (Bukan Kondisi Darurat)` · `Aman` · "Curhat aja" |
| `priority` tiga nilai | `Tinggi` · `Sedang` · `Rendah` |

Filter di UI psikolog memakai istilah sendiri: `kritis` → `tinggi`, `prioritas` → `rendah` + `sedang` (lihat [`GetPsychologistReferralsRequest`](../../app/Http/Requests/GetPsychologistReferralsRequest.php)).

### `MoodStatus`

| Nilai di kode | Copy UI | Catatan |
|---|---|---|
| `happy` | `Gembira` | |
| `neutral` | `Netral` | |
| `sad` | `Sedih` | |
| `angry` | `Marah` | |
| — | **`Takut`** | `[SPEC-ONLY]` tidak ada padanan di enum |
| `isSecure() == true` | `Aman` | |
| `isSecure() == false` | `Tidak Aman` | |

Copy pengantar: *"Bagaimana perasaanmu hari ini?"* / *"Luangkan waktu sejenak untuk merefleksikan perasaanmu. Informasi ini akan membantu kami memberikan dukungan yang tepat untukmu."*

### `ReportStatus` pada `sharings` (sisi Guru BK)

Desain memakai lebih banyak varian daripada enum, dan kapitalisasinya tidak konsisten.

| Nilai enum | Copy UI yang ditemukan |
|---|---|
| `Belum Ditinjau` | `Belum Ditinjau` · `Belum Ditangani BK` |
| `Sedang Ditangani` | `Sedang Ditangani` · `Sedang Ditangani BK` |
| `Sudah Ditanggapi` | `Sudah Ditanggapi` · `Dibalas` |
| `Belum Ditanggapi` | `Belum Ditanggapi` · `Belum Dibalas` |
| `Menunggu Persetujuan Siswa` | `Menunggu Persetujuan Siswa` · `Butuh Persetujuan` · `Menunggu Persetujuan Rujukan` |
| `Konseling Dijadwalkan` | `Dijadwalin` · `DIJADWAL ULANG` / `Dijadwal Ulang` |
| `Diselesaikan` | `Selesai` · `Diselesaikan` · `SELESAI` |
| `Dibatalkan` | `Dibatalkan` · `DIBATALKAN` |
| `Jadwal Ditolak Siswa` | `Ditolak` |
| `Bukan Urgent` | tidak ditemukan padanannya di desain |
| — | `Dirujuk ke Psikolog` — status tampilan, bukan nilai enum |
| — | `Disetujui` · `Menunggu` — bagian dari kosakata **lama** yang di backend sudah mati (lihat [`08-state-machines.md`](08-state-machines.md)) |

### `ConsentStatus` dan scope — **cocok sempurna dengan backend**

Screen `Review Persetujuan #5609:38576`, judul *"Persetujuan Akses Data"*, pengantar *"Untuk melanjutkan rujukan konseling, berikut data yang Anda izinkan untuk dibagikan kepada psikolog mitra."*

| Scope di kode | Label checkbox | Deskripsi di UI |
|---|---|---|
| `mood_history` | "Riwayat mood 30 hari terakhir" | "Data aktivitas dan pola mood Anda dalam 30 hari terakhir" |
| `sharing_history` | "Kutipan curhat 30 hari terakhir" | "Teks curhat sisawa dalam 30 hari terakhir" *(typo "sisawa" ada di desain)* |
| `assesment_logs` | "Catatan asesmen Guru BK" | "Catatan dan asesmen dari Guru BK sekolah" |

CTA: `Setuju dan Lanjutkan`. Validasi UI: *"Untuk melanjutkan rujukan konseling, pilih minimal 1 data yang akan dibagikan."* — **persis sesuai** aturan `min:1` di [`UpdateConsentRequest`](../../app/Http/Requests/UpdateConsentRequest.php). Jendela 30 hari juga cocok dengan [`ReferralPayloadBuilder`](../../app/Services/ReferralPayloadBuilder.php).

Status consent di UI: `Persetujuan Berhasil` (dengan auto-redirect *"Otomatis dialihkan dalam 5 detik..."*).

### `SlotStatus` — cocok

| Nilai | Copy UI |
|---|---|
| `available` | `Tersedia` |
| `tentative` | `Menunggu Konfirmasi` |
| `confirmed` | `Terkonfirmasi` |

Legend di screen `Kelola Jadwal Konsultasi #5499:8802` persis bertuliskan `Status Slot: Tersedia | Menunggu Konfirmasi | Terkonfirmasi`, plus statistik `Terkonfirmasi 3 | Menunggu Konfirmasi 2 | Slot Tersedia 3`.

> Penting: desain mengasumsikan `tentative` dipakai, padahal di backend **tidak pernah ditulis oleh kode aplikasi** (hanya oleh seeder). Lihat [`08-state-machines.md`](08-state-machines.md). Layar ini akan menampilkan slot sebagai "Tersedia" padahal sudah ada booking pending.

### `BookingStatus`

| Nilai | Copy UI |
|---|---|
| `pending` | `MENUNGGU KONFIRMASI` · `Menunggu Konfirmasi Psikolog` |
| `confirmed` | `Terkonfirmasi` |
| `rejected` | `Perubahan Jadwal Diajukan` — bukan "ditolak"; desain membingkainya sebagai usulan jadwal baru |
| `expired` | *"Batas waktu rujukan telah berakhir. Silakan ajukan ulang untuk memilih jadwal konsultasi yang baru."* |
| `finished` | `Rujukan selesai` |

### `SharingAction` dan keputusan tindak lanjut BK

Screen `#5448:13161`, *"Pilih satu keputusan penanganan untuk kasus ini."* — single-select **4 opsi**, sementara enum hanya punya 3:

| Opsi UI | Keterangan di UI | Padanan di kode |
|---|---|---|
| "Konseling Mandiri" | "Guru BK menangani langsung" | `SharingAction::INTERNAL` = `konseling_mandiri` |
| "Perlu Rujukan Psikolog" | — | **bukan `SharingAction`** — jalannya lewat `CounselingResolution::NEEDMORE` + membuat counseling `external` |
| "Perlu Penanganan Medis" | "Rujuk ke IGD / fasilitas kesehatan" | `SharingAction::MEDIC` = `penanganan_medis` |
| "Lainnya" | "Tindak lanjut di luar opsi di atas" | `SharingAction::OTHER` = `lainnya` |

Jadi satu kontrol UI memetakan ke **dua mekanisme backend berbeda**. Jangan asumsikan keempat opsi menulis ke kolom `sharings.action`.

### `CounselingResolution`

Nilai enum sangat panjang dan jelas bukan copy UI: `'Bukan Kondisi Kritis (Red Zone)'`, `'Bukan Kondisi Prioritas (Yellow Zone)'`, `'Perlu Rujukan Professional'`. Desain tidak menampilkan string ini apa adanya — frontend perlu memetakannya sendiri.

### SLA

| Di kode | Copy UI |
|---|---|
| `cutdown_for_report` | `BATAS TINDAK LANJUT` · `Batas Waktu` · countdown `01:58:10` · `Tindakan Segera Diperlukan` |
| `is_sla_breached` (≥ 2 jam) | `Pelanggaran SLA` · `DALAM Batas Waktu` |

Dashboard Kepsek juga menampilkan `TOTAL KASUS AKTIF`, `Intervensi Selesai`, `Rujukan Psikolog`, modal `Timeline Penanganan` ("Kasus terdeteksi" → "Notifikasi terkirim" → …), dan disclaimer privasi *"Informasi ditampilkan terbatas untuk menjaga privasi siswa"* — konsisten dengan `PrincipalDashboardController` yang sengaja tidak mengirim `title`/`description`/`reply`.

### Gamifikasi Cirrus — cocok dengan backend

| Di kode | Copy UI |
|---|---|
| `clouds.water` | **"Tetesan Air"** / "tetesan embun" · `Total Tetesan Air 122` |
| `clouds.level` | `Level 1` |
| `clouds.exp` | `Pengalaman 50 XP` · `50/100 XP` |
| `clouds.happiness` | `Kebahagiaan 100%` |
| `POST /api/buy` | `Toko Makanan` — "Pilih makanan untuk Cirrus", "4 item tersedia" |
| `POST /api/claim` | `Hadiah Harian` — "Klaim tetesan embun gratis", "Klaim dalam 24 jam" |

Interaksi: "Klik awan untuk berinteraksi!" dengan state happiness 15% versus 100%.

### Anonimisasi siswa di layar psikolog

Sebelum consent diberikan, desain menampilkan siswa sebagai `Siswa #A-2812`, `Siswa #B-1930`. Backend memakai skema berbeda: `stu_` + 8 karakter pertama `md5(user.id)` di `raw_payload`. Keduanya menyamarkan identitas, tapi **formatnya tidak sama** — jangan harap backend mengirim `#A-2812`.

Transkrip curhat di screen alert BK juga disensor di desain ("Saya ingin b**** d***"), sementara di backend fungsi penyamaran justru **dimatikan** (lihat [`07-data-model/04-referral-and-consent.md`](07-data-model/04-referral-and-consent.md)). Desain lebih protektif daripada implementasi.

---

## Field form penting

**Curhat** (`#5505:53469`): `Judul` (contoh "Stress Menghadapi Ujian") + `Isi` (placeholder "Type your message here.") + "Ceritakan lebih lanjut" + tombol `Kirim`. Ada 4 tema panduan: Tekanan Akademik, Kesehatan Mental, Masalah Keluarga, Pengembangan Diri. Backend hanya menyimpan `title` dan `description` — tema panduan tidak dipersistensi.

**Kontak darurat** (`#5450:6347`): "Kontak Bantuan Darurat" / "Hotline Darurat Nasional" / `119` / tombol `Salin Nomor`, `Hubungi 119`, `Tutup`. Cocok dengan `schools.emergency_contacts` yang di-seed dengan `119`.

**CRUD psikolog** (`#4284:33533`): section "Akun Kredensial" (Email*, Password*, checkbox "Kirim kredensial login ke email psikolog") + "Informasi Profesional" (nomor STR contoh `STR-PSI-00192`, instansi, email). Catatan: **checkbox kirim kredensial lewat email tidak ada implementasinya** — mail driver default `log` dan tidak ada notification untuk ini.

**Profil**: staf menampilkan Nama Lengkap, Username, Nomor Telepon, NIP/NUPTK, Peran, Hak Akses. Siswa menampilkan Nama Lengkap, NISN, Username, No Telepon, Kelas, Sekolah, wali kelas.

**Kelola jadwal psikolog** (`#5499:8802`): grid mingguan Senin–Sabtu, slot per jam ("09:00–10:00"), tombol `Tambah Jadwal` / `+ tambah` per hari. Catatan desain: "Hapus disabled = tidak dapat dihapus" dan "Jadwal yang dipublikasikan akan tersedia untuk siswa dengan rujukan aktif" — keduanya cocok dengan `PsychologistSlotPolicy::delete` dan guard booking aktif.

**State yang digambarkan**: toast sukses ("Profil Anda berhasil disimpan !"), modal perubahan belum tersimpan ("Simpan Perubahan? | ... | Batal | Simpan"), validasi consent minimal 1, countdown SLA, auto-redirect 5 detik, pesan kadaluarsa. **Tidak ditemukan** state loading/skeleton maupun empty state eksplisit.

Filter & tabel umum: `Cari...`, `Cari nama siswa...`, `Pilih Kelas`, `Pilih Peran`, `Pilih Status`, `Pilih Sekolah`, `Pilih Jenis Konten`, `Pilih Akses`, `Pilih Waktu`, `Actions`/`Aksi`, `Cetak Laporan`, `Tambah Sekolah/Kelas/TU/Jadwal`.

---

## `[SPEC-ONLY]` — ada di desain, belum ada di backend

| Fitur | Bukti di desain | Kondisi di backend |
|---|---|---|
| Mood `Takut` | 5 opsi mood | `MoodStatus` hanya 4 nilai |
| 4 aktivitas self-help tambahan | Latihan Pernapasan, Pelukan Kupu-Kupu, Aktivitas Fisik (timer 10/15/20 Menit, Atur/Ulang/Mulai/Jeda), Afirmasi Diri — dikategorikan Emotional / Mindfulness / Physical | `self_helps.type` hanya 4 nilai. Konsisten, tab "Riwayat Self Help" Guru Wali juga hanya menampilkan 4 |
| **Konfirmasi orang tua/wali** | "Orang tua/wali siswa telah dihubungi dan menyetujui proses pengajuan rujukan konseling." — pernyataan wajib sebelum rujukan | Tidak ada kolom, tabel, atau endpoint. Tidak ada aktor orang tua |
| **Reschedule dua arah** | "Psikolog mengajukan perubahan jadwal" `#5609:39677` → siswa menyetujui/menolak; "Ajukan Perubahan Jadwal", "Perubahan Jadwal Diajukan" | `DecideReferralAction` membuat booking baru langsung `confirmed`, tanpa persetujuan ulang siswa |
| **Superadmin "Kelola API"** | `#2727:50076` — "API Key / Token", nilai, status "Aktif" | Tabel `gemini_api_token` **sudah ada** dan dirotasi otomatis, tapi tidak ada endpoint CRUD. Ini kemungkinan UI untuk tabel itu |
| Feedback kebermanfaatan artikel | `#1654:26637` — "Apakah artikel ini bermanfaat? | Bermanfaat | Tidak" | Tidak ada tabel atau endpoint |
| Dark Mode | Toggle di top nav siswa | Tidak relevan bagi backend (preferensi klien), tapi tidak ada tempat menyimpannya per user |
| `Cetak Laporan` / export | Di Data Siswa, Pola Mood, Riwayat Self Help | Hanya export mood yang ada (3 endpoint Excel). Tidak ada export daftar siswa atau self-help |
| Hasil angket sebagai arketipe bernama | "Navigator Masa Depan", "Ekspedisi Menemu Jati Diri", "Laporan Pahlawan", "Misi Eksplorasi Anda (Minggu Ini)", CTA "MARI EKSPLORASI" | Backend menghasilkan `The Strategist` / `The Advocate` / `The Innovator` / `The Builder` (jalur secure, murni Inggris) dan `The Sage (Sang Bijak)` / `The Artisan (Sang Perajin)` / `The Guardian (Sang Penjaga)` / `The Architect (Sang Arsitek)` (jalur insecure, Inggris + Indonesia). **Tidak satu pun cocok dengan nama di desain** — perlu diputuskan mana yang benar |

### Arah sebaliknya — ada di backend, tanpa desain

- **Login pertama wajib** (`PATCH /api/profile`) dan **lupa password**: tidak ada screen-nya. Hanya ada layar login dan tautan "Forgot password?" di form login psikolog yang tidak menuju ke mana-mana.
- **Manajemen quotes**: tidak ditemukan screen-nya, padahal ada endpoint create/delete.
- **Manajemen video YouTube per se**: hanya ada "Kelola Konten Edukasi | Management artikel dan video" secara umum.
- Ringkasan klinis AI **ada** desainnya: `Laporan AI & Catatan Klinis #5281:5171` ("Ringkasan kasus dan anotasi klinis profesional").

---

## Inkonsistensi label yang perlu diputuskan

Bukan temuan bug — ini keputusan produk yang lebih baik diambil sekali daripada ditebak per fitur.

| Hal | Variasi yang ditemukan |
|---|---|
| Kapitalisasi status | `Selesai` vs `selesai` vs `SELESAI`; `Dibatalkan` vs `DIBATALKAN` |
| Status jadwal | `Disetujui` vs `Dijadwalin` vs `Dijadwal Ulang` |
| Tombol logout | `Sign Out` vs `Keluar` — berbeda antar role dan bahkan antar frame |
| Typo di desain | `Monitoring Penangan` (sidebar Kepsek, seharusnya "Penanganan") · `sisawa` (deskripsi scope consent) · `Pantai kondisi siswa` (seharusnya "Pantau") |
| Nama arketipe | Bahasa Indonesia di desain vs Bahasa Inggris di kode |

Typo di atas dikutip apa adanya **secara sengaja**, supaya cocok dengan apa yang developer lihat di Figma. Jangan dirapikan di dokumen ini tanpa merapikan desainnya.

---

## Design system (ringkas)

Berada di page `design system #23:23`, bukan file terpisah.

| Aspek | Nilai |
|---|---|
| Ikon | **Tabler Icons**, dua set lengkap: Filled `#134:52071`, Outline `#188:28563` |
| Tipografi | **Plus Jakarta Sans** (primer) + **Inter** (sekunder). Skala bernama: Display-Heading 1/61px · Heading 3/39px · Heading 4/31px · Subheading/25px · Title-Section Title/20px · Body/16px · Caption/13px, masing-masing Regular/Medium/Bold/Black |
| Warna inti | Indigo `#6366F1` / `#4F46E5`; netral slate `#F8FAFC` `#CBD5E1` `#E5E7EB` `#374151`; amber `#FD9D26` |
| Warna semantik zona | Merah `#E23648` `#F43F5E` · Kuning/amber `#FBBF24` `#FDE047` `#FFB900` · Hijau `#16A34A` · Biru `#2563EB`, plus tint 50 (`#FEF2F2`, `#FEF3C7`, `#FFF7ED`, `#FFF1F2`) |
| Komponen | `Stepper #279:33695` — **variannya persis flow siswa**: step 1 "mood check-in", step 2 "isi angket", step 3 "hasil". Juga `Buttons`, `Radio button`, `radio quest` (kartu pilihan), `progress bar` (10%–100%), `sort` |
| Template laporan | `Laporan Navigator #2223:62811`, `Laporan Pahlawan #3118:76519` — template hasil angket per arketipe |

Ramp warna lengkap 50→950 bergaya Tailwind ada di frame `In this file #192:28217`.

---

## Yang tidak tersedia

**Data prototype tidak bisa dibaca.** Figma MCP tidak mengembalikan `interactions`, `navigateTo`, `reactions`, maupun `prototypeStartNode` — nol hasil di semua page. Akibatnya **urutan navigasi antar screen di [`05-user-flows.md`](05-user-flows.md) bersifat inferensi**, disusun dari nama frame, posisi, dan copy tombol. Urutan pemanggilan API di dokumen itu tetap terverifikasi dari kode; yang tidak terverifikasi adalah urutan layarnya.

Spesifikasi komponen per node (padding, radius, state hover/disabled) belum diambil. Page `assets`, `draft`, dan `hi-fi 2 (draft)` belum ditelusuri dan dianggap bukan sumber kebenaran.
