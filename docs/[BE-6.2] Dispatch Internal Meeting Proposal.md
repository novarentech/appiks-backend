# [BE-6.2] Dispatch Internal Meeting Proposal

## Overview

Provides an authenticated endpoint enabling counselors (Guru BK) to schedule a counseling session directly from a flagged high-risk student report (Red/Yellow Zone). This ensures high-risk cases are immediately channeled into formal clinical sessions.

---

## Endpoint Specification

### `POST /api/report/{report}/schedule-meeting`

- **Authentication:** Required (JWT Bearer Token).
- **Controller Action:** `ReportController@scheduleMeeting`.
- **Action Class:** `ScheduleReportCounselingAction`.

### Request Payload

All parameters are validated inline directly in the controller using native Laravel validation:

```json
{
  "proposed_date": "2026-06-15",
  "proposed_time": "10:00",
  "room": "Ruang Konseling BK 1",
  "notes": "Pertemuan awal menyusul hasil klasifikasi NLP"
}
```

### Validation Rules

- `proposed_date`: `required | date_format:Y-m-d`
- `proposed_time`: `required | date_format:H:i`
- `room`: `nullable | string | max:255`
- `notes`: `nullable | string`

---

## Business Logic Workflow

The creation and preparation of the counseling proposal are extracted into a dedicated Action class to maintain a lightweight, single-responsibility architecture.

1. **Authorize Request:** Invokes `ReportPolicy@scheduleMeeting` to confirm that the requesting user is the student's assigned counselor, or has admin/super/headteacher credentials.
2. **Combine Schedule:** Merges `proposed_date` and `proposed_time` into a single Carbon-compatible string.
3. **Persist Schedule:** Inserts a new record into `counselings` with:
   - `student_id` = Target student from the incident report.
   - `counselor_id` = Authenticated counselor ID.
   - `source_type` = `nlp_incident`.
   - `report_id` = ID of the source report.
   - `status` = `menunggu` (waiting for student confirmation/attendance).
4. **Decouple Event:** Dispatches the `CounselingScheduled` event to cleanly isolate side-effects (such as push/in-app notifications).

---

## Authorization & Security

Role-based access control is managed strictly via `app/Policies/ReportPolicy.php`:

```php
public function scheduleMeeting(User $user, Report $report): bool
{
    return $user->role === UserRole::SUPER->value
        || $user->role === UserRole::ADMIN->value
        || $user->role === UserRole::HEADTEACHER->value
        || ($user->role === UserRole::COUNSELOR->value && $report->user->counselor_id == $user->id);
}
```

*Student role access attempts will receive a `403 Forbidden` response.*
