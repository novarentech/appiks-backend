# UI Spec: Siswa - Formulir Curhat & Assessment Mood
**Figma Node ID:** `4850:28946`

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Multi-step Interactive Form Card
- **Visible Fields:**
  - `mood_trigger_options` (Array of Strings) -> `Diperlakukan tidak adil`, `Konflik dengan teman/keluarga`, `Frustrasi dengan diri sendiri`, `Tekanan dari orang lain`, `Merasa tidak didengar`, `Situasi yang tidak bisa dikontrol`
  - `expression_options` (Array of Strings) -> `Berbicara langsung tentang masalahnya`, `Meledak secara emosional`, `Menyendiri sampai tenang`, `Menulis atau journaling`, `Olahraga atau aktivitas fisik`, `Menahan dalam hati`
  - `curhat_categories` (Array of Objects) -> `Tekanan Akademik`, `Kesehatan Mental`, `Masalah Keluarga`, `Pengembangan Diri`

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Penyebab Mood | Checkbox Multi-select | Required, Array of Strings | `mood_triggers` |
| Judul Curhat | Text Input | Required, Max 150 chars | `title` |
| Ceritakan Keluhanmu | Text Area | Required, Min 10 chars, Max 5000 chars | `story_content` |
| Ekspresi Mood | Radio / Single-select | Required, String | `expression_type` |
| Kategori Curhat | Select / Card Click | Optional, UUID / String | `category_id` |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Submit Curhat (Klik Tombol "Kirim Curhat")
  - **HTTP Method & Type:** `POST` `/api/v1/siswa/curhat`
  - **Behavior Logic:** Menyimpan curhat siswa ke database. Memicu synchronous/asynchronous AI Sentiment & Crisis Assessment Service untuk memindai kata kunci emosional/suisidal. API Response akan mengembalikan flag `risk_level` (`RED`, `YELLOW`, `SAFE`) yang digunakan oleh frontend untuk menentukan modal rujukan mana yang akan ditampilkan. `[Architect Note]`.

---

### [Architect Note]
1. **AI Risk Assessment Hook:** Begitu curhat dikirim, teks `story_content` wajib dianalisis oleh AI Service/Classifier. Jika terdeteksi indikasi krisis tinggi (cth: kata kunci menyakiti diri sendiri), sistem secara otomatis membuat alert *Zona Merah* untuk Guru BK dan merespon payload dengan `risk_level: "RED"`.
2. **Data Confidentiality:** Teks curhat disimpan secara terenkripsi di tabel `student_stories`. Hak akses pembacaan hanya diberikan kepada Guru BK PIC sekolah siswa tersebut.
