# UI Spec: Psikolog - Laporan AI & Catatan Klinis (Detail Rujukan Masuk)
**Figma Node IDs:** `4415:29358` (Lihat Detail Rujukan), `4452:29787`, `4471:2266` (Detail Catatan & Laporan), `4452:28709`, `4452:29043`, `4452:29418` (Confirmation Modals)

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Case Detail Profile with AI Summary & Clinical Input Card
- **Visible Fields:**
  - `case_id` (UUID) -> ID Kasus
  - `student_profile` (Object) -> `Identitas Siswa`:
    - `fullname` (String) -> `Alex Allan`
    - `nis` (String) -> `TRD-64851`
    - `class_name` (String) -> `X IPA 1`
    - `bk_counselor` (String) -> `Sri Wahyuni, S.Pd, M.Pd`
    - `report_date` (String) -> `08/27/2025 09:00 AM`
    - `booking_status` (String) -> `Menunggu Persetujuan`
  - `ai_summary` (String) -> Ringkasan otomatis AI (`Ringkasan AI`) `[Architect Note]`.
  - `psychologist_clinical_notes` (String / Nullable / Encrypted) -> Catatan klinis yang sudah disimpan oleh Psikolog login `[Architect Note]`.

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Tambah Catatan Klinis | Text Area | Optional, Max 3000 chars | `clinical_notes` |
| Simpan | Submit Button | Only enabled if text area is dirty | N/A |
| Konfirmasi | Bottom Button | Active if status is pending | N/A |
| Tolak Jadwal | Bottom Button | Active if status is pending | N/A |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Get AI Summary & Case Detail (Saat halaman dibuka)
  - **HTTP Method & Type:** `GET` `/api/v1/psikolog/referrals/{id}/summary`
  - **Behavior Logic:** Mengambil identitas siswa, ringkasan AI, dan catatan klinis psikolog jika ada.

- [ ] **Action:** Save Clinical Notes (Klik Tombol "Simpan" pada card)
  - **HTTP Method & Type:** `POST` `/api/v1/psikolog/referrals/{id}/clinical-notes`
  - **Behavior Logic:** Menyimpan catatan klinis psikolog login pada tabel `psychologist_clinical_notes` terenkripsi.

---

### [Architect Note]
1. **AI Summary Generation & Privacy Compliance:** Content `ai_summary` dihasilkan di backend melalui antrean job background LLM yang menganalisis curhat + mood 30 hari yang disetujui siswa. Catatan klinis psikolog (`psychologist_clinical_notes`) disimpan terenkripsi (AES-256-GCM) di tabel `psychologist_clinical_notes`. Catatan ini bersifat rahasia profesional (medical privilege) dan hanya boleh diakses oleh psikolog penanggung jawab, tidak dibagikan kepada siswa maupun Guru BK sekolah.
