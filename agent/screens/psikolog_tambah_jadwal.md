# UI Spec: Psikolog Mitra - Modal Form Tambah Jadwal Konsultasi
**Figma Node IDs:** `4862:29871` (tambah jadwal ver 2), `4842:32419`

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Modal Form Popup
- **Visible Fields:**
  - `modal_title` (String) -> `Tambah Jadwal`
  - `modal_subtitle` (String) -> `Jadwal ini bersifat tentative dan menunggu konfirmasi`
  - `repeat_info_text` (String) -> `Slot ini akan otomatis muncul di minggu-minggu berikutnya pada hari & jam yang sama. Anda tetap bisa menghapus atau mengubahnya per minggu.`

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Tanggal | Date Picker | Required, Date format (`YYYY-MM-DD`), Must be today or future date | `date` |
| Jam Mulai | Time Picker | Required, Time format (`HH:mm`) | `start_time` |
| Jam Selesai | Time Picker | Required, Time format (`HH:mm`), Must be > `start_time` | `end_time` |
| Ulangi Setiap Minggu | Checkbox | Optional Boolean (Default: `false`) | `is_recurring` |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Create New Slot Schedule (Klik "Tambahkan Jadwal")
  - **HTTP Method & Type:** `POST` `/api/v1/psikolog/schedules/slots`
  - **Behavior Logic:** Memvalidasi tidak adanya bentrokan jam (*time-slot overlap validation*) dengan slot yang sudah ada. Jika `is_recurring: true`, backend men-generate entri slot berulang untuk 4 minggu ke depan secara otomatis. `[Architect Note]`.

---

### [Architect Note]
1. **Overlap Validation:** Backend wajib melakukan pemeriksaan terhadap tabel `psychologist_schedules` agar jam mulai dan selesai yang dimasukkan tidak bersinggungan dengan slot lain milik psikolog tersebut pada tanggal yang sama.
2. **Recurring Slot Generation:** Jika `is_recurring` diaktifkan, background job akan menyalin slot ini ke tanggal-tanggal di minggu berikutnya dengan hari dan jam yang sama.
