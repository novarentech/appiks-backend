# UI Spec: Siswa - Kelola Persetujuan Data & Konfirmasi Pencabutan Izin
**Figma Node IDs:** `3964:32046` (Kelola Persetujuan Data), `3965:32210` (Konfirmasi Pencabutan Izin), `3965:32284` (ConfirmationModal)

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Consent Management Dashboard & Modal Dialog
- **Visible Fields (`Kelola Persetujuan Data`):**
  - `page_title` (String) -> Judul halaman (`Kelola Persetujuan Data`).
  - `page_description` (String) -> Penjelasan (`Lihat dan kelola izin akses data yang telah Anda bagikan kepada psikolog mitra`).
  - `active_grant_info` (Object) -> Kartu ringkasan izin aktif:
    - `psychologist_name` (String) -> `Dr. Sarah Wijaya, M.Psi., Psikolog`
    - `facility_name` (String) -> `Puskesmas Kecamatan Menteng`
    - `granted_at_formatted` (String) -> Waktu pemberian izin (`Izin diberikan pada: 13 Mei 2026, 14:30 WIB`)
    - `status_badge` (String / Enum: `aktif`, `dicabut`) -> Status izin (`Aktif`)
  - `granted_items_list` (Array of Objects) -> Daftar data spesifik yang sedang dibagikan (`Data yang Dibagikan`):
    - `item_key` (String) -> Key data (`share_mood_30_days`, `share_red_zone_curhat`, `share_bk_assessment`)
    - `title` (String) -> `Riwayat mood 30 hari terakhir`, `Kutipan teks curhat yang memicu Red Zone`, `Catatan asesmen Guru BK`
    - `granted_at_info` (String) -> `Diberikan pada: 13 Mei 2026, 14:30 WIB`
  - `revocation_section_visible` (Boolean) -> Flag untuk menampilkan kotak peringatan & tombol cabut izin (`Mencabut Izin Akses`).

- **Visible Fields (`ConfirmationModal - 3965:32284`):**
  - `modal_title` (String) -> `Konfirmasi Pencabutan Izin`
  - `confirmation_message` (String) -> `Apakah Anda yakin ingin mencabut izin akses data untuk Dr. Sarah Wijaya, M.Psi., Psikolog? Tindakan ini tidak dapat dibatalkan.`

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Cabut Izin Akses | Trigger Button | Opens confirmation modal (`ConfirmationModal`) | N/A |
| Batal (Modal) | Button | Closes modal dialog without changes | N/A |
| Ya, Cabut Izin (Modal) | Submit Button | Required confirmation to trigger revocation | `consent_id` / `referral_id` |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Load Active Consents (Saat halaman kelola dibuka)
  - **HTTP Method & Type:** `GET` `/api/v1/siswa/consents/active`
  - **Behavior Logic:** Mengambil daftar izin data yang berstatus `aktif` untuk user siswa saat itu.

- [ ] **Action:** Execute Consent Revocation (Klik Tombol "Ya, Cabut Izin" pada Modal Konfirmasi)
  - **HTTP Method & Type:** `POST` `/api/v1/siswa/consents/{id}/revoke`
  - **Behavior Logic:** Melakukan pencabutan izin akses data secara permanen. Backend mengubah status pada tabel `data_access_consents` menjadi `revoked` dan mencatat `revoked_at = now()`.

---

### [Architect Note]
1. **Immediate Access Cutoff & Scope Invalidation Logic:** Pencabutan izin (`Ya, Cabut Izin`) harus langsung membatalkan seluruh hak akses Psikolog terhadap data siswa secara seketika (*immediate revocation*). Middleware backend wajib menghapus/menginvalidasi token scope atau Redis ACL cache yang memberikan izin baca `share_mood_30_days`, `share_red_zone_curhat`, atau `share_bk_assessment` kepada Psikolog tersebut.
2. **Audit Trail Preservation:** Meskipun statusnya diubah menjadi `dicabut` / `revoked`, log histori riwayat pemberian dan pencabutan izin tidak boleh di-hard delete dari database demi keperluan audit kepatuhan privasi (audit trail protection).
