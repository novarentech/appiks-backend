# Dokumentasi Fondasi APPIKS Backend

<!-- verified: branch=dev commit=266f860 date=2026-10-09 scope=. -->
> **Terverifikasi terhadap:** `dev` @ `266f860` · 2026-10-09

Folder ini menjelaskan backend APPIKS **sebagaimana benar-benar dibangun** (as-built). Setiap klaim di sini menyebut file sumbernya, supaya Anda bisa memverifikasi sendiri dan supaya tautan yang mati menjadi tanda dokumen sudah kadaluarsa.

## Yang ini BUKAN

| Butuh ini | Lihat ini |
|---|---|
| Skema request/response per endpoint | Scramble, dijalankan runtime: `/docs` (UI) · `/api/docs.json` |
| Standar koding yang wajib diikuti | [`agent/RULE_OF_ARCHITECT.md`](../../agent/RULE_OF_ARCHITECT.md) |
| Kontrak integrasi dengan frontend Next.js | [`agent/technical-reference.md`](../../agent/technical-reference.md) |
| Riwayat permintaan fitur (tiket AND/BE) | [`docs/tasks/`](../tasks/) dan file `docs/[BE-x.y] *.md` |
| Spesifikasi UI per screen | [`agent/screens/`](../../agent/screens/) — **aspirational, baca peringatannya dulu** |

> **Peringatan penting.** `docs/tasks/` dan `agent/screens/` adalah **arsip niat desain**, bukan dokumentasi sistem. Keduanya sudah melenceng dari kode: menyebut endpoint `/api/v1/siswa/*` dan tabel `mood_checkins`, `referrals`, `data_access_consents` yang tidak pernah dibangun. Jangan jadikan sumber kebenaran untuk nama tabel atau endpoint. Yang as-built ada di folder ini.

## Daftar isi

| File | Isi |
|---|---|
| [`00-product-overview.md`](00-product-overview.md) | Apa itu APPIKS, rantai intervensi, batas sistem, invarian desain yang tidak boleh dilanggar |
| [`01-glossary.md`](01-glossary.md) | Istilah domain ↔ identifier di kode, dan jebakan penamaan |
| [`02-actors-and-scoping.md`](02-actors-and-scoping.md) | 7 aktor, kolom scoping, graf relasi aktor |
| [`03-authorization.md`](03-authorization.md) | Empat tempat otorisasi ditegakkan + matriks role × aksi |
| [`04-user-stories.md`](04-user-stories.md) | User story per aktor dengan kriteria penerimaan dan endpoint |
| [`05-user-flows.md`](05-user-flows.md) | Flow lintas aktor (sequence diagram) |
| [`06-user-activities.md`](06-user-activities.md) | Inventaris aktivitas per aktor |
| [`07-data-model/`](07-data-model/) | ERD per domain + kamus tabel |
| [`08-state-machines.md`](08-state-machines.md) | Lifecycle setiap enum status + tabel transisi |
| [`09-api-surface.md`](09-api-surface.md) | Peta seluruh endpoint: siapa boleh, ditegakkan di mana |
| [`10-architecture-and-integrations.md`](10-architecture-and-integrations.md) | Runtime, job/event/observer, kontrak NLP & Gemini |
| [`11-environment-and-runbook.md`](11-environment-and-runbook.md) | Setup lokal, akun demo, seeder sebagai skenario, deploy |
| [`12-ui-contract.md`](12-ui-contract.md) | Nilai enum ↔ copy UI verbatim, peta screen Figma |

## Jalur baca

- **Developer backend baru** → `01` (istilah) → `07-data-model/` (bentuk data) → `08` (status) → `09` (endpoint) → `03` (otorisasi)
- **Developer frontend** → `00` → `12` (label & screen) → `09` → [`agent/technical-reference.md`](../../agent/technical-reference.md)
- **Pemilik produk / non-teknis** → `00` → `04` (user story) → `05` (flow) → `06` (aktivitas)
- **Agent (sesi Claude)** → mulai dari [`CLAUDE.md`](../../CLAUDE.md) di root, lalu `01`, lalu slice yang relevan saja

## Kosakata label

Setiap baris atau section yang tidak berlabel dianggap `[IMPLEMENTED]`. Label dipakai persis seperti ini:

| Label | Arti |
|---|---|
| `[IMPLEMENTED]` | Ada di kode, terverifikasi. Default, biasanya tidak ditulis. |
| `[PARTIAL]` | Kodenya ada tapi tidak aktif atau tidak lengkap — mis. job yang tidak pernah dipanggil, call site yang dikomentari. |
| `[SPEC-ONLY]` | Ada di spesifikasi atau desain, **tidak ada di kode**. Wajib menautkan asalnya. |
| `[DEAD]` | Kode/route ada tapi tidak bisa jalan — method hilang, guard yang tidak pernah lolos. |

Aturan keras: **klaim yang tidak bisa disitasi ke file di repo wajib dilabeli `[SPEC-ONLY]`.** Ketiadaan aturan inilah yang membuat `agent/screens/` lama-lama terbaca sebagai kebenaran.

## Aturan sitasi

- Selalu pakai path **repo-relatif** (`../../app/Enums/ReportStatus.php`) — bisa diklik di GitHub maupun VS Code. Path absolut (`E:\...`) tidak pernah muncul di dokumen.
- Sebut nomor baris hanya bila sudah benar-benar dibuka, dan hanya untuk hal yang spesifik (mis. prompt sistem di `GenerateGeminiReferralSummaryJob.php:35-56`).
- Jangan menyalin nilai secret, isi `.env`, atau screenshot ke dokumen mana pun.

## Higiene Mermaid

Semua diagram memakai Mermaid inline agar ter-render di GitHub, ter-diff di PR, dan bisa dibaca agent tanpa renderer.

1. Label yang mengandung spasi atau tanda baca wajib dikutip: `S1["Menunggu Persetujuan Siswa"]`.
2. Jangan pernah menaruh karakter `|` di dalam label.
3. Setiap diagram diawali komentar `%%` berisi file sumbernya.
4. ID node stabil dan dipakai ulang di semua diagram: `SISWA`, `BK`, `PSI`, `KEPSEK`, `WALI`, `TU`, `SUPER`, `SYS`.
5. Patuhi batas ukuran: ERD ≤ 10 entitas, sequence ≤ 5 partisipan, activity ≤ 15 node, dan tiap diagram di bawah ~40 baris agar diff-nya masih bisa dibaca.

## Resep verifikasi ulang

Jalankan ini sebelum mempercayai dokumen yang headernya sudah lama:

```bash
# 1. Apa yang berubah sejak dokumen terakhir diverifikasi?
git log --oneline <commit-di-header>..HEAD -- routes app/Enums database/migrations app/Policies

# 2. Endpoint masih cocok dengan 09-api-surface.md?
php artisan route:list --json

# 3. Enum masih cocok dengan 08-state-machines.md?
grep -rn 'case ' app/Enums

# 4. Tabel masih cocok dengan 07-data-model/?
ls database/migrations
```

Sebuah dokumen dianggap **kadaluarsa** bila commit di `verified:`-nya lebih lama daripada perubahan terakhir pada path di `scope=`-nya.

## Status verifikasi

| Dokumen | Commit | Tanggal | Keterangan |
|---|---|---|---|
| `README.md` | `266f860` | 2026-10-09 | |
| `01-glossary.md` | `266f860` | 2026-10-09 | |
| `02-actors-and-scoping.md` | `266f860` | 2026-10-09 | |
| `07-data-model/README.md` | `266f860` | 2026-10-09 | |
| `07-data-model/01-identity-and-tenancy.md` | `266f860` | 2026-10-09 | |
| `07-data-model/02-wellbeing-and-assessment.md` | `266f860` | 2026-10-09 | |
| `07-data-model/03-sharing-triage-and-counseling.md` | `266f860` | 2026-10-09 | |
| `07-data-model/04-referral-and-consent.md` | `266f860` | 2026-10-09 | |
| `07-data-model/05-content-and-engagement.md` | `266f860` | 2026-10-09 | |
| `00-product-overview.md` | `266f860` | 2026-10-09 | |
| `03-authorization.md` | `266f860` | 2026-10-09 | |
| `04-user-stories.md` | `266f860` | 2026-10-09 | |
| `05-user-flows.md` | `266f860` | 2026-10-09 | |
| `06-user-activities.md` | `266f860` | 2026-10-09 | |
| `08-state-machines.md` | `266f860` | 2026-10-09 | |
| `09-api-surface.md` | `266f860` | 2026-10-09 | |
| `10-architecture-and-integrations.md` | `266f860` | 2026-10-09 | |
| `11-environment-and-runbook.md` | `266f860` | 2026-10-09 | |
| `12-ui-contract.md` | `266f860` | 2026-10-09 | sisi desain dari Figma, di luar repo |
