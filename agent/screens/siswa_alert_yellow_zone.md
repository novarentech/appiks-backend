# UI Spec: Siswa - Peringatan Intervensi Menengah (Yellow Zone Alert)
**Figma Node ID:** `4860:29368`

## 1. Data Presentational (GET Blueprint)
*Gunakan bagian ini untuk memetakan kolom/field yang dibaca dari database dan dikirim via API Response JSON*
- **Component Type:** Warning Modal Overlay
- **Visible Fields:**
  - `alert_title` (String) -> `Perhatian Diperlukan`
  - `alert_message` (String) -> `Hasil analisis curhatmu menunjukkan indikasi stres / kecemasan tingkat menengah. Sangat disarankan untuk berdiskusi lebih lanjut dengan Guru BK sekolah.`

---

## 2. User Interaction & Input Fields (Payload Blueprint)
*Gunakan bagian ini untuk merancang FormRequest Validation & skema database*
| Field Label | UI Element Type | Requirements / Constraints | Expected Payload Key |
| :--- | :--- | :--- | :--- |
| Jadwalkan Konseling | Primary Button | Redirects to `siswa_pilih_jadwal_konsultasi` | N/A |
| Nanti Saja / Ke Beranda | Secondary Button | Dismisses modal | N/A |

---

## 3. Operations & API Triggers (Route & Controller Blueprint)
*Gunakan bagian ini untuk merancang Endpoint, HTTP Method, dan Logic Hook*
- [ ] **Action:** Transition to Counseling Schedule (Klik "Jadwalkan Konseling")
  - **HTTP Method & Type:** `GET` `/api/v1/siswa/counseling/available-slots`
  - **Behavior Logic:** Mengarahkan siswa langsung ke halaman pemilihan slot konsultasi Guru BK dengan pre-filled data referensi curhat. `[Architect Note]`.

---

### [Architect Note]
1. **Prioritization Tagging:** Ketika curhat berada di *Yellow Zone*, sistem secara otomatis menandai kasus siswa tersebut pada dashboard Guru BK sebagai "Prioritas Menengah" sehingga Guru BK dapat memantau jika kondisi siswa memburuk.
