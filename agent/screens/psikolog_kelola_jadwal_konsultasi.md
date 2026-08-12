# UI Spec: Psikolog Mitra - Kelola Jadwal Konsultasi
**Figma Node IDs:** `4840:29698` (Kelola Jadwal Konsultasi), `4842:31455`

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Weekly Calendar Schedule & Slot Management Dashboard
- **Visible Fields:**
  - `active_week_range` (String) -> `Minggu 27 Juli – 1 Agustus 2026`
  - `summary_stats` (Object):
    - `confirmed_slots_count` (Integer) -> `3`
    - `pending_slots_count` (Integer) -> `2`
    - `active_users_count` (Integer) -> `5`
    - `available_slots_count` (Integer) -> `3`
  - `weekly_schedule_days` (Array of Objects) -> Daftar hari (Senin - Sabtu):
    - `day_name` (String) -> `Senin`
    - `date_number` (Integer) -> `27`
    - `month_name` (String) -> `Juli`
    - `slots` (Array of Objects):
      - `slot_id` (UUID)
      - `time_range` (String) -> `09:00–10:00`
      - `status` (Enum: `tersedia`, `menunggu_konfirmasi`, `terkonfirmasi`)
      - `student_code` (String / Nullable) -> `Siswa #A-2812` (tersamar)
      - `is_deletable` (Boolean) -> `true` jika status `tersedia`, `false` jika sudah dibooking.

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Tambah Jadwal | Primary Button | Opens `psikolog_tambah_jadwal` modal | N/A |
| Navigasi Minggu | Arrow / Button | Navigates previous/next week | `week_offset` |
| Hapus Slot | Trash Icon Button | Only active for available slots | N/A |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Get Psychologist Weekly Availability Schedule
  - **HTTP Method & Type:** `GET` `/api/v1/psikolog/schedules`
  - **Query Parameters:** `start_date`, `end_date`
  - **Behavior Logic:** Mengambil seluruh slot ketersediaan waktu konseling Psikolog login untuk minggu yang dipilih.

- [ ] **Action:** Delete Availability Slot
  - **HTTP Method & Type:** `DELETE` `/api/v1/psikolog/schedules/slots/{slot_id}`
  - **Behavior Logic:** Menghapus slot ketersediaan waktu. Menerapkan penguncian (lock): slot dengan status `menunggu_konfirmasi` atau `terkonfirmasi` dilarang dihapus (`400 Bad Request`). `[Architect Note]`.

---

### [Architect Note]
1. **Psychologist Role Scope:** Halaman ini khusus diperuntukkan bagi **Psikolog Mitra** terdaftar untuk mengatur ketersediaan slot konseling rujukan dari sekolah.
2. **Anonymized Student Display:** Identitas siswa yang memboking slot disamarkan dalam bentuk kode unik (`Siswa #A-2812`) demi menjaga privasi sebelum rujukan dikonfirmasi secara resmi.
