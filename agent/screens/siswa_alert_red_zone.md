# UI Spec: Siswa - Peringatan Intervensi Kritis (Red Zone Alert & Hotline Bantuan)
**Figma Node IDs:** `4851:29095` (Modal Red Zone Detected), `4852:31147` (After Click Lihat Kontak Bantuan)

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Emergency Modal Overlay & Emergency Contact List Card
- **Visible Fields:**
  - `alert_title` (String) -> `Kamu Memerlukan Perhatian Segera`
  - `alert_message` (String) -> `Kami Peduli dengan Kondisimu. Terima kasih telah mempercayai kami dengan menceritakan apa yang kamu rasakan. Kami mendeteksi bahwa isi curhatanmu menunjukkan tanda-tanda yang memerlukan perhatian segera...`
  - `emergency_hotlines` (Array of Objects) -> Daftar kontak bantuan darurat:
    - `service_name` (String) -> Nama hotline / lembaga (cth: `Hotline Sehat Jiwa Kemenkes`, `Guru BK Sekolah`)
    - `phone_number` (String) -> Nomor kontak darurat (cth: `119 extension 8`)
    - `operating_hours` (String) -> Jam operasional (cth: `24 Jam`)

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Lihat Kontak Bantuan | Primary Action Button | Redirects to Emergency Contacts Modal | N/A |
| Hubungi Sekarang | Call / Link Button | Triggers `tel:` protocol / direct call | N/A |
| Tutup / Ke Halaman Dashboard | Secondary Button | Dismisses modal, redirects to dashboard | N/A |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Get Emergency Hotline Contacts
  - **HTTP Method & Type:** `GET` `/api/v1/emergency-contacts`
  - **Behavior Logic:** Mengambil daftar kontak bantuan darurat nasional & kontak Guru BK aktif sekolah.

- [ ] **Action:** Acknowledge Crisis Alert (Saat siswa mengklik "Lihat Kontak Bantuan" atau menghubungi kontak)
  - **HTTP Method & Type:** `POST` `/api/v1/siswa/curhat/{id}/ack-crisis`
  - **Behavior Logic:** Merekam status bahwa siswa telah melihat warning darurat dan memilih mengakses kontak bantuan. `[Architect Note]`.

---

### [Architect Note]
1. **Immediate BK Notification Event:** Saat modal *Red Zone Detected* dipicu, backend wajib langsung menembakkan notifikasi push/WhatsApp darurat real-time ke akun Guru BK PIC yang bertugas agar penanganan fisik/tatap muka di sekolah dapat segera disiapkan.
2. **Client-side Persistence:** Modal Red Zone tidak dapat ditutup secara tidak sengaja (modal backdrop-click disabled) untuk memastikan siswa membaca pesan dukungan dan memiliki akses penuh ke hotline bantuan.
