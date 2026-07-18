# UI Spec: Siswa - Status Rujukan (Jadwal Konseling Detail)
**Figma Node IDs:** `3970:32847`, `3972:33091` (Menunggu Konfirmasi), `3975:33322`, `3975:33418` (Terkonfirmasi)

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Detail Card List within Tabbed Activity Panel
- **Visible Fields:**
  - `referral_status` (String / Enum: `menunggu_konfirmasi`, `terkonfirmasi`) -> Badge status jadwal (`Menunggu Konfirmasi` / `Terkonfirmasi`).
  - `psychologist_name` (String) -> Nama psikolog mitra (`Dr. Sarah Wijaya, M.Psi., Psikolog`).
  - `facility_name` (String) -> Nama instansi kesehatan (`Puskesmas Kecamatan Menteng`).
  - `counselor_name` (String) -> Nama Guru BK pengaju (`Guru BK : Sri Wahyuni, S.Pd, M.Pd`).
  - `time_slot_label` (String) -> Jam slot waktu (`09:00 - 10:00 WIB`).
  - `session_details` (Object / Collapsible):
    - `date_formatted` (String) -> Tanggal konsultasi (`Sabtu, 16 Mei 2026`).
    - `location` (String) -> Lokasi fisik atau virtual link pertemuan (`Puskesmas Kecamatan Cempaka Putih, Ruang Konsultasi 1`) `[Architect Note]`.
    - `created_at_formatted` (String) -> Tanggal pengajuan dibuat (`Dibuat pada : 08 Mei 2026, 11:00 WIB`).

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Lihat Detail / Sembunyikan | Button Link | Client-side collapsible toggle | N/A |
| Kembali ke Beranda | Navigation Button | Navigates back to `/siswa/beranda` | N/A |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Get Rujukan Status Details
  - **HTTP Method & Type:** `GET` `/api/v1/siswa/booking-schedules/{id}`
  - **Behavior Logic:** Mengambil informasi status terkini dari jadwal rujukan bersangkutan, termasuk lokasi spesifik jika sudah dikonfirmasi.

---

### [Architect Note]
1. **Dynamic Location and Notification Dispatch:** Field `location` hanya diisi dan dikirim oleh API ketika status booking sudah berubah menjadi `terkonfirmasi`. Saat transisi status dari `menunggu_konfirmasi` ke `terkonfirmasi` terjadi (dipicu oleh aksi Psikolog), backend harus secara otomatis mengirimkan notifikasi push (Firebase/FCM) dan email berisi detail waktu, lokasi, serta petunjuk pertemuan ke siswa bersangkutan.
2. **Access Control:** Endpoint GET detail rujukan ini hanya boleh diakses oleh pemilik rujukan (siswa bersangkutan) atau wali siswa terdaftar, Guru BK pengaju, dan Psikolog yang bersangkutan (data ownership safety check).
