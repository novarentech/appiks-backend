# Glosarium Domain APPIKS

<!-- verified: branch=dev commit=266f860 date=2026-10-09 scope=app/Enums,app/Models,database/migrations -->
> **Terverifikasi terhadap:** `dev` @ `266f860` · 2026-10-09
> **Sumber utama:** [`app/Enums/`](../../app/Enums/) · [`app/Models/`](../../app/Models/) · [`database/migrations/`](../../database/migrations/)

Kode APPIKS mencampur Bahasa Indonesia dan Inggris — kadang dalam satu baris yang sama. Nama tabelnya berbahasa Inggris (`sharings`, `mood_records`), tapi nilai enum-nya berbahasa Indonesia (`'Belum Ditinjau'`, `'Tatap Muka'`). Dokumen ini jembatannya. Baca ini dulu sebelum dokumen lain.

Pemetaan nilai enum ke **copy UI verbatim** ada di [`12-ui-contract.md`](12-ui-contract.md), bukan di sini.

---

## Aktor

| Istilah | Identifier di kode | Arti |
|---|---|---|
| Siswa | `UserRole::STUDENT` = `'student'` | Pengguna utama. Punya `room_id`, `mentor_id`, `counselor_id`, `school_id`. |
| Guru BK | `UserRole::COUNSELOR` = `'counselor'` | Guru Bimbingan & Konseling. **Aktor pivotal** — melakukan triage curhat, menjadwalkan konseling, memutuskan rujukan. Dikaitkan ke siswa lewat `users.counselor_id`. |
| Guru Wali | `UserRole::TEACHER` = `'teacher'` | Wali kelas. Dikaitkan ke siswa lewat `users.mentor_id`. Di kode disebut *mentor*, di UI disebut *Guru Wali*. |
| Kepala Sekolah | `UserRole::HEADTEACHER` = `'headteacher'` | Pemantau tingkat sekolah. Hanya melihat metadata insiden, bukan isinya. |
| Admin TU | `UserRole::ADMIN` = `'admin'` | Tata Usaha sekolah. Membuat akun staf & siswa, mengelola kelas dan konten. |
| Psikolog Mitra | `UserRole::PSYCHOLOGIST` = `'psychologist'` | Psikolog profesional **eksternal**, lintas sekolah. Wajib punya baris di `psychologist_profiles`; tanpa itu aksesnya ditolak 403. |
| Superadmin | `UserRole::SUPER` = `'super'` | Pemilik platform. Mengelola sekolah dan akun psikolog mitra. |
| Orang tua / wali | — | **`[SPEC-ONLY]`** Bukan aktor sistem. Tidak ada role, tabel, maupun endpoint. Desain Figma mewajibkan pernyataan bahwa orang tua sudah dihubungi sebelum rujukan, tapi backend tidak merekamnya. |

Sumber: [`app/Enums/UserRole.php`](../../app/Enums/UserRole.php), [`database/migrations/2025_08_25_221626_create_users_table.php`](../../database/migrations/2025_08_25_221626_create_users_table.php)

---

## Identitas & struktur sekolah

| Istilah | Identifier di kode | Arti |
|---|---|---|
| Sekolah | tabel `schools` | Unit tenancy. Punya `emergency_contacts` (JSON; seeder mengisi hotline nasional `119`). |
| Kelas / Rombel | tabel `rooms` | `name` + `level` (enum `X` / `XI` / `XII`) + `code` (char 8, unik). Saat membuat siswa, yang dipakai adalah `code`, bukan ID. |
| NISN | `users.identifier` untuk siswa | Nomor induk siswa, 10 digit, unik lintas sistem. |
| NIP / NUPTK | `users.identifier` untuk staf | 18 digit untuk guru/BK/kepsek; 16–25 digit untuk admin. |
| STR | `psychologist_profiles.str_number`, juga disimpan di `users.identifier` | Surat Tanda Registrasi — nomor izin praktik psikolog. Unik. |
| Terverifikasi | `users.verified` (boolean) | `false` sampai pengguna menyelesaikan login pertama (ganti password, username, nomor telepon). **Bukan** verifikasi email. |
| Master wilayah | tabel `locations` | Data kelurahan/kecamatan/kota/provinsi Indonesia. **Tidak** di-FK-kan — `schools` menyimpan nama wilayah sebagai string. |

---

## Mood & asesmen

| Istilah | Identifier di kode | Arti |
|---|---|---|
| Mood check-in | tabel `mood_records`, `POST /api/mood_record` | Satu entri per siswa per hari (unique gabungan `recorded` + `user_id`). |
| Kolom tanggal mood | `mood_records.recorded` | **Bukan** `date` atau `recorded_at`, dan tipenya `date`. Sering membuat query salah. |
| Aman / Tidak Aman | `MoodStatus::label()` | `happy` dan `neutral` → "Aman"; `sad` dan `angry` → "Tidak Aman". |
| secure / insecure | `MoodStatus::isSecure()` | Istilah internal untuk hal yang sama. Menentukan **bank soal kuesioner** mana yang disajikan dan jenis quote mana yang muncul. |
| Kuesioner persona / angket | tabel `questionnaires` | Bank soal. Kolom `type` = `secure` (7 soal) atau `insecure` (10 soal). |
| Arketipe / persona | tidak ada tabel | Hasil interpretasi kuesioner. Jalur `secure` dihitung deterministik di kode; jalur `insecure` dibantu Gemini. |
| Cache jawaban AI | tabel `ai_generated` | Jawaban siswa **tidak pernah disimpan**. Pilihan jawaban diubah menjadi kunci huruf (mis. `ABCDA`), lalu interpretasinya di-cache per kunci. |
| Misi mingguan | bagian dari hasil kuesioner `insecure` | Dua misi hasil generate Gemini. Tidak dipersistensi sebagai tabel sendiri. |

Sumber: [`app/Enums/MoodStatus.php`](../../app/Enums/MoodStatus.php), accessor `last_mood` di [`app/Models/User.php`](../../app/Models/User.php)

---

## Curhat & triage

| Istilah | Identifier di kode | Arti |
|---|---|---|
| Curhat | tabel `sharings`, `POST /api/sharing` | Cerita bebas dari siswa. Inti sistem deteksi dini. |
| Laporan / Report | tabel `reports`, `POST /api/report` | **Berbeda dari curhat**: ini permintaan bertemu Guru BK. Hanya boleh dibuat kalau mood terakhir `sad` atau `angry`. |
| Analisis NLP | tabel `nlp_analyses` | Hasil microservice Flask eksternal. Relasi polymorphic (`nlpable_type` / `nlpable_id`); saat ini hanya dipasang ke `Sharing`. |
| Zona Merah / Red Zone | `zone_status` = `'Red Zone'` | Indikasi krisis → prioritas `tinggi`, SLA **2 jam**. |
| Zona Kuning / Yellow Zone | `zone_status` = `'Yellow Zone'` | Perlu perhatian → prioritas `sedang`, SLA **72 jam**. |
| No Trigger / Zona Aman | `zone_status` = `'No Trigger'` | Tidak terdeteksi risiko → prioritas `rendah`, tanpa SLA. |
| Prioritas | kolom `priority` pada `sharings` & `reports` | `tinggi` / `sedang` / `rendah`, ditulis oleh job NLP. Enum [`Priority`](../../app/Enums/Priority.php) ada tapi **tidak dipakai** — kolomnya enum MySQL biasa. |
| Cutdown | `sharings.cutdown_for_report`, `reports.cutdown_for_report` | Batas waktu tindak lanjut (timer SLA). Dinolkan oleh command `cutdown:clear` setelah lewat. Namanya menyesatkan — ini *deadline*, bukan hitungan. |
| False positive | `NlpAnalysisStatus::FALSE_POSITIVE` | Guru BK menyatakan alert NLP salah. Hanya boleh saat status masih `Belum Ditinjau`. Efek: prioritas → `rendah`, status → `Bukan Urgent`, SLA dimatikan, alasannya disimpan sebagai umpan balik pelatihan model. |
| Tindak lanjut curhat | [`SharingAction`](../../app/Enums/SharingAction.php) | `konseling_mandiri` / `penanganan_medis` / `lainnya`. Perhatikan: opsi "Perlu Rujukan Psikolog" di UI **bukan** bagian enum ini — rujukan jalan lewat `CounselingResolution`. |

---

## Konseling & rujukan

| Istilah | Identifier di kode | Arti |
|---|---|---|
| Konseling | tabel `counselings` | **Agregat pusat sistem.** Menautkan siswa, Guru BK, psikolog, curhat, dan laporan. |
| Konseling internal | `counselings.type = 'internal'` | Ditangani Guru BK sendiri di sekolah. |
| Rujukan | `counselings.type = 'external'` | Dirujuk ke Psikolog Mitra. Inilah yang memicu alur consent. |
| Sumber kasus | `counselings.source_type` | `regular` (dari laporan) atau `nlp_incident` (dari curhat yang ditandai NLP). |
| Resolusi | [`CounselingResolution`](../../app/Enums/CounselingResolution.php) | Tiga nilai. `'Perlu Rujukan Professional'` adalah **pemicu rujukan**; dua lainnya menutup kasus sebagai bukan-kritis atau bukan-prioritas. |
| Metode | [`CounselingMethod`](../../app/Enums/CounselingMethod.php) | `'Tatap Muka'` / `'Video Call'` / `'Chat'`. Disimpan di `counselings.method` dan di `counseling_logs.session_mode`. |
| Catatan klinis | `counseling_logs.clinical_notes` | Cast `encrypted` — terenkripsi di level aplikasi, **tidak bisa di-query atau dicari lewat SQL**. |
| Jejak kasus / timeline penanganan | tabel `case_events` | Append-only. Satu-satunya tempat **urutan** kejadian tersimpan — kolom status di tabel lain ditimpa di tempat. 28 jenis kejadian di [`CaseEventType`](../../app/Enums/CaseEventType.php). Akses saat ini hanya Kepala Sekolah. Lihat [`13-case-timeline.md`](13-case-timeline.md) |
| Audit trail | tabel `counseling_log_histories` | Append-only. Setiap perubahan `clinical_notes` otomatis menyimpan nilai lamanya beserta `updated_by`. Tidak punya soft delete. |
| Persetujuan data / consent | tabel `counseling_consents` | Izin siswa membagikan datanya ke psikolog. Dibuat otomatis berstatus `pending` begitu konseling `external` dibuat. |
| Scope consent | `counseling_consents.scopes` (JSON) | Tepat tiga nilai yang sah: `mood_history`, `sharing_history`, **`assesment_logs`**. Minimal satu wajib dipilih saat memberi izin. |
| Slot | tabel `psychologist_slots` | Ketersediaan waktu yang dipublikasikan psikolog. Status `available` / `tentative` / `confirmed`. |
| Booking | tabel `booking_schedules` | Pengajuan jadwal oleh siswa atas sebuah slot. `deadline_at` = **24 jam** sejak dibuat. |
| Ringkasan klinis AI | tabel `clinical_summaries` | Narasi hasil Gemini + `raw_payload` (data mentah yang dikirim ke AI, sudah disaring sesuai scope). `raw_payload` tidak pernah dikirim ke klien. |

---

## Self-help & gamifikasi

| Istilah | Identifier di kode | Arti |
|---|---|---|
| Self-help | tabel `self_helps` | Latihan mandiri. Kolom `type` punya **4 nilai**: `'Daily Journaling'`, `'Gratitude Journal'`, `'Grounding Technique'`, `'Sensory Relaxation'`. Isi `content` berupa JSON dengan bentuk berbeda per tipe — lihat [`database/factories/SelfHelpFactory.php`](../../database/factories/SelfHelpFactory.php). |
| Cirrus | tabel `clouds` | Hewan peliharaan berbentuk awan. Satu per siswa, dibuatkan otomatis saat akun siswa dibuat. |
| Tetesan Air / tetesan embun | `clouds.water` | Mata uang dalam game. Istilah UI-nya "Tetesan Air"; di kode hanya `water`. |
| Level & XP | `clouds.level`, `clouds.exp` | Naik level otomatis saat `exp` ≥ 100. |
| Streak | `clouds.streak` dan streak mood | Dua hal berbeda bernama sama: `clouds.streak` untuk check-in harian game, sementara streak mood dihitung dari `mood_records`. |

---

## Konten

| Istilah | Identifier di kode | Arti |
|---|---|---|
| Konten edukasi | tabel `videos`, `articles` | Per sekolah (`school_id`). Video adalah embed YouTube (`video_id`); artikel memakai JSON pohon editor Lexical — **bukan HTML**. |
| Quote | tabel `quotes` | Kolom `type` = `secure` / `insecure` / `daily`. Dua yang pertama disajikan sesuai mood siswa hari itu. |
| Tag | tabel `tags` + pivot `video_tag`, `article_tag` | Empat tag bawaan seeder: Self Awareness, Mindfulness, Mental Health, Bullying. |

---

## Jebakan penamaan

Hal-hal yang paling sering membuat developer baru salah. Semuanya sudah diverifikasi di kode.

1. **`assesment_logs` salah tulis — dan harus tetap salah.** Seharusnya `assessment` (dua `s`). Nilai ini dipakai konsisten di validasi, seeder, dan payload builder, jadi memperbaikinya adalah breaking change. Lihat [`app/Http/Requests/UpdateConsentRequest.php`](../../app/Http/Requests/UpdateConsentRequest.php).

2. **`ReportStatus` dipakai dua tabel.** Enum yang sama untuk `reports.status` dan `sharings.status`, tapi state yang tercapai berbeda. Beberapa case-nya bahkan menggambarkan tahap konseling atau booking, bukan tahap laporan. Lihat [`08-state-machines.md`](08-state-machines.md).

3. **Dua arti `psychologist_id`.** Di `counselings.psychologist_id` menunjuk ke `users.id`; di `psychologist_slots.psychologist_id` menunjuk ke `psychologist_profiles.id`.

4. **Enum `Priority` tidak dipakai.** Kolom `priority` dideklarasikan sebagai enum MySQL inline. Jangan berasumsi nilainya ter-cast ke enum PHP.

5. **Bandingkan enum sebagai case, bukan sebagai `->value`.** Pada model yang meng-cast kolom status ke enum (mis. `Sharing`), ekspresi `$model->status == ReportStatus::X->value` **selalu false**. Bug ini pernah mematikan satu policy secara senyap (commit `26ac4b2`).

6. **`mood_records.recorded`**, bukan `date`.

7. **`cutdown_for_report`** adalah *deadline*, bukan durasi.

8. **Guru Wali = `teacher` = mentor.** Tiga nama untuk satu hal: UI menyebut "Guru Wali", enum menyebut `teacher`, kolom FK menyebut `mentor_id`.

9. **Curhat ≠ Laporan.** `sharings` adalah cerita bebas; `reports` adalah permintaan bertemu. Keduanya punya kolom `priority`, `status`, dan `cutdown_for_report` yang mirip, dan keduanya bisa bermuara ke konseling — lewat jalur berbeda.

10. **Beberapa kolom relasi tidak punya foreign key di level database.** `counselings.student_id`, `counselings.counselor_id`, `counselings.sharing_id`, `self_helps.user_id`, dan `clouds.user_id` dibuat tanpa `constrained()`. Relasi Eloquent-nya ada, tapi tidak ada jaminan integritas dari DB. Lihat [`07-data-model/README.md`](07-data-model/README.md).

11. **Tidak ada global scope.** Pemisahan antar sekolah dilakukan manual di setiap controller. Melewatkan filter `school_id` berarti membocorkan data sekolah lain.
