# [BE-6.3] Store Final Counseling Outcome & Lock Records

## Overview

Provides a secure endpoint for counselors to log session summaries, close parent counseling records, set resolving verdicts, and complete associated incident reports. Once stored, records are locked as read-only; revisions automatically update the append-only version history.

---

## Endpoint Specification

### `POST /api/counseling-logs`

- **Authentication:** Required (JWT Bearer Token).
- **Controller Action:** `CounselingController@storeLog`.
- **Action Class:** `StoreCounselingLogAction`.

### Request Payload

All parameters are validated in the controller using PHP Enums for type safety:

```json
{
  "counseling_id": 4,
  "session_mode": "Tatap Muka",
  "clinical_notes": "Siswa menunjukkan tanda pemulihan pasca bimbingan intensif...",
  "resolution_status": "Bukan Kondisi Kritis (Red Zone)"
}
```

### Validation Rules

- `counseling_id`: `required | integer | exists:counselings,id`
- `session_mode`: `required | string | Rule::enum(CounselingMethod::class)`
- `clinical_notes`: `required | string`
- `resolution_status`: `required | string | Rule::enum(CounselingResolution::class)`

---

## Business Logic Workflow

The logic flow is encapsulated within `StoreCounselingLogAction` to isolate transaction side-effects:

1. **Authorize Request:** Invokes `CounselingPolicy@storeLog` ensuring only the specific assigned counselor for that session can submit.
2. **Log Outcome:** Inserts a record in `counseling_logs`. The model automatically encrypts `clinical_notes`.
3. **Lock Counseling Status:** Updates parent counseling session status to `selesai` (completed), set resolution and session method.
4. **Close Report Loop:** If the session is linked to a source report (`report_id`), the report's status is updated to `selesai` and the outcome summary is logged inside the report's `result` column, resolving the pipeline.
5. **Decouple Event:** Dispatches the `CounselingLogStored` event.

---

## Immutability & Audit Trail (Version History)

To prevent silent tampering of student medical records, a database listener handles updates dynamically.

### `CounselingLogObserver`

If a counselor edits `clinical_notes` on a stored record, the original decrypted data is automatically archived inside `counseling_log_histories`:

```php
public function updating(CounselingLog $counselingLog): void
{
    if ($counselingLog->isDirty('clinical_notes')) {
        CounselingLogHistory::create([
            'counseling_log_id' => $counselingLog->id,
            'clinical_notes' => $counselingLog->getOriginal('clinical_notes'),
            'updated_by' => Auth::id() ?? $counselingLog->counselor_id,
        ]);
    }
}
```

This secures an automated, transparent, and encrypted audit trail of edits.

---

## Authorization & Security

Role-based access is strictly managed via `app/Policies/CounselingPolicy.php`:

```php
public function storeLog(User $user, Counseling $counseling): bool
{
    return $counseling->counselor_id == $user->id;
}
```

*Students or unauthorized counselors receive a `403 Forbidden` response.*
