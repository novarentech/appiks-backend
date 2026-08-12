# UI Spec: Siswa - Dashboard Self Help & Resources (CMS Content)
**Figma Node IDs:** `4896:30133` (self Help), `4852:31463` (self-help page)

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Resource Hub Dashboard Card & CMS Content List
- **Visible Fields:**
  - `quote_of_the_day` (Object) -> `quote_text`, `author`
  - `user_profile` (Object) -> `fullname` (`Marsha Bilqiis`), `streak_days` (`7 hari!`), `today_mood` (`Gembira`), `safety_status` (`Aman`)
  - `my_curhat_status` (Array of Objects) -> Status curhat siswa: `title`, `counselor_name`, `status` (`Dibalas`/`Menunggu`), `created_at`
  - `mood_calendar` (Array of Objects) -> Log riwayat mood bulanan
  - `cms_resources` (Array of Objects) -> Daftar artikel & video dari CMS database:
    - `resource_id` (UUID)
    - `title` (String) -> `Mengapa Self-Awareness Penting untuk Kesehatan Mental?`
    - `summary` (String) -> `Penjelasan singkat tentang pentingnya mengenali diri sendiri...`
    - `category` (String / Enum) -> `Konten Edukasi`, `Anger Management`, `Self Help`
    - `content_type` (String / Enum) -> `Artikel`, `Video`
    - `thumbnail_url` (String)

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Filter Kategori | Filter Chip / Pill | Optional String | `category` |
| Filter Jenis Konten | Filter Chip / Pill | Optional Enum (`artikel`, `video`) | `content_type` |
| Baca / Tonton Artikel | Card Click | Opens full CMS article view | N/A |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Get CMS Educational Resources List
  - **HTTP Method & Type:** `GET` `/api/v1/siswa/resources`
  - **Query Parameters:** `category`, `content_type`, `page`, `per_page`
  - **Behavior Logic:** Mengambil daftar materi artikel dan video edukasi dari tabel CMS `educational_resources` yang dikelola admin/superadmin. `[Architect Note]`.

---

### [Architect Note]
1. **CMS Database Retrieval:** Seluruh data rekomendasi materi `cms_resources` ditarik langsung dari database CMS statis (tabel `educational_resources`), bukan hasil kalkulasi AI generatif secara langsung.
