# Perubahan API — Konseling & Rujukan Psikolog

**Tanggal:** 10 Oktober 2026 · **Untuk:** developer frontend (web Next.js & klien mobile)
**Status:** sudah di branch `dev`, belum dirilis ke `main`

---

## Ringkasan

Status konseling dulu tidak pernah bergerak sepanjang alur rujukan. Sebuah rujukan dibuat dengan status `menunggu`, lalu tetap `menunggu` meskipun siswa sudah memberi persetujuan, sudah memilih jadwal, dan psikolog sudah mengkonfirmasi — baru melompat ke `selesai` saat psikolog menulis hasil. Enam langkah di tengah tidak punya representasi apa pun.

Akibatnya layar-layar di desain **tidak mungkin dibangun**. Yang paling jelas: kartu `Konseling Terlewat` dengan badge `Kedaluwarsa` dan tombol **"Pilih Jadwal Baru"**. Payload counseling membuang data booking, jadi frontend tidak punya cara mengetahui jadwalnya sudah kadaluarsa.

Sekarang:

- `CounselingStatus` **4 → 8 nilai**, menutupi seluruh alur
- `BookingStatus` **5 → 6 nilai** (tambah `rescheduled`)
- Payload counseling membawa objek **`latest_booking`** beserta dua field turunan siap pakai
- Dua endpoint baru (`cancel`, `repropose`) dan satu aksi baru (`decide` `action=reject`)
- Beberapa filter dan hitungan yang sebelumnya salah atau tidak terjangkau, kini benar

Referensi lengkap sisi backend: [`docs/foundation/08-state-machines.md`](../foundation/08-state-machines.md) dan [`docs/foundation/09-api-surface.md`](../foundation/09-api-surface.md). Pemetaan ke copy desain: [`docs/foundation/12-ui-contract.md`](../foundation/12-ui-contract.md).

---

## ⚠️ Breaking changes — kerjakan ini dulu

### 1. `GET /api/student/counselings` berubah bentuk

Dulu endpoint ini mengembalikan model mentah. Sekarang melewati `CounselingResource`, sama seperti `GET /api/counseling`.

**Yang berubah:**

| | Dulu | Sekarang |
|---|---|---|
| Objek `counselor` / `psychologist` bersarang | model mentah — memuat `school_id`, `room_id`, `mentor_id`, `counselor_id` | lewat `UserResource` — **keempat field itu dibuang** |
| Field baru | — | `latest_booking`, `slot`, `room` (di-override jadi nama instansi psikolog bila `type = external`) |
| Bentuk paginasi (`?page=` / `?search=`) | bentuk paginator mentah Laravel: `{current_page, data, total, …}` di dalam `data` | `{data, links, meta}` — konsisten dengan `GET /api/psychologist/referrals` |

Kalau Anda membaca `counselor.school_id` atau `counselor.room_id` dari endpoint ini, ambil dari sumber lain. Kalau Anda membaca `data.current_page` / `data.total`, pindah ke `data.meta.current_page` / `data.meta.total`.

Tanpa `?page` dan tanpa `?search`, respons tetap array datar seperti sebelumnya.

### 2. Filter `status=selesai` pada daftar rujukan psikolog

`GET /api/psychologist/referrals?status=selesai` dulu mensyaratkan booking `confirmed` **dan** counseling `selesai` — kombinasi yang tidak pernah dihasilkan alur sungguhan, karena saat psikolog menutup sesi booking menjadi `finished` dan counseling menjadi `selesai` **bersamaan**. Jadi filter ini **selalu kosong**, dan rujukan yang benar-benar selesai tidak muncul di view terfilter mana pun.

Sekarang `status=selesai` memetakan ke booking `finished`. Kalau Anda sebelumnya menambahkan workaround untuk ini, hapus.

### 3. `GET /api/psychologist/referrals-overview` — arti `selesai` berubah

```json
{
  "pending": 2,
  "confirmed": 1,
  "selesai": 1,
  "by_status": {
    "pending": 2, "confirmed": 1, "rescheduled": 1,
    "rejected": 0, "expired": 1, "finished": 1
  },
  "total": 6
}
```

Tiga kunci lama tetap ada supaya tidak merusak kode yang sudah jalan, tapi **`selesai` sekarang benar-benar berisi angka** (dulu praktis selalu 0). Dua kunci baru: `by_status` memuat keenam nilai dengan zero-fill, dan `total`. Jumlah seluruh `by_status` dijamin sama dengan jumlah baris di `GET /api/psychologist/referrals` tanpa filter — dulu `rejected`, `expired`, dan `finished` tidak terhitung di mana pun.

Pakai `by_status` untuk KPI card baru; `pending`/`confirmed`/`selesai` cukup untuk yang sudah ada.

### 4. `PATCH /api/student/counselings/{counseling}/acknowledge` sekarang punya otorisasi

Endpoint ini sebelumnya **tidak memeriksa apa pun** — siapa pun yang terautentikasi bisa menyetujui atau menolak konseling siapa pun. Sekarang:

| Kondisi | Respons |
|---|---|
| Pemanggil bukan siswa pemilik sesi | `403` |
| Status konseling tidak sedang menunggu siswa | `422` dengan pesan *"Jadwal konseling ini tidak sedang menunggu persetujuan Anda."* |

Status yang menunggu siswa: `menunggu`, `menunggu_jadwal`, `dijadwal_ulang`. Jangan tampilkan tombol Setujui/Tolak di luar status itu.

---

## Nilai status baru

### `CounselingStatus` — 8 nilai

| Nilai | Arti | Siapa yang ditunggu |
|---|---|---|
| `menunggu` | Baru dibuat Guru BK | **Siswa** — menyetujui jadwal (internal) atau memberi consent (external) |
| `menunggu_jadwal` | Consent sudah diberikan; siswa harus memilih slot. **Juga status setelah booking kadaluarsa atau ditolak psikolog** | **Siswa** — pilih slot |
| `menunggu_konfirmasi` | Booking sudah diajukan | **Psikolog** |
| `dijadwalkan` | Terkonfirmasi, sesi siap jalan | — |
| `dijadwal_ulang` | Guru BK mengajukan jadwal baru setelah siswa menolak | **Siswa** |
| `selesai` | Hasil sudah dicatat | — |
| `ditolak` | Siswa menolak jadwal, atau menolak consent | — |
| `dibatalkan` | Guru BK membatalkan | — |

Tiga nilai bersifat **terminal** (`selesai`, `ditolak`, `dibatalkan`) — tidak akan berubah lagi, dan backend menolak aksi apa pun terhadapnya dengan `422`.

> `dijadwal_ulang` **hanya muncul di jalur konseling internal.** Di jalur psikolog, reschedule bersifat auto-setuju sehingga counseling langsung ke `dijadwalkan`; jejaknya dibaca dari `latest_booking.was_rescheduled`. Jangan harapkan `dijadwal_ulang` pada rujukan psikolog.

### `BookingStatus` — 6 nilai

| Nilai | Arti |
|---|---|
| `pending` | Diajukan siswa; psikolog punya 24 jam untuk merespons |
| `confirmed` | Diterima psikolog; sesi akan berjalan |
| `rescheduled` | **Baru.** Booking ini digeser psikolog. Booking pengganti dibuat langsung `confirmed` |
| `rejected` | Psikolog menolak rujukannya |
| `expired` | 24 jam lewat tanpa keputusan |
| `finished` | Psikolog sudah mencatat hasil |

**Mengapa `rescheduled` ditambahkan:** sebelumnya jadwal yang hanya digeser ditandai `rejected`, sehingga muncul sebagai "Ditolak" di inbox psikolog. Sekarang `rejected` berarti penolakan sungguhan.

### `SlotStatus` — tidak ada nilai baru, tapi `tentative` sekarang nyata

Ketiga nilainya (`available`, `tentative`, `confirmed`) sudah ada, tapi **`tentative` sebelumnya tidak pernah ditulis kode aplikasi** — hanya seeder. Artinya slot yang sudah diklaim siswa tetap tampil `available`.

Sekarang slot menjadi `tentative` begitu siswa mengajukan booking, dan kembali `available` bila booking kadaluarsa atau ditolak. Layar Kelola Jadwal psikolog kini bisa dipercaya: `Tersedia` / `Menunggu Konfirmasi` / `Terkonfirmasi` mencerminkan keadaan sebenarnya.

---

## `latest_booking` — objek baru di payload counseling

Muncul di `GET /api/counseling`, `GET /api/counseling/{id}`, dan `GET /api/student/counselings`.

```json
{
  "id": 22,
  "type": "external",
  "status": "menunggu_jadwal",
  "room": "Puskesmas Jetis",
  "slot": { "id": 44, "slot_date": "2026-10-13", "slot_start_time": "09:00" },
  "latest_booking": {
    "id": 5,
    "status": "expired",
    "deadline_at": "2026-10-09T21:31:50+07:00",
    "reject_reason": null,
    "location": null,
    "is_expired": true,
    "was_rescheduled": false
  }
}
```

| Field | Catatan |
|---|---|
| `id` | ID booking — dipakai untuk `GET /api/student/bookings/{booking}`. Dulu ID ini hanya diberikan sekali, di respons `POST`, jadi tidak bisa diambil ulang |
| `status` | Salah satu dari 6 nilai `BookingStatus` |
| `deadline_at` | ISO-8601 **zona Asia/Jakarta** (`+07:00`), bukan UTC. Konsisten dengan `StudentBookingController` |
| `reject_reason` | Alasan psikolog menggeser atau menolak. Tampilkan ini di kartu `Perubahan Jadwal` dan `Ditolak` |
| `location` | Lokasi sesi, diisi saat booking dikonfirmasi |
| **`is_expired`** | **Turunan.** Jangan hitung sendiri — lihat di bawah |
| **`was_rescheduled`** | **Turunan.** Sumber badge `Perubahan Jadwal` |

`latest_booking` bernilai `null` kalau rujukan belum punya booking sama sekali (mis. status masih `menunggu` atau `menunggu_jadwal` pertama kali). Kuncinya hanya muncul pada endpoint yang memuat relasinya — ketiga endpoint di atas sudah memuatnya.

### `is_expired` — kenapa jangan dihitung sendiri

```
is_expired = status === 'expired'
          || (status === 'pending' && deadline_at sudah lewat)
```

Cabang kedua penting: command yang menandai booking kadaluarsa jalan **tiap 15 menit**, jadi ada jendela hingga 15 menit di mana booking sudah melewati tenggat tapi `status` masih `pending`. Kalau Anda hanya memeriksa `status === 'expired'`, selama jendela itu UI akan menampilkan countdown yang sudah mati sebagai masih aktif.

Ini juga sejalan dengan cara desainer memperlakukan kolom `BATAS WAKTU` (Aktif / Kadaluarsa) sebagai **dimensi terpisah** dari `STATUS RUJUKAN`, bukan sebagai nilai status.

### `was_rescheduled` — sumber badge "Perubahan Jadwal"

`true` kalau ada booking sebelumnya pada rujukan yang sama berstatus `rescheduled`. Jadi badge `Perubahan Jadwal` muncul saat:

```
status === 'dijadwalkan' && latest_booking.was_rescheduled === true
```

Ini **bukan** nilai status, karena reschedule bersifat auto-setuju — tidak ada state "menunggu persetujuan perubahan".

---

## Pemetaan status → badge

| Kondisi | Badge desain |
|---|---|
| `menunggu` + `type = external` | `Butuh Persetujuan` |
| `menunggu` + `type = internal` | `Menunggu Persetujuan` |
| `menunggu_jadwal` + `latest_booking.is_expired` | **`Kedaluwarsa`** — kartu `Konseling Terlewat`, tombol `Pilih Jadwal Baru` |
| `menunggu_jadwal` | `Pilih Jadwal` |
| `menunggu_konfirmasi` | `Menunggu Konfirmasi` |
| `dijadwalkan` + `latest_booking.was_rescheduled` | `Perubahan Jadwal` |
| `dijadwalkan` | `Terkonfirmasi` / `Dijadwalin` |
| `dijadwal_ulang` | `Dijadwal Ulang` |
| `selesai` | `Selesai` |
| `ditolak` | `Jadwal Ditolak Siswa` |
| `dibatalkan` | `Dibatalkan` |

Perhatikan urutannya: cek `is_expired` **sebelum** `menunggu_jadwal` biasa, dan `was_rescheduled` **sebelum** `dijadwalkan` biasa.

---

## Alur lengkap beserta status yang diharapkan

### Jalur rujukan psikolog (external)

| # | Aksi | Endpoint | `counselings.status` | `latest_booking.status` | Slot |
|---|---|---|---|---|---|
| 1 | Guru BK membuat rujukan | `POST /api/counseling` | `menunggu` | — | — |
| 2 | Siswa memberi consent | `PATCH /api/student/consents/{consent}` `is_granted=true` | `menunggu_jadwal` | — | — |
| 3 | Siswa melihat tanggal & slot | `GET /api/student/referrals/{counseling}/available-dates` · `available-slots` | `menunggu_jadwal` | — | `available` |
| 4 | Siswa mengajukan jadwal | `POST /api/student/bookings` | `menunggu_konfirmasi` | `pending` (tenggat +24 jam) | `tentative` |
| 5a | Psikolog konfirmasi | `PATCH /api/psychologist/referrals/{booking}/decide` `action=confirm` | `dijadwalkan` | `confirmed` | `confirmed` |
| 5b | Psikolog geser jadwal | sama, `action=reschedule` | `dijadwalkan` | booking lama `rescheduled`, **baru** `confirmed` | lama `available`, baru `confirmed` |
| 5c | Psikolog menolak | sama, `action=reject` | `menunggu_jadwal` | `rejected` | `available` |
| 5d | Tenggat 24 jam lewat | otomatis, tiap 15 menit | `menunggu_jadwal` | `expired` | `available` |
| 6 | Psikolog mencatat hasil | `POST /api/psychologist/referrals/{counseling}/feedback` | `selesai` | `finished` | `confirmed` |

**Setelah 5c atau 5d, rujukannya tetap hidup.** Siswa kembali ke langkah 3 dan memilih slot baru pada rujukan yang sama — ini yang dimaksud tombol `Pilih Jadwal Baru` di desain. Bukan mengajukan rujukan baru dari nol.

Kalau siswa menolak consent di langkah 2, counseling menjadi `ditolak` dan alurnya berhenti.

### Jalur konseling internal (Guru BK sendiri)

| # | Aksi | Endpoint | `counselings.status` |
|---|---|---|---|
| 1 | Guru BK mengajukan jadwal | `POST /api/counseling` (tanpa `psychologist_id`) | `menunggu` |
| 2a | Siswa menyetujui | `PATCH /api/student/counselings/{counseling}/acknowledge` `type=accept` | `dijadwalkan` |
| 2b | Siswa menolak | sama, `type=decline` | `ditolak` |
| 3 | Guru BK mengajukan ulang | `PATCH /api/counseling/{counseling}/repropose` | `dijadwal_ulang` |
| 4 | Siswa menyetujui | `acknowledge` `type=accept` | `dijadwalkan` |
| 5 | Guru BK mencatat hasil | `POST /api/counseling-logs` | `selesai` |
| — | Guru BK membatalkan | `PATCH /api/counseling/{counseling}/cancel` | `dibatalkan` |

**Jalur internal tidak memakai digital consent akses data.** Siswa hanya menyetujui **jadwal**. Screen `Review Persetujuan` dengan tiga checkbox scope hanya ada di jalur rujukan psikolog. Satu-satunya centang di jalur internal adalah pernyataan Guru BK bahwa orang tua sudah dihubungi — itu diisi Guru BK, bukan siswa, dan belum ada field-nya di backend.

---

## Endpoint baru

### `PATCH /api/counseling/{counseling}/cancel`

Guru BK membatalkan sesi. Aksi `Batalkan Jadwal` di desain.

```json
{ "notes": "Sudah selesai ditangani wali kelas." }
```

- Otorisasi: hanya Guru BK yang **ditugaskan** pada sesi itu (`403` kalau bukan)
- `422` kalau status sudah terminal
- Efek: counseling → `dibatalkan`, curhat tertaut → `Dibatalkan`
- `notes` opsional; kalau dikosongkan, catatan lama dipertahankan

### `PATCH /api/counseling/{counseling}/repropose`

Guru BK mengajukan jadwal baru setelah siswa menolak. Tombol `Ajukan Jadwal Konseling Kembali` di desain.

```json
{ "date": "2026-10-15", "time": "10:00", "room": "Ruang BK 2", "notes": "..." }
```

- `date` wajib, format `Y-m-d`. `time` wajib, format `H:i`. `room` dan `notes` opsional
- Otorisasi: Guru BK yang ditugaskan (`403`)
- **`422` kalau status bukan `ditolak`** — endpoint ini hanya untuk memulihkan jadwal yang ditolak siswa
- Efek: counseling → `dijadwal_ulang`, `scheduled_at` diperbarui, curhat → `Menunggu Persetujuan Siswa`

### `action=reject` pada `decide`

`PATCH /api/psychologist/referrals/{booking}/decide` sekarang menerima tiga aksi:

| `action` | Field wajib | Efek |
|---|---|---|
| `confirm` | — | Booking + slot `confirmed`, counseling `dijadwalkan`, ringkasan AI dibuat |
| `reschedule` | `reschedule_reason`, `slot_id` | Booking lama `rescheduled`, booking baru langsung `confirmed`, counseling `dijadwalkan`, ringkasan AI dibuat |
| **`reject`** | `reschedule_reason` | Booking `rejected`, slot dilepas, counseling kembali ke `menunggu_jadwal` |

Pesan sukses berbeda per aksi: *"Rujukan berhasil dikonfirmasi."*, *"Rujukan telah dijadwalkan ulang."*, *"Rujukan telah ditolak."*

Guard yang akan mengembalikan `422`:

| Aksi | Syarat status booking |
|---|---|
| `confirm` | harus `pending` |
| `reschedule` | `pending` atau `confirmed` |
| `reject` | harus `pending` |

> Catatan: `reschedule` kini **juga** memicu pembuatan ringkasan klinis AI. Sebelumnya hanya `confirm` yang memicunya, sehingga rujukan yang digeser tidak pernah punya ringkasan dan layar Laporan AI selalu kosong.

---

## Perubahan lain yang perlu Anda ketahui

### `POST /api/student/bookings` bisa mengembalikan `409`

Satu rujukan hanya boleh punya satu booking hidup. Kalau rujukan sudah punya booking berstatus `pending`, `confirmed`, atau `finished`:

```
409 Conflict — "Rujukan ini sudah memiliki jadwal yang masih aktif."
```

Booking yang `expired`, `rejected`, atau `rescheduled` **tidak** memblokir — itulah yang memungkinkan "Pilih Jadwal Baru" bekerja. Tangani `409` sebagai "refresh dulu, state Anda sudah usang", bukan sebagai error validasi.

### `GET .../available-slots` sekarang konsisten dengan `available-dates`

Dua perbaikan:

1. Dulu endpoint ini mengecualikan **setiap** slot yang pernah dibooking rujukan ini — termasuk yang booking-nya sudah kadaluarsa. Jadi setelah kadaluarsa, siswa tidak bisa memilih slot yang sama lagi. Sekarang hanya slot dengan booking yang masih hidup yang dikecualikan.
2. Dulu endpoint ini tidak punya batas tanggal, sementara `available-dates` memakai batas H+2. Akibatnya `available_slots_count` dari endpoint tanggal bisa berbeda dari jumlah `time_slots` yang benar-benar dikembalikan. Sekarang keduanya memakai batas yang sama.

Kalau Anda punya workaround untuk ketidakcocokan jumlah slot, hapus.

### `deadline_at` pada booking yang langsung `confirmed`

Booking pengganti dari reschedule dulu diberi `deadline_at = now() - 2 hari`, sehingga booking yang baru saja dibuat langsung tampil di bawah filter `batas_waktu=kadaluarsa`. Sekarang `deadline_at` untuk booking `confirmed` diisi **waktu sesi** (dari slotnya), yang bermakna dan tidak lagi salah terbaca.

`deadline_at` hanya berarti "batas waktu psikolog merespons" untuk booking `pending`.

### `BookingScheduleResource`

- **Tambah** `is_expired` — aturan turunan yang sama seperti di `latest_booking`
- **Hapus** `deleted_at` — sebelumnya ikut terkirim tanpa alasan

Dipakai oleh `GET /api/psychologist/referrals`, `referrals/pending`, dan respons `decide`.

### Filter pada `GET /api/psychologist/referrals`

| Hal | Dulu | Sekarang |
|---|---|---|
| Kapitalisasi | `?status=Menunggu Konfirmasi` → `422` | diterima — input dinormalkan sebelum validasi |
| Nilai baru | — | `dijadwal ulang` (alias: `dijadwal_ulang`, `rescheduled`) |
| `selesai` | selalu kosong | booking `finished` |
| `terkonfirmasi` | `confirmed` + counseling bukan `selesai` | `confirmed` saja — pemeriksaan kedua sudah tidak perlu |
| Nilai tak dikenal | `422` dari validasi | tetap `422`; kalau lolos, hasilnya kosong, bukan seluruh daftar |

Alias Inggris (`pending`, `confirmed`, `finished`, `rejected`, `rescheduled`, `expired`) sekarang benar-benar diterima — dulu ditangani controller tapi ditolak validasi lebih dulu, jadi tidak pernah bisa dipakai.

Filter `batas_waktu` (`aktif` / `kadaluarsa`) dan `priority` (`kritis` / `prioritas`) tidak berubah.

### Akses data klinis psikolog

`GET /api/psychologist/referrals/{counseling}/summary`, `POST .../feedback`, dan ketiga endpoint `recap/.../monthly/*` dulu memakai `status = confirmed` sebagai gerbang otorisasi, sehingga **`403` setelah sesi ditutup** (karena booking menjadi `finished`). Sekarang `confirmed` dan `finished` keduanya diizinkan — psikolog tetap bisa membuka laporan sesi yang sudah selesai.

Sebaliknya, endpoint summary dan feedback dulu menerima booking berstatus **apa pun**, termasuk `expired` dan `rejected` — artinya psikolog bisa mengirim feedback pada rujukan yang sudah mati dan melompatkan counseling ke `selesai`. Sekarang ditolak `403`.

---

## Yang TIDAK berubah

- `ConsentStatus` (`pending` / `granted` / `rejected`) dan ketiga scope (`mood_history`, `sharing_history`, `assesment_logs` — perhatikan ejaannya, satu `s`). **Pencabutan consent masih belum ada** di backend
- `ReportStatus` dan seluruh alur curhat/sharing
- `Priority` tetap tiga nilai (`tinggi`/`sedang`/`rendah`); pemetaan ke `kritis`/`prioritas` tetap di sisi backend
- Autentikasi, login, dan claim JWT
- Endpoint mood, kuesioner, self-help, Cirrus, dan konten
- **Masih belum ada notifikasi apa pun.** Booking kadaluarsa, dikonfirmasi, atau digeser tidak mengirim notifikasi ke siapa pun — frontend harus polling. Ini pekerjaan terpisah

---

## Checklist implementasi

- [ ] Pindahkan pembacaan `data.current_page` / `data.total` ke `data.meta.*` pada `GET /api/student/counselings?page=`
- [ ] Berhenti membaca `counselor.school_id` / `room_id` / `mentor_id` / `counselor_id` dari endpoint itu
- [ ] Perluas penanganan status konseling dari 4 ke 8 nilai — nilai tak dikenal jangan sampai membuat kartu kosong
- [ ] Tambah `rescheduled` ke penanganan status booking
- [ ] Pakai `latest_booking.is_expired`, **jangan** hitung dari `deadline_at` sendiri
- [ ] Pakai `latest_booking.was_rescheduled` untuk badge `Perubahan Jadwal`
- [ ] Bangun kartu `Konseling Terlewat` + tombol `Pilih Jadwal Baru` → arahkan kembali ke flow pemilihan slot pada rujukan yang sama
- [ ] Tangani `409` pada `POST /api/student/bookings` sebagai "state usang, refresh"
- [ ] Sembunyikan tombol Setujui/Tolak jadwal di luar status `menunggu`, `menunggu_jadwal`, `dijadwal_ulang`
- [ ] Tambah tombol `Batalkan Jadwal` (Guru BK) → `PATCH /api/counseling/{id}/cancel`
- [ ] Tambah tombol `Ajukan Jadwal Konseling Kembali` (Guru BK, hanya saat status `ditolak`) → `.../repropose`
- [ ] Tambah aksi tolak di inbox psikolog → `decide` `action=reject`
- [ ] Hapus workaround filter `status=selesai` kalau ada
- [ ] Pakai `by_status` untuk KPI card rujukan psikolog

---

## Mencoba sendiri

Backend dev jalan di `http://127.0.0.1:8000`, dokumentasi API interaktif di `http://127.0.0.1:8000/docs`.

Akun demo dengan username **stabil** (password semuanya `password`): `siswa1active` dan `siswa2active` (siswa), `gurubk` (Guru BK), `ermin` dan `yulia` (psikolog), `kepsek`/`headteacher`, `admintu`, `super`. Daftar lengkap dan skenario data demo ada di [`docs/foundation/11-environment-and-runbook.md`](../foundation/11-environment-and-runbook.md).

Username delapan siswa lainnya dibuat acak oleh faker, jadi berubah setiap kali database di-seed ulang. Untuk menemukan siswa yang rujukannya **kadaluarsa** — yang Anda butuhkan untuk menguji kartu `Konseling Terlewat` — minta backend menjalankan:

```bash
php artisan tinker --execute="echo App\Models\Counseling::where('status','menunggu_jadwal')->with('student')->first()?->student?->username;"
```

Data demo sudah memuat kedelapan status konseling dan lima dari enam status booking. `rejected` belum ada di data demo — hasilkan sendiri lewat `decide` `action=reject`.

Kalau ada yang tidak cocok antara dokumen ini dan perilaku API, kemungkinan besar dokumen ini yang salah — tolong kabari.
