# User Stories

<!-- verified: branch=dev commit=266f860 date=2026-10-09 scope=routes/api.php,app/Http/Controllers,app/Policies,docs/tasks -->
> **Terverifikasi terhadap:** `dev` @ `266f860` · 2026-10-09
> **Sumber:** [`routes/api.php`](../../routes/api.php) · [`app/Policies/`](../../app/Policies/) · referensi tiket di [`docs/tasks/`](../tasks/)

Story ditulis dari perilaku yang **benar-benar ada di kode**, bukan dari spesifikasi. ID-nya stabil — pakai untuk menautkan pekerjaan, PR, dan test. Kriteria penerimaan ditulis sebagai hal yang bisa diamati, bukan sebagai implementasi.

Label status mengikuti [`README.md`](README.md): tanpa label berarti `[IMPLEMENTED]`. Story yang hanya ada di desain atau spesifikasi dikumpulkan di bagian terakhir.

---

## Siswa — `US-SISWA-nn`

| ID | Story | Kriteria penerimaan | Endpoint |
|---|---|---|---|
| `US-SISWA-01` | Sebagai siswa, saya ingin mengganti password dan username saat pertama masuk, supaya akun saya tidak lagi memakai password bawaan. | Hanya bisa dilakukan sekali. Password minimal 8 karakter dengan huruf kecil, huruf besar, dan angka. Username dan nomor telepon harus unik. Setelah berhasil, `verified` menjadi `true` dan percobaan kedua ditolak dengan pesan "Kamu sudah merubah profile". | `PATCH /api/profile` |
| `US-SISWA-02` | Sebagai siswa, saya ingin mencatat perasaan saya hari ini, supaya saya terbantu mengenali pola emosi saya. | Satu entri per hari, ditolak kalau sudah mencatat. Respons memuat label "Aman" atau "Tidak Aman" dan pesan dukungan. | `POST /api/mood_record` |
| `US-SISWA-03` | Sebagai siswa, saya ingin melihat streak dan rekap mood saya, supaya saya melihat konsistensi saya. | Streak dan rekap bulanan tersedia. Rekap hanya bisa diakses siswa. | `GET /api/mood_record/streaks` · `/recap/{month}` |
| `US-SISWA-04` | Sebagai siswa, saya ingin mengisi angket dan mengetahui profil diri saya, supaya saya lebih memahami kekuatan saya. | Bank soal dipilih otomatis dari mood hari ini — 7 soal kalau Aman, 10 soal kalau Tidak Aman. Hasilnya berupa arketipe utama dan pendamping. **Jawaban tidak disimpan**, jadi hasil tidak bisa dilihat ulang sebagai riwayat. | `GET /api/questionnaire` · `POST /api/questionnaire/{type}` |
| `US-SISWA-05` | Sebagai siswa, saya ingin mendapat misi mingguan, supaya saya punya langkah konkret. | Dua misi dihasilkan pada jalur `insecure`. Hasil yang sama untuk kombinasi jawaban yang sama, karena di-cache per kunci jawaban. | `POST /api/questionnaire/insecure` |
| `US-SISWA-06` | Sebagai siswa, saya ingin membaca kutipan yang sesuai perasaan saya hari ini. | Butuh mood hari ini sudah dicatat; tanpa itu ditolak. Kutipan `secure` untuk mood Aman, `insecure` untuk Tidak Aman. | `GET /api/quote/mood` · `/daily` |
| `US-SISWA-07` | Sebagai siswa, saya ingin bercerita tentang masalah saya, supaya Guru BK tahu dan bisa membantu. | Curhat tersimpan dengan judul dan isi. Respons **selalu** memuat nomor Guru BK saya dan kontak darurat sekolah. Teks dianalisis untuk menentukan prioritas dan tenggat tindak lanjut. **Gagal kalau saya belum punya Guru BK yang ditugaskan.** | `POST /api/sharing` |
| `US-SISWA-08` | Sebagai siswa, saya ingin tahu apakah curhat saya sudah ditanggapi. | Dua curhat terbaru beserta statusnya. | `GET /api/notification/latest-sharing` |
| `US-SISWA-09` | Sebagai siswa, saya ingin meminta bertemu Guru BK ketika saya merasa tidak baik. | **Hanya bisa kalau mood terakhir saya `sedih` atau `marah`.** Permintaan tersimpan dengan topik, tanggal, dan jam. Semua curhat saya hari itu otomatis naik ke prioritas tinggi. | `POST /api/report` |
| `US-SISWA-10` | Sebagai siswa, saya ingin menulis jurnal harian dan jurnal rasa syukur, supaya saya bisa merefleksikan hari saya. | Empat jenis latihan tersedia, masing-masing dengan bentuk isian sendiri. Tersimpan dan bisa dibaca kembali oleh Guru Wali saya. | `POST /api/self-help/daily-journaling` dan tiga lainnya |
| `US-SISWA-11` | Sebagai siswa, saya ingin melakukan teknik grounding ketika cemas. | Pola 5-4-3-2-1 tersimpan sebagai isian terstruktur. | `POST /api/self-help/grounding-technique` |
| `US-SISWA-12` | Sebagai siswa, saya ingin merawat Cirrus, supaya saya punya alasan menyenangkan untuk kembali setiap hari. | Bisa mengklaim hadiah harian (streak sampai 7 hari) dan membeli makanan dengan Tetesan Air. Level naik otomatis saat XP mencapai 100. | `GET /api/cirrus` · `POST /api/claim` · `POST /api/buy` |
| `US-SISWA-13` | Sebagai siswa, saya ingin melihat jadwal konseling dan status curhat saya di satu tempat. | Widget beranda memuat konseling aktif, konseling selesai, dan persetujuan yang menunggu. | `GET /api/student/dashboard/widgets` · `/api/student/counselings` |
| `US-SISWA-14` | Sebagai siswa, saya ingin menyetujui atau menolak jadwal konseling yang diajukan Guru BK. | `accept` menjadikan konseling terjadwal; `decline` menolaknya. Status curhat tertaut ikut berubah. ⚠️ **Saat ini endpoint ini tidak memeriksa siapa yang memanggil** — lihat [`03-authorization.md`](03-authorization.md). | `PATCH /api/student/counselings/{counseling}/acknowledge` |
| `US-SISWA-15` | Sebagai siswa, saya ingin memilih sendiri data apa yang dibagikan ke psikolog, supaya saya tetap mengendalikan cerita saya. | Tiga pilihan: riwayat mood, kutipan curhat, catatan asesmen BK. **Minimal satu wajib dipilih** kalau menyetujui. Menolak akan mengosongkan seluruh pilihan. Hanya saya yang bisa menjawabnya. | `GET /api/student/counselings/{counseling}/consent` · `PATCH /api/student/consents/{consent}` |
| `US-SISWA-16` | Sebagai siswa, saya ingin memilih jadwal konsultasi dengan psikolog dari slot yang tersedia. | Hanya slot mulai dua hari ke depan yang ditawarkan, dan slot yang sudah diklaim orang lain tidak muncul. Setelah diajukan, psikolog punya 24 jam untuk merespons. | `GET .../available-dates` · `.../available-slots` · `POST /api/student/bookings` |
| `US-SISWA-17` | Sebagai siswa, saya ingin tahu status pengajuan jadwal saya. | Status booking bisa dibaca. ⚠️ Tidak ada notifikasi apa pun — kalau booking saya kadaluarsa, saya baru tahu saat membuka aplikasi. | `GET /api/student/bookings/{booking}` |
| `US-SISWA-18` | Sebagai siswa, saya ingin membaca video dan artikel edukasi dari sekolah saya. | Hanya konten sekolah saya. Video berupa embed YouTube, artikel berupa konten kaya. | `GET /api/content` · `/api/video` · `/api/articles` |

---

## Guru BK — `US-BK-nn`

| ID | Story | Kriteria penerimaan | Endpoint |
|---|---|---|---|
| `US-BK-01` | Sebagai Guru BK, saya ingin melihat ringkasan hari ini saat membuka aplikasi. | Jumlah konselee, laporan hari ini, pertemuan hari ini, curhat hari ini. | `GET /api/dashboard/counselor` |
| `US-BK-02` | Sebagai Guru BK, saya ingin melihat daftar curhatan konselee saya, diurutkan berdasarkan urgensi. | Bisa difilter per kelas, status, dan prioritas. Hanya memuat konselee saya. | `GET /api/sharing` |
| `US-BK-03` | Sebagai Guru BK, saya ingin melihat hasil analisis risiko sebuah curhat, supaya saya bisa memutuskan dengan cepat. | Detail memuat skor total, zona (Merah/Kuning/Tanpa Pemicu), dan kata kunci yang terdeteksi beserta bobotnya. | `GET /api/sharing/{sharing}` |
| `US-BK-04` | Sebagai Guru BK, saya ingin menandai bahwa saya sudah mulai menangani sebuah kasus. | Status menjadi "Sedang Ditangani" dan waktu penanganan dicatat. Ini yang menghentikan hitungan pelanggaran SLA di dashboard Kepala Sekolah. | `PATCH /api/sharing/ack/{sharing}` |
| `US-BK-05` | Sebagai Guru BK, saya ingin menandai alert yang keliru, supaya antrean saya bersih dan model NLP belajar dari koreksi saya. | **Hanya bisa selama status masih "Belum Ditinjau".** Prioritas turun ke rendah, status menjadi "Bukan Urgent", tenggat dimatikan, dan alasan saya disimpan sebagai umpan balik pelatihan model. | `PATCH /api/sharing/false-positive/{sharing}` |
| `US-BK-06` | Sebagai Guru BK, saya ingin mencatat keputusan tindak lanjut atas sebuah curhat. | Tiga pilihan: konseling mandiri (kasus berlanjut), penanganan medis, atau lainnya (keduanya menutup kasus). | `PATCH /api/sharing/acknowledge/{sharing}` |
| `US-BK-07` | Sebagai Guru BK, saya ingin membalas curhat siswa. | **Satu balasan per curhat.** Percobaan kedua ditolak. Nama saya dan tanggal balasan dicatat. Status menjadi "Sudah Ditanggapi". | `PATCH /api/sharing/reply/{sharing}` |
| `US-BK-08` | Sebagai Guru BK, saya ingin mengajukan pertemuan konseling dari sebuah laporan siswa. | Konseling dibuat berstatus menunggu persetujuan siswa, dengan tanggal, jam, ruang, dan catatan. Curhat tertaut berubah menjadi "Menunggu Persetujuan Siswa". | `POST /api/report/{report}/schedule-meeting` |
| `US-BK-09` | Sebagai Guru BK, saya ingin membuat sesi konseling internal maupun rujukan eksternal. | Tanpa `psychologist_id` menghasilkan konseling internal dan butuh tanggal + jam. Dengan `psychologist_id` menghasilkan rujukan dan butuh alasan; permintaan persetujuan data otomatis dibuat untuk siswa. | `POST /api/counseling` |
| `US-BK-10` | Sebagai Guru BK, saya ingin mencatat hasil konseling secara aman, supaya ada rekam jejak profesional. | Catatan klinis **terenkripsi** di penyimpanan. Resolusi dan metode sesi dicatat. Konseling ditandai selesai dan laporan tertaut ditutup. **Hanya Guru BK yang ditugaskan pada sesi itu yang boleh menulis.** | `POST /api/counseling-logs` |
| `US-BK-11` | Sebagai Guru BK, saya ingin setiap perubahan catatan klinis terekam, supaya catatan bisa dipertanggungjawabkan. | Setiap kali catatan diubah, nilai sebelumnya tersimpan beserta siapa yang mengubah dan kapan. Riwayat ini **tidak bisa dihapus**. Referensi: `AND-6`. | otomatis lewat observer |
| `US-BK-12` | Sebagai Guru BK, saya ingin melihat pola mood konselee saya, supaya saya punya konteks sebelum sesi. | Pola mingguan dan bulanan, hanya untuk konselee saya. | `GET /api/mood-record/pattern/{user}/{type}` |
| `US-BK-13` | Sebagai Guru BK, saya ingin mengunduh data mood sebagai Excel, untuk laporan internal sekolah. | Tiga variasi: hari ini, mingguan per siswa, bulanan per siswa. | `GET /api/mood_record/export/...` |
| `US-BK-14` | Sebagai Guru BK, saya ingin melihat grafik laporan dan jumlah curhat, supaya saya tahu beban kerja saya. | Tersedia untuk Guru BK, superadmin, dan kepala sekolah. | `GET /api/dashboard/report-graph` · `sharing-count` |

**Yang bukan kewenangan Guru BK:** membaca jurnal self-help siswa (hanya Guru Wali), melihat tren mood tingkat sekolah (hanya superadmin), mengelola akun atau konten.

---

## Psikolog Mitra — `US-PSI-nn`

| ID | Story | Kriteria penerimaan | Endpoint |
|---|---|---|---|
| `US-PSI-01` | Sebagai psikolog mitra, saya ingin mempublikasikan jadwal ketersediaan saya, supaya siswa bisa memilih waktu. | Tanggal tidak boleh di masa lalu. Slot yang bertumpukan ditolak. Bisa diulang mingguan sampai satu tahun. Referensi: `AND-13`. | `POST /api/psychologist/slots` |
| `US-PSI-02` | Sebagai psikolog mitra, saya ingin menghapus slot yang belum dipakai. | Slot milik saya sendiri, dan **ditolak kalau sudah ada pengajuan aktif**. | `DELETE /api/psychologist/slots/{slot}` |
| `US-PSI-03` | Sebagai psikolog mitra, saya ingin melihat ringkasan rujukan masuk, supaya saya tahu apa yang perlu segera direspons. | Jumlah menunggu konfirmasi, terkonfirmasi, dan selesai. | `GET /api/psychologist/referrals-overview` |
| `US-PSI-04` | Sebagai psikolog mitra, saya ingin menelusuri rujukan dengan filter. | Pencarian teks, filter status, filter prioritas (`kritis` / `prioritas`), dan filter batas waktu (`aktif` / `kadaluarsa`), dengan paginasi. Referensi: `AND-10`. | `GET /api/psychologist/referrals` |
| `US-PSI-05` | Sebagai psikolog mitra, saya ingin menerima atau mengusulkan jadwal lain atas rujukan masuk. | Hanya untuk slot milik saya dan hanya selama booking masih menunggu. Menerima akan mengunci slot dan memicu pembuatan ringkasan klinis. Mengusulkan jadwal lain butuh alasan dan slot pengganti. Referensi: `AND-9`, `AND-2`. | `PATCH /api/psychologist/referrals/{booking}/decide` |
| `US-PSI-06` | Sebagai psikolog mitra, saya ingin membaca ringkasan kondisi siswa sebelum sesi, supaya saya tidak memulai dari nol. | **Hanya bisa kalau siswa sudah memberi persetujuan.** Ringkasan berupa satu paragraf naratif faktual, tanpa diagnosis dan tanpa rekomendasi terapi, diakhiri disclaimer. Identitas asli siswa terbuka di sini. Referensi: `AND-10`. | `GET /api/psychologist/referrals/{counseling}/summary` |
| `US-PSI-07` | Sebagai psikolog mitra, saya ingin melihat rekap 30 hari sesuai data yang diizinkan siswa. | Tiga endpoint terpisah, masing-masing ditolak 403 kalau scope-nya tidak diberikan: mood butuh `mood_history`, curhat butuh `sharing_history`, asesmen BK butuh `assesment_logs`. | `GET /api/psychologist/recap/{counseling}/monthly/...` |
| `US-PSI-08` | Sebagai psikolog mitra, saya ingin menutup rujukan dengan catatan klinis saya dan menilai kualitas ringkasan AI. | Rujukan ditandai selesai di keempat record terkait. Rating `good` atau `bad` beserta masukan perbaikan tersimpan — penilaian ini **terhadap ringkasan AI**, bukan terhadap siswa. | `POST /api/psychologist/referrals/{counseling}/feedback` |

---

## Kepala Sekolah — `US-KEPSEK-nn`

| ID | Story | Kriteria penerimaan | Endpoint |
|---|---|---|---|
| `US-KEPSEK-01` | Sebagai kepala sekolah, saya ingin melihat gambaran sekolah saya. | Jumlah siswa, guru, Guru BK, dan kelas di sekolah saya. | `GET /api/dashboard/headteacher` |
| `US-KEPSEK-02` | Sebagai kepala sekolah, saya ingin melihat statistik penanganan kasus. | Total kasus aktif, intervensi selesai, rujukan psikolog, dan jumlah pelanggaran SLA. Referensi: `AND-12`. | `GET /api/headteacher/dashboard/stats` |
| `US-KEPSEK-03` | Sebagai kepala sekolah, saya ingin memantau insiden **tanpa membaca isi curhat siswa**, supaya privasi siswa terjaga. | Daftar insiden hanya memuat metadata — judul, isi, dan balasan **tidak pernah dikirim**. Memuat penanda `is_sla_breached` untuk insiden yang belum ditangani ≥ 2 jam. Referensi: `AND-12`, `BE-12.2`. | `GET /api/headteacher/incidents` |
| `US-KEPSEK-04` | Sebagai kepala sekolah, saya ingin menyaring insiden yang melewati batas waktu, supaya saya bisa menindak keterlambatan. | Filter nama siswa, status, pelanggaran batas waktu, dan nama Guru BK penanggung jawab, dengan paginasi. Referensi: `BE-12.3`. | query pada endpoint yang sama |
| `US-KEPSEK-05` | Sebagai kepala sekolah, saya ingin menandai notifikasi sudah saya baca. | Notifikasi ditandai terbaca. ⚠️ **`[PARTIAL]`** Notifikasi Red Zone tidak pernah dikirim karena job pengirimnya tidak dipanggil siapa pun — jadi endpoint ini bekerja pada data yang praktis tidak pernah ada. Referensi: `BE-12.1`. | `PATCH /api/headteacher/notifications/{id}/read` |

---

## Guru Wali — `US-GURU-nn`

| ID | Story | Kriteria penerimaan | Endpoint |
|---|---|---|---|
| `US-GURU-01` | Sebagai guru wali, saya ingin melihat kondisi emosi kelas saya hari ini. | Jumlah siswa yang saya walikan, dan pembagian mood Aman / Tidak Aman hari ini. | `GET /api/dashboard/teacher` |
| `US-GURU-02` | Sebagai guru wali, saya ingin melihat daftar siswa yang saya walikan. | Hanya siswa dengan `mentor_id` saya. | `GET /api/dashboard/student` |
| `US-GURU-03` | Sebagai guru wali, saya ingin melihat pola mood siswa saya, supaya saya bisa bertanya di waktu yang tepat. | Pola mingguan atau bulanan, hanya untuk siswa yang saya walikan. | `GET /api/mood-record/pattern/{user}/{type}` |
| `US-GURU-04` | Sebagai guru wali, saya ingin membaca riwayat latihan self-help siswa saya. | Hanya siswa yang saya walikan. **Hanya guru wali yang punya akses ini — Guru BK tidak.** | `GET /api/self-help/{type}/{user}` |
| `US-GURU-05` | Sebagai guru wali, saya ingin mengunduh data mood sebagai Excel. | Tersedia untuk guru wali dan Guru BK. | `GET /api/mood_record/export/...` |

---

## Admin TU — `US-ADMIN-nn`

| ID | Story | Kriteria penerimaan | Endpoint |
|---|---|---|---|
| `US-ADMIN-01` | Sebagai admin TU, saya ingin membuat kelas, supaya siswa bisa dikelompokkan. | Nama, jenjang (X/XI/XII), dan kode 8 karakter yang unik. Kode inilah yang dipakai saat membuat siswa. | `POST /api/room` |
| `US-ADMIN-02` | Sebagai admin TU, saya ingin membuat akun guru wali, Guru BK, dan kepala sekolah. | NIP 18 digit dan unik. Peran dibatasi tiga itu — **saya tidak bisa membuat admin atau superadmin**. | `POST /api/dashboard/users` |
| `US-ADMIN-03` | Sebagai admin TU, saya ingin membuat akun siswa dengan wali dan Guru BK-nya. | NISN 10 digit. Wali dan Guru BK diisi dengan **NIP** mereka, kelas diisi dengan **kode kelas** — ketiganya harus sudah ada. Siswa memakai password bawaan sampai login pertama. | `POST /api/user/student` |
| `US-ADMIN-04` | Sebagai admin TU, saya ingin mengimpor banyak siswa sekaligus dari Excel, supaya tidak perlu memasukkan satu per satu. | Template bisa diunduh. Impor tersedia dalam mode langsung dan latar belakang. ⚠️ Endpoint template saat ini **bisa diakses tanpa login**. | `GET /api/user/bulk/template` · `POST /api/user/bulk` |
| `US-ADMIN-05` | Sebagai admin TU, saya ingin mengubah atau menghapus akun di sekolah saya. | Hanya akun di sekolah saya, dan **bukan** akun superadmin atau admin lain. Penghapusan bersifat soft delete. | `PATCH /api/edit-user/{user}` · `DELETE /api/user/{user}` |
| `US-ADMIN-06` | Sebagai admin TU, saya ingin menambahkan video edukasi dari YouTube. | Saya hanya memasukkan id video; judul, durasi, channel, dan thumbnail diambil otomatis. Hanya untuk sekolah saya. | `POST /api/video` |
| `US-ADMIN-07` | Sebagai admin TU, saya ingin menulis dan menyunting artikel edukasi. | Konten berupa teks kaya beserta thumbnail. Slug unik. Penyuntingan memakai `POST` karena ada unggahan berkas. | `POST /api/articles` · `POST /api/article-update/{article}` |
| `US-ADMIN-08` | Sebagai admin TU, saya ingin mengelola kutipan motivasi sesuai mood. | Tiga jenis: `secure`, `insecure`, `daily`. **Hanya bisa dibuat dan dihapus — tidak ada endpoint ubah.** | `POST /api/quote` · `DELETE /api/quote/{quote}` |
| `US-ADMIN-09` | Sebagai admin TU, saya ingin melihat statistik akun dan konten sekolah saya. | Jumlah pengguna per peran, jumlah konten, dan konten yang ditambahkan hari ini. | `GET /api/dashboard/admin` · `content-statistics` |

---

## Superadmin — `US-SUPER-nn`

| ID | Story | Kriteria penerimaan | Endpoint |
|---|---|---|---|
| `US-SUPER-01` | Sebagai superadmin, saya ingin mendaftarkan sekolah baru. | Nama, telepon, dan email unik. Kontak darurat tersimpan sebagai daftar. **Menghapus sekolah ikut menghapus seluruh kelas dan penggunanya di level basis data.** | `POST /api/school` |
| `US-SUPER-02` | Sebagai superadmin, saya ingin membuat akun admin TU untuk sebuah sekolah. | Identifier 16–25 digit, `school_id` wajib. | `POST /api/user/admin` |
| `US-SUPER-03` | Sebagai superadmin, saya ingin mengelola akun psikolog mitra. | Email menjadi username, nomor STR menjadi identifier dan harus unik. Membuat dua baris data sekaligus: akun dan profil profesional. Referensi: `AND-7`. | `GET` · `POST` · `PATCH` · `DELETE /api/admin/psychologists/...` |
| `US-SUPER-04` | Sebagai superadmin, saya ingin menonaktifkan psikolog mitra tanpa menghapusnya. | Status aktif bisa dibalik. | `PATCH /api/admin/psychologists/{psychologist}/toggle` |
| `US-SUPER-05` | Sebagai superadmin, saya ingin melihat tren mood per sekolah, supaya saya bisa membandingkan kondisi antar sekolah. | **Hanya superadmin.** | `GET /api/mood-trends/{school}/{type}` · `GET /api/dashboard/mood-trends` |
| `US-SUPER-06` | Sebagai superadmin, saya ingin melihat seluruh riwayat laporan dan curhat seorang siswa saat ada eskalasi. | Hanya superadmin, dan target harus berperan siswa. Satu-satunya peran yang bisa melihat riwayat lengkap seorang siswa. | `GET /api/report/student/{user}` · `GET /api/sharing/student/{user}` |
| `US-SUPER-07` | Sebagai superadmin, saya ingin melihat jumlah sekolah dan admin di platform. | — | `GET /api/dashboard/super` |

---

## Story yang baru ada di spesifikasi atau desain

Dipanen dari [`docs/tasks/`](../tasks/) dan dari inventaris desain di [`12-ui-contract.md`](12-ui-contract.md). **Tidak ada implementasinya.** Sekaligus menjadi bahan mentah pengembangan berikutnya.

| ID | Story | Mengapa belum jalan | Sumber |
|---|---|---|---|
| `US-SISWA-19` `[SPEC-ONLY]` | Sebagai siswa, saya ingin **mencabut** persetujuan data yang sudah saya berikan, supaya saya tetap mengendalikan data saya. | Tidak ada state `revoked` di `ConsentStatus`, tidak ada kolom `revoked_at`, tidak ada endpoint. Setelah diberikan, akses psikolog tidak bisa ditarik. | `AND-1`, [`agent/screens/siswa_kelola_persetujuan_data.md`](../../agent/screens/siswa_kelola_persetujuan_data.md) |
| `US-SISWA-20` `[SPEC-ONLY]` | Sebagai siswa, saya ingin menyetujui atau menolak **usulan jadwal baru dari psikolog**. | `DecideReferralAction` membuat booking pengganti yang sudah terkonfirmasi, tanpa melibatkan siswa. | Desain `#5609:39677` |
| `US-SISWA-21` `[SPEC-ONLY]` | Sebagai siswa, saya ingin mencatat mood **"Takut"**. | `MoodStatus` hanya punya empat nilai. | Desain mood check-in |
| `US-SISWA-22` `[SPEC-ONLY]` | Sebagai siswa, saya ingin latihan pernapasan, pelukan kupu-kupu, aktivitas fisik berwaktu, dan afirmasi diri. | `self_helps.type` hanya empat nilai; tidak ada endpoint untuk empat latihan lainnya. | Desain self-help (8 aktivitas) |
| `US-SISWA-23` `[SPEC-ONLY]` | Sebagai siswa, saya ingin menilai apakah sebuah artikel bermanfaat. | Tidak ada tabel atau endpoint. | Desain `#1654:26637` |
| `US-SISWA-24` `[SPEC-ONLY]` | Sebagai siswa, saya ingin mengatur ulang password saya kalau lupa. | Tidak ada alur reset password; tabel `password_reset_tokens` tidak dipakai. | — |
| `US-BK-15` `[SPEC-ONLY]` | Sebagai Guru BK, saya ingin mencatat bahwa **orang tua/wali sudah dihubungi dan menyetujui** sebelum mengajukan rujukan. | Tidak ada kolom, tabel, atau aktor orang tua di sistem. | Desain rujukan |
| `US-BK-16` `[SPEC-ONLY]` | Sebagai Guru BK, saya ingin menandai bahwa NLP **melewatkan** sebuah kasus (false negative), supaya model belajar dari kesalahan arah sebaliknya. | Hanya `false-positive` yang punya endpoint. Tiga nilai lain di `NlpAnalysisStatus` hanya bisa ditulis seeder. | `BE-4.1` |
| `US-BK-17` `[SPEC-ONLY]` | Sebagai Guru BK, saya ingin mencetak laporan data siswa dan riwayat self-help. | Hanya export mood yang ada. | Desain "Cetak Laporan" |
| `US-KEPSEK-06` `[PARTIAL]` | Sebagai kepala sekolah, saya ingin **diberi tahu segera** saat ada kasus Zona Merah, tanpa harus membuka dashboard. | Job, notification, dan channel `database` + `mail` sudah ada, tapi **tidak ada kode yang memanggil job-nya**. Driver mail juga default `log`. | `AND-12`, `BE-12.1` |
| `US-SUPER-08` `[SPEC-ONLY]` | Sebagai superadmin, saya ingin mengelola API key dari antarmuka. | Tabel `gemini_api_token` ada dan dirotasi otomatis, tapi tidak ada endpoint CRUD. | Desain `#2727:50076` |
| `US-SISWA-25` `[DEAD]` | Sebagai siswa, saya ingin alur laporan saya dikonfirmasi, dijadwalkan ulang, ditutup, atau dibatalkan oleh Guru BK. | Keempat endpoint ada tapi guard-nya membandingkan status dengan kosakata lama, sehingga selalu 403. Lihat [`08-state-machines.md`](08-state-machines.md). | — |

---

## Catatan untuk penulisan story berikutnya

- Satu story = satu kemampuan yang bisa diamati dari luar. Kalau kriteria penerimaannya menyebut nama tabel atau kelas, itu tugas teknis, bukan story.
- Selalu cantumkan endpoint-nya. Story tanpa endpoint berarti `[SPEC-ONLY]` dan harus dipindahkan ke bagian terakhir.
- ID tidak pernah dipakai ulang. Kalau sebuah story dihapus, nomornya dibiarkan kosong.
- Kalau sebuah story pindah dari `[SPEC-ONLY]` menjadi terimplementasi, pindahkan barisnya ke tabel aktor yang bersangkutan dan perbarui header `verified:` dokumen ini.
