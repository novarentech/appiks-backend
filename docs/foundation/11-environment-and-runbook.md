# Environment & Runbook

<!-- verified: branch=dev commit=working-tree date=2026-10-10 scope=database/seeders,app/Console/Commands,composer.json,phpunit.xml,.github/workflows -->
> **Terverifikasi terhadap:** `dev` working tree · 2026-10-10
> **Sumber:** [`database/seeders/`](../../database/seeders/) (23 file) · [`app/Console/Commands/`](../../app/Console/Commands/) · [`composer.json`](../../composer.json) · [`phpunit.xml`](../../phpunit.xml)

Cara menjalankan APPIKS di mesin sendiri, data demo apa yang tersedia, dan apa saja yang bisa membuat Anda bingung di sepuluh menit pertama.

---

## Menjalankan dari nol

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
# isi DB_* di .env, lalu:
php artisan migrate --seed
php artisan serve
```

Lalu **dua proses tambahan di terminal terpisah** — ini bukan opsional:

```bash
php artisan queue:work      # tanpa ini: analisis NLP & ringkasan AI tidak pernah jalan
php artisan schedule:work   # tanpa ini: booking tidak pernah kadaluarsa, timer SLA tidak dibersihkan
```

Kegagalan paling umum saat development: curhat tersimpan tapi prioritas dan tenggatnya kosong. Penyebabnya hampir selalu worker yang tidak jalan atau `NLP_SERVICE_URL` yang belum diisi.

Dokumentasi API langsung tersedia di `/docs` — tidak perlu Postman.

### Catatan lingkungan

| Hal | Nilai |
|---|---|
| PHP | ^8.4 |
| Database | MySQL (`DB_CONNECTION=mysql`, database `appiks`). `config/database.php` punya fallback `sqlite`, dan `phpunit.xml` memakai sqlite `:memory:` |
| Zona waktu | `Asia/Jakarta` — semua perhitungan "hari ini" bergantung ini |
| Lokal | `locale=id`, `faker_locale=id_ID` |
| Queue | `database` |
| Mail | Driver default `log` — tidak ada email yang benar-benar terkirim |
| Mesin dev di sini | Windows + PowerShell |

Tanpa `NLP_SERVICE_URL` dan `GEMINI_API_KEY`, sistem tetap jalan: curhat tersimpan tanpa triage, dan ringkasan AI mengembalikan `null`. Daftar lengkap env var ada di [`10-architecture-and-integrations.md`](10-architecture-and-integrations.md).

---

## Perintah artisan penting

| Perintah | Fungsi |
|---|---|
| `php artisan migrate --seed` | Setup awal |
| `php artisan migrate:fresh --seed` | **Menghapus semua data.** Jangan pernah dijalankan terhadap database bersama |
| **`php artisan migrate:fresh-backup`** | Yang sebaiknya Anda pakai saat iterasi lokal. Mencadangkan `ai_generated` dan `gemini_api_token` ke `storage/app/backup_*.json`, menjalankan `migrate:fresh`, memulihkan kedua tabel itu, lalu `db:seed`. Kedua tabel ini mahal dibangun ulang karena menghabiskan kuota API nyata |
| `php artisan migrate:rollback` | **Jangan.** Dua `down()` tidak lengkap — `articles`, `article_tag`, dan `clouds` tertinggal. Lihat [`07-data-model/README.md`](07-data-model/README.md) |
| `php artisan import:locations` | Impor massal data wilayah Indonesia dari `public/database/seeder/location.sql` |
| `php artisan db:seed --class=DemoCaseSeeder` | Data demo bersih dengan analisis NLP sungguhan |
| `php artisan cutdown:clear` | Bersihkan timer SLA yang sudah lewat (dijadwalkan tiap 10 menit) |
| `php artisan referrals:expire-pending` | Kadaluarsakan booking yang melewati 24 jam (tiap 15 menit) |
| `php artisan generate:archtype` | Panaskan cache arketipe AI. Entri schedulernya dikomentari |
| `php artisan compress:thumbnail` | Kompresi thumbnail artikel lewat layanan eksternal |
| `php artisan lowercase:username` | Perbaikan data satu kali |

---

## Akun demo

Semua akun memakai `config('app.default_password')` — nilai bawaan **`password`**.

### Dari `DatabaseSeeder` (jalur `migrate --seed`)

| Username | Peran | `verified` |
|---|---|---|
| `super` | Superadmin | ✅ |
| `headteacher` | Kepala Sekolah | ✅ |
| `admintu` | Admin TU | ✅ |
| `guruwali` | Guru Wali | ✅ |
| `gurubk` | Guru BK | ✅ |
| `siswa{roomId}active` | Siswa | ✅ — **pakai ini untuk menguji alur siswa** |
| `siswa{roomId}` | Siswa | ❌ — pakai ini untuk menguji alur login pertama |
| 8 siswa acak | Siswa | ✅ |

Satu sekolah, dua kelas dengan kode tetap `aa11aa11` dan `bb22bb22`.

### Dari `DemoCaseSeeder` (jalur demo)

| Username | Peran |
|---|---|
| `super` | Superadmin |
| `kepsek` | Kepala Sekolah |
| `admintu` | Admin TU |
| `bkdemo1`, `bkdemo2` | Guru BK, masing-masing 4 siswa |
| `siswa1` … `siswa8` | Siswa |
| `ermin`, `yulia` | Psikolog Mitra (dengan nomor STR) |

Akun siswa yang `verified = false` **masih bisa login dengan password bawaan**. Ini bukan bug di seeder — begitulah cara provisioning bekerja, dan konsekuensinya dibahas di [`02-actors-and-scoping.md`](02-actors-and-scoping.md).

---

## Seeder sebagai skenario yang bisa dijalankan

Karena tidak ada test (lihat bagian terakhir), **seeder adalah dokumentasi perilaku yang paling akurat yang tersisa**. Masing-masing meninggalkan database dalam keadaan tertentu yang mendemokan satu alur.

### `ReferralFlowSeeder` — 625 baris, spesifikasi terkaya untuk domain rujukan

Dijalankan dua kali, sekali per psikolog (`ermin` dengan `bkdemo1`, `yulia` dengan `bkdemo2`), masing-masing 6 siswa. Setiap skenario membangun `Report` + `Sharing` + `Counseling` bertipe `external` dengan resolusi `Perlu Rujukan Professional`.

| # | Skenario | Keadaan akhir | Mendemokan |
|---|---|---|---|
| 1 | **Tiga scope → selesai** | Consent `granted` dengan ketiga scope; **satu booking `rescheduled`** mendahului booking `finished`; `CounselingLog` + `ClinicalSummary` lengkap termasuk `raw_payload` ketiga scope; `rating = good` | FLOW-4 jalur penuh, sekaligus fixture `rescheduled` dan flag `was_rescheduled` |
| 2 | **Hanya mood → menunggu** | Scope `[mood_history]`; slot `tentative`; booking `pending`, `deadline_at = now()+18 jam`; counseling `menunggu_konfirmasi`; `raw_payload` hanya berisi baris mood | Penegakan consent per scope |
| 3 | **Curhat + asesmen → menunggu** | Scope `[sharing_history, assesment_logs]`; booking `pending`, `deadline_at = now()+22 jam`; counseling `menunggu_konfirmasi` | Kombinasi scope selain mood |
| 4 | **Hanya asesmen → kadaluarsa** | Scope `[assesment_logs]`; booking `expired`, `deadline_at = now()-12 jam`; slot `available`; **counseling `menunggu_jadwal`** — siap dipakai menguji "Pilih Jadwal Baru" | Pekerjaan `referrals:expire-pending` dan pemulihan setelah kadaluarsa |
| 5 | **Terkonfirmasi, akan datang** | Ketiga scope; slot + booking `confirmed` Senin depan 09:00; `location = "{institution}, Lt. 2, Ruang Konseling"` | Keadaan siap-sesi |
| 6 | **Consent masih menunggu** | Consent `pending`, `scopes = null`; counseling `menunggu`; laporan `Menunggu Persetujuan Siswa` | Titik awal alur consent |

> Skenario 5 dan 6 hanya jalan bila psikolog punya 6 siswa; `DemoCaseSeeder` memberi 4, jadi keduanya biasanya terlewat. `BookingStatus::REJECTED` tidak punya fixture — nilai itu dihasilkan lewat `decide` `action=reject` di runtime.

### `CounselingFlowSeeder` — 6 siklus konseling internal

| Skenario | Keadaan akhir |
|---|---|
| A | Selesai, bersumber dari laporan: laporan `Diselesaikan`, konseling `selesai`, ada `CounselingLog` dan `ClinicalSummary` yang `summary_data`-nya berupa **string JSON** (`{chief_complaint, assessment, plan, session_count, resolution}`) |
| B | Menunggu persetujuan siswa: laporan `Menunggu Persetujuan Siswa`, konseling **`menunggu`** |
| C | Siswa menolak jadwal: laporan `Jadwal Ditolak Siswa`, konseling `ditolak` |
| D | Insiden NLP: `Sharing` berisiko tinggi + `NlpAnalysis` (`true-positive`, skor 95, zona merah) → `Counseling{source_type: nlp_incident, report_id: null, status: menunggu}` |
| E | Jadwal diajukan ulang setelah ditolak siswa: konseling **`dijadwal_ulang`**, laporan `Menunggu Persetujuan Siswa` |
| F | Dibatalkan Guru BK: konseling **`dibatalkan`**, laporan dan curhat `Dibatalkan` |

> Catatan representasi: `summary_data` di seeder ini adalah **string JSON**, sementara di `ReferralFlowSeeder` berupa **prosa biasa**. Konsumen harus menoleransi keduanya.

### `DemoCaseSeeder` — satu-satunya yang memanggil layanan NLP sungguhan

Tenant demo minimal: `super`, `kepsek`, `admintu`, dua Guru BK dengan 4 siswa masing-masing. Untuk setiap siswa dibuat tiga curhat — "Merasa Hampa" (diharapkan kuning), "Capek Banget… mau mati aja" (diharapkan merah), dan satu kontrol netral — masing-masing didorong lewat `ProcessNlpAnalysisJob::dispatchSync()` sehingga **layanan NLP sungguhan yang mengklasifikasikannya**. Lalu memanggil `PsychologistSeeder`, `PsychologistSlotSeeder`, dan `ReferralFlowSeeder`.

Artinya: seeder ini **butuh layanan NLP hidup** untuk menghasilkan data yang bermakna. Tanpa itu, semua curhat demo berakhir tanpa zona dan tanpa SLA.

### Seeder lainnya

| Seeder | Isi |
|---|---|
| `QuestionnaireSeeder` | Bank soal sungguhan: 7 item `secure`, 10 item `insecure`, lengkap dengan label kategori psikometrik |
| `PsychologistSeeder` | Dua psikolog bernama lengkap dengan nomor STR, membuat baris `users` **dan** `psychologist_profiles` |
| `PsychologistSlotSeeder` | Setiap Senin & Rabu selama sebulan, tiga slot satu jam 08:00–11:00, semua `available` |
| `NlpAnalysisSeeder` | Satu baris per curhat yang ada; memetakan prioritas ke verdict `true-positive`/`false-negative`/dst secara probabilistik |
| `MoodRecordSeeder` | 36 hari × setiap siswa |
| `ReportSeeder` | 10 per siswa |
| `SharingSeeder` | 3 per siswa, berotasi melalui kesepuluh nilai `ReportStatus` |
| `SelfHelpSeeder` | 30 hari per siswa, acak dari empat jenis |
| `QuotesSeeder`, `VideoSeeder`, `ArticleSeeder`, `TagSeeder`, `CloudSeeder` | Data konten dan gamifikasi |
| `CaseTimelineSeeder` | **Dijalankan paling akhir** di `DatabaseSeeder` dan `DemoCaseSeeder`. Membangun jejak kasus (`case_events`) dengan menurunkannya dari timestamp fixture yang sudah ada — lihat [`13-case-timeline.md`](13-case-timeline.md). Idempoten |
| `LocationSeeder` | Memanggil `import:locations` |
| `GeminiApi` | **Dikomentari** di `DatabaseSeeder`. Memecah `GEMINI_API_KEYS` menjadi baris pool rotasi |
| `AiGenerated` | **Dikomentari, dan rusak** — menyisipkan kolom `section` yang tidak ada di migrasi. `[DEAD]` |

Urutan di `DatabaseSeeder` **bergantung satu sama lain**: `NlpAnalysisSeeder` butuh `sharings`, `CounselingFlowSeeder` butuh `reports` + `sharings` + `users`, `ReferralFlowSeeder` butuh `counselings` + `psychologist_slots`. Jangan diacak.

---

## Menelusuri satu alur rujukan secara manual

Cara tercepat memastikan dokumentasi ini masih benar. Jalankan `DemoCaseSeeder`, lalu lewat `/docs`:

1. Login sebagai `siswa1` (password `password`) → simpan token.
2. `GET /api/student/counselings` → cari konseling bertipe `external`.
3. `GET /api/student/counselings/{id}/consent` → baca permintaan persetujuan.
4. `PATCH /api/student/consents/{consentId}` dengan `{"is_granted": true, "scopes": ["mood_history","sharing_history","assesment_logs"]}`.
5. `GET /api/student/referrals/{counselingId}/available-dates` → perhatikan tidak ada tanggal dalam dua hari ke depan.
6. `GET /api/student/referrals/{counselingId}/available-slots?date=...`.
7. `POST /api/student/bookings` dengan `slot_id` → booking `pending`, `deadline_at` 24 jam ke depan.
8. Login sebagai `ermin` → `GET /api/psychologist/referrals/pending`.
9. `PATCH /api/psychologist/referrals/{bookingId}/decide` dengan `{"action":"confirm"}` → booking & slot `confirmed`, job ringkasan AI dijalankan **(butuh worker jalan)**.
10. `GET /api/psychologist/referrals/{counselingId}/summary` → narasi AI + identitas asli siswa.
11. `POST /api/psychologist/referrals/{counselingId}/feedback` → menutup booking, konseling, dan curhat sekaligus.

Kalau langkah 10 mengembalikan ringkasan kosong, periksa `GEMINI_API_KEY` dan tabel `failed_jobs`.

---

## Reset data

| Cara | Kapan |
|---|---|
| `php artisan migrate:fresh-backup` | Default untuk development lokal |
| `php artisan db:seed --class=DemoCaseSeeder` | Menambah tenant demo ke database yang sudah ada |
| **`GET /reset`** | 🛑 Endpoint HTTP yang menjalankan `migrate:fresh --seed --seeder=DemoCaseSeeder`. Hanya dilindungi HTTP Basic dengan kredensial bawaan `novaren` / `secret`. **Siapa pun yang mencapai URL ini menghancurkan seluruh database, termasuk semua catatan klinis.** Kalau instance ini bisa diakses publik, setel `RESET_AUTH_USER` dan `RESET_AUTH_PASSWORD` sekarang |

---

## Deploy

| Aspek | Detail |
|---|---|
| `dev` branch | `.github/workflows/dev.yml` → build → Docker Hub tag `dev` → SSH, `docker compose up` di VPS |
| `main` branch | `prod.yml` → tag `latest` |
| Pemicu | **Keduanya `push`-only.** Tidak ada workflow `pull_request` |
| Runtime | Satu container, FrankenPHP + supervisord menjalankan web, `queue:work`, dan `schedule:work` |
| Health check | `GET /up` |

**Yang tidak otomatis:**

- **Migrasi tidak dijalankan saat deploy.** Harus manual setelahnya.
- Tidak ada yang menjalankan test — memang tidak ada test, dan tidak ada workflow PR untuk menjalankannya.
- Deploy me-restart container, jadi job yang sedang jalan terputus (akan diambil ulang dari tabel `jobs`).

---

## Status testing

**Tidak ada direktori `tests/`.** Dihapus di commit HEAD `266f860` — 4 file Pest feature, `tests/Pest.php`, `tests/TestCase.php`, dan folder `stubs/`, sekitar 1.235 baris. Sementara itu [`phpunit.xml`](../../phpunit.xml) **masih** merujuk `tests/Unit` dan `tests/Feature`, jadi suite-nya rusak karena berkasnya tidak ada.

Pulihkan dengan:

```bash
git checkout 266f860^ -- tests
```

Yang dulu dicakup, dan berguna sebagai spesifikasi perilaku:

| File | Cakupan |
|---|---|
| `PsychologistSummaryTest.php` | Consent memicu job Gemini; `raw_payload` memuat kunci untuk setiap scope yang diizinkan; endpoint ringkasan 403 untuk siswa dan psikolog bukan penanggung jawab, 200 untuk yang ditugaskan; bentuk respons; feedback menolak `rating` di luar `good`/`bad` |
| `PsychologistReferralTest.php` | 497 baris. Daftar pending 403/200; `decide` butuh alasan saat reschedule; 403 lintas psikolog; confirm mengunci slot dan memicu event; reject mengembalikan slot; perintah `referrals:expire-pending`; seluruh matriks filter pada `referrals` dan `referrals-overview` |
| `PsychologistSlotTest.php` | CRUD slot: publikasi, tumpang-tindih, safe delete |
| `PrincipalDashboardTest.php` | Dashboard kepala sekolah + alur read receipt notifikasi |

> **Jangan pernah mengklaim "tests pass".** Tidak ada yang bisa dijalankan.

---

## Dependency utama

[`composer.json`](../../composer.json) — PHP ^8.4, `laravel/framework ^12.0`.

| Paket | Fungsi |
|---|---|
| `tymon/jwt-auth ^2.2` | Autentikasi |
| `dedoc/scramble ^0.12` | Dokumentasi OpenAPI runtime dari PHPDoc + atribut `#[Group]` / `#[ExcludeRouteFromDocs]` |
| `google-gemini-php/laravel ^2.0` | Integrasi Gemini |
| `google/apiclient ^2.18` | YouTube Data API |
| `maatwebsite/excel ^3.1` | Import/export Excel |
| `laravel/sanctum ^4.0` | **Terpasang tapi tidak dipakai** — guard API memakai JWT |
| `pestphp/pest ^4.0` (dev) | Terpasang, tapi tidak ada test untuk dijalankan |
| `laravel/pint` (dev) | Code style — `php vendor/bin/pint` |

**Tidak ada `package.json`.** Skrip `composer dev` menyebut `npm run dev` / vite, tapi tidak ada frontend di repo ini.
