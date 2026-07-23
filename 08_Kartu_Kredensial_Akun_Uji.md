# Kartu Kredensial Akun Uji

Berikut adalah daftar akun yang disiapkan melalui `DemoCaseSeeder` untuk keperluan demonstrasi studi kasus. 

Semua akun menggunakan kata sandi yang sama: **`password123`**

## Akun Guru BK (Counselors)
Setiap Guru BK memiliki 1 Siswa yang terhubung dan telah mengirimkan 2 kasus (1 Kuning, 1 Merah).

| No | Nama | Email | Username | Peran |
|----|---|---|---|---|
| 1 | Guru BK 01 | bk01@appiks.test | bk01 | Guru BK |
| 2 | Guru BK 02 | bk02@appiks.test | bk02 | Guru BK |
| 3 | Guru BK 03 | bk03@appiks.test | bk03 | Guru BK |
| 4 | Guru BK 04 | bk04@appiks.test | bk04 | Guru BK |
| 5 | Guru BK 05 | bk05@appiks.test | bk05 | Guru BK |

## Akun Siswa (Students)
Setiap Siswa terhubung ke Guru BK dengan nomor yang sama (Siswa 01 ke Guru BK 01, dst.).

| No | Nama | Email | Username | Peran | Terhubung Ke |
|----|---|---|---|---|---|
| 6 | Siswa 01 | siswa01@appiks.test | siswa01 | Siswa | Guru BK 01 |
| 7 | Siswa 02 | siswa02@appiks.test | siswa02 | Siswa | Guru BK 02 |
| 8 | Siswa 03 | siswa03@appiks.test | siswa03 | Siswa | Guru BK 03 |
| 9 | Siswa 04 | siswa04@appiks.test | siswa04 | Siswa | Guru BK 04 |
| 10 | Siswa 05 | siswa05@appiks.test | siswa05 | Siswa | Guru BK 05 |

## Akun Demo Interaktif
Akun ini dikhususkan untuk didemonstrasikan secara langsung (live-typing).

| No | Nama | Email | Username | Peran | Keterangan |
|----|---|---|---|---|---|
| 11 | Guru BK Demo | bkdemo@appiks.test | bkdemo | Guru BK | Untuk walkthrough segmen 2 (Melihat dasbor BK) |
| 12 | Siswa Demo | siswademo@appiks.test | siswademo | Siswa | Untuk walkthrough segmen 2 (Kirim curhat saat demo) |

---
*Gunakan `php artisan db:seed --class=DemoCaseSeeder` untuk memuat data di atas ke dalam basis data.*
