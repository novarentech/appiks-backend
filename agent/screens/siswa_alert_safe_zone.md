# UI Spec: Siswa - Konfirmasi Curhat Tersimpan (Safe Zone Modal)
**Figma Node ID:** `4896:3484`

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Success Info Modal Overlay
- **Visible Fields:**
  - `modal_title` (String) -> `Curhatmu Telah Tersimpan`
  - `modal_message` (String) -> `Terima kasih sudah berbagi cerita! Curhatanmu tersimpan dengan aman dan terlindungi. Kamu juga bisa membaca artikel bantuan mandiri yang kami rekomendasikan.`

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Lihat Rekomendasi Self Help | Primary Button | Redirects to `siswa_self_help_dashboard` | N/A |
| Kembali ke Dashboard | Secondary Button | Redirects to `siswa_beranda` | N/A |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Close Modal & Redirect to Self Help
  - **HTTP Method & Type:** `GET` `/api/v1/siswa/self-help/articles`
  - **Behavior Logic:** Mengarahkan siswa ke halaman artikel/video bantuan mandiri yang relevan dengan topik curhatnya. `[Architect Note]`.

---

### [Architect Note]
1. **Low-Priority Logging:** Curhat berkategori Safe Zone tetap tercatat dalam histori emosional siswa untuk kalkulasi kalender mood & streak harian, namun tidak memicu alarm darurat untuk Guru BK.
