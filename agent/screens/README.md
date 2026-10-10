# ⚠️ These are aspirational UI specs, not a description of the system

The 33 files in this folder were written **before** the features were built, from Figma designs. They describe an API and a schema that **were never implemented**.

Known divergences from the shipped backend:

| These specs say | Reality |
|---|---|
| `POST /api/v1/siswa/mood-checkin` and other `/api/v1/siswa/*` paths | No endpoint is versioned. The real path is `POST /api/mood_record` |
| Table `mood_checkins`, field `mood_note`, 5 moods including `cemas` | Table `mood_records`, no note field, 4 moods (`happy`, `neutral`, `sad`, `angry`) |
| Tables `referrals`, `data_access_consents` | Never created. Referral is folded into `counselings` + `counseling_consents` + `booking_schedules` |
| AES-256-GCM encryption | Eloquent's `encrypted` cast on two columns |

Keep these files — they are the record of what was asked for, and the `[Architect Note]` sections still carry useful intent. Just **do not use them as the source of truth** for table names, endpoint paths, or field names.

**For as-built facts, start at [`docs/foundation/README.md`](../../docs/foundation/README.md)** — in particular [`09-api-surface.md`](../../docs/foundation/09-api-surface.md) for endpoints and [`07-data-model/`](../../docs/foundation/07-data-model/) for the schema. The mapping between Figma screens and backend values is in [`12-ui-contract.md`](../../docs/foundation/12-ui-contract.md).
