# UI Spec: Siswa - Daftar Jadwal Konsultasi (Pemilihan Tanggal)
**Figma Node ID:** `3960:36516`

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Date Selection Grid & Psychologist Profile Card
- **Visible Fields:**
  - `page_title` (String) -> `Pilih Jadwal Konsultasi`
  - `page_subtitle` (String) -> `Pilih waktu yang sesuai dengan jadwal Anda untuk konsultasi dengan psikolog mitra`
  - `target_psychologist` (Object) -> Kartu ringkas profil psikolog:
    - `psychologist_name` (String) -> `Dr. Sarah Wijaya, M.Psi., Psikolog`
    - `facility_name` (String) -> `Puskesmas Kecamatan Menteng`
    - `specialization` (String) -> `Psikologi Klinis Anak & Remaja`
  - `earliest_slot_banner` (String) -> `Slot tersedia paling cepat tanggal Sabtu, 16 Mei 2026` `[Architect Note]`
  - `available_dates_grid` (Array of Objects) -> Daftar tanggal dan sisa kuota (`Pilih Tanggal`):
    - `date_raw` (Date / YYYY-MM-DD) -> Tanggal standar database (`2026-05-16`).
    - `date_formatted` (String) -> Tampilan tanggal lokal (`Sabtu, 16 Mei 2026`).
    - `available_slots_count` (Integer) -> Jumlah sisa sesi kosong (`3`).
    - `slot_label` (String) -> Tampilan teks kuota (`3 slot tersedia`).
    - `is_selectable` (Boolean) -> Flag aktif/tidaknya tombol tanggal berdasarkan ketersediaan kuota (`available_slots_count > 0`).

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Date Grid Item Button | Action Button | Only selectable if `is_selectable == true` | `selected_date` (`2026-05-16`) |
| Kembali | Navigation Button | Navigates back to `Review Persetujuan` or `Pusat Aktivitas` | N/A |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Load Available Consultation Dates (Dimuat saat halaman pemilihan tanggal dibuka)
  - **HTTP Method & Type:** `GET` `/api/v1/siswa/rujukan-psikolog/{id}/available-dates`
  - **Behavior Logic:** Mengambil rentang tanggal (misal 14 atau 30 hari ke depan) yang memiliki kuota jadwal psikolog. Backend menghitung `available_slots_count` berdasarkan pengurangan total kapasitas harian psikolog dengan sesi yang sudah di-booking (`status IN ('menunggu_konfirmasi', 'terkonfirmasi')`).

- [ ] **Action:** Select Date to View Slots (Klik salah satu tombol tanggal pada Grid)
  - **HTTP Method & Type:** `GET` `/api/v1/siswa/rujukan-psikolog/{id}/available-slots?date=2026-05-16`
  - **Behavior Logic:** Mengarahkan siswa ke screen pemilihan jam/slot spesifik (`siswa_pilih_jadwal_konsultasi.md`) untuk tanggal yang diklik.

---

### [Architect Note]
1. **Dynamic Slot & Quota Availability Logic:** Perhitungan `available_slots_count` untuk tiap tanggal harus akurat dan *concurrency-safe*. Backend harus memperhitungkan slot yang sedang dalam masa tunggu konfirmasi (`menunggu_konfirmasi`) agar tidak terjadi *double-booking* atau kelebihan kapasitas pada hari yang sama.
2. **Earliest Slot Calculation (`earliest_slot_banner`):** Field `earliest_slot_banner` dikalkulasi secara dinamis oleh query backend dengan mencari `MIN(slot_date)` di mana `available_slots_count > 0` dan `slot_date >= CURRENT_DATE`.
