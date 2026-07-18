# UI Spec: Psikolog - List Inbox Rujukan Masuk
**Figma Node IDs:** `4410:27642` (List Inbox Rujukan Masuk), `4414:28578` (List Inbox Rujukan Masuk - Selengkapnya)

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Card List with Filter Tabs
- **Visible Fields:**
  - `referrals_inbox` (Array of Objects) -> Daftar kartu rujukan masuk:
    - `referral_id` (UUID) -> ID Rujukan
    - `student_name` (String) -> `Ardi Putra`
    - `proposed_date` (String) -> `08/28/2025`
    - `proposed_time` (String) -> `10:00`
    - `counselor_name` (String) -> `Guru BK : Sri Wahyuni, S.Pd, M.Pd`
    - `safety_zone` (String / Enum: `kritis`, `prioritas`, `aman`) -> Badge kategori urgensi (`Kritis` / `Prioritas`).
    - `booking_status` (String / Enum: `menunggu_konfirmasi`, `terkonfirmasi`, `selesai`, `expired`) -> Status booking (`Menunggu Persetujuan` / `Terkonfirmasi` / `Selesai` / `Kadaluarsa`).
    - `bk_counselor_notes` (String) -> `Catatan Awal Guru BK : Berdasarkan asesmen, siswa memerlukan pendampingan`
    - `submitted_at_formatted` (String) -> `Diajukan pada : 21 Mei 2026, 14:00 WIB`
    - `actions_allowed` (Object) -> Flag tombol aksi yang diizinkan untuk dirender:
      - `can_confirm` (Boolean)
      - `can_decline` (Boolean)
      - `can_view_ai_report` (Boolean)
      - `can_change_schedule` (Boolean)
      - `can_view_report` (Boolean)

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Filter Status | Tab Navigation | Optional | `status_tab` (Query Param) |
| Konfirmasi | Button | Only active on pending state | N/A |
| Tolak Jadwal | Button | Only active on pending state | N/A |
| Buka Laporan AI | Button | Only active on confirmed state | N/A |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Get Inbox Referrals List
  - **HTTP Method & Type:** `GET` `/api/v1/psikolog/referrals`
  - **Behavior Logic:** Mengambil seluruh daftar rujukan masuk yang diarahkan ke faskes/psikolog login. Kasus berstatus `Kritis` diurutkan di paling atas.

- [ ] **Action:** Confirm Referral Booking (Klik "Konfirmasi")
  - **HTTP Method & Type:** `POST` `/api/v1/psikolog/referrals/{id}/confirm`
  - **Behavior Logic:** Mengubah status rujukan menjadi `terkonfirmasi`. Backend secara otomatis menetapkan ruang/metode konsultasi dan memicu push notification sukses ke Siswa dan Guru BK. `[Architect Note]`.

- [ ] **Action:** Decline Referral Booking (Klik "Tolak Jadwal")
  - **HTTP Method & Type:** `POST` `/api/v1/psikolog/referrals/{id}/decline`
  - **Behavior Logic:** Mengubah status rujukan menjadi `ditolak_psikolog`. Mengembalikan status insiden siswa di BK menjadi `sedang_ditangani` agar Guru BK dapat mencari jadwal alternatif atau merujuk ke psikolog lain.

---

### [Architect Note]
1. **Auto-Cancellation for Expired Proposals:** Booking proposal memiliki SLA kadaluwarsa 24 jam. Jika dalam 24 jam sejak `submitted_at` pihak Psikolog tidak mengonfirmasi/menolak, cron job backend harus otomatis mengubah status menjadi `expired` ('Kadaluarsa'), melepaskan slot waktu psikolog, dan mengirimkan alert kembali ke Guru BK.
