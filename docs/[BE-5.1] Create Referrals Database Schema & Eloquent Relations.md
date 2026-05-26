# [BE-5.1] Create Referrals Database Schema & Eloquent Relations

## Overview

A new table named `counselings` has been created to manage counseling sessions between students and counselors. The `counselor_id` is **dynamic** — it can reference either a **Teacher** or a **Psychologist** (both are records in the `users` table), making the table flexible for different counseling roles.

An SLA **cutdown timer** (`cutdown_at`) enforces response deadlines for assigned counselors.


## Table Structure

### `counselings`

| Column         | Type      | Description                                       |
| -------------- | --------- | ------------------------------------------------- |
| id             | bigint    | Primary key                                       |
| student_id     | bigint    | FK to `users` (the student receiving counseling)  |
| counselor_id   | bigint    | FK to `users` (teacher or psychologist)           |
| room           | string    | Room/location for the session (nullable)          |
| notes          | string    | Counselor's session notes (nullable)              |
| reason         | string    | Reason for counseling (nullable)                  |
| type           | enum      | `internal` or `external` (default: `internal`)    |
| resolution     | enum      | Resolution outcome (nullable)                     |
| method         | enum      | Session method (nullable)                         |
| status         | enum      | Counseling status (default: `dijadwalkan`)        |
| scheduled_at   | datetime  | Scheduled session date/time (nullable)            |
| cutdown_at     | datetime  | SLA deadline / cutdown timer (nullable)           |
| created_at     | timestamp | Created time                                      |
| updated_at     | timestamp | Updated time                                      |
| deleted_at     | timestamp | Soft delete timestamp                             |

## Status Values

| Status        | Description                    |
| ------------- | ------------------------------ |
| `dijadwalkan` | Scheduled (default)            |
| `menunggu`    | Waiting for counselor response |
| `selesai`     | Completed                      |
| `ditolak`     | Rejected/declined              |

Default: `dijadwalkan`

## Method Values

| Method        | Description    |
| ------------- | -------------- |
| `Tatap Muka`  | Offline/In-person |
| `Video Call`  | Online video call |
| `Chat`        | Chat-based session |

## Resolution Values

| Resolution                                         | Description                                   |
| -------------------------------------------------- | --------------------------------------------- |
| `Bukan Kondisi Kritis (Red Zone)`                  | Not a critical/red-zone condition             |
| `Bukan Kondisi Prioritas (Yellow Zone)`            | Not a priority/yellow-zone condition          |
| `Perlu Rujukan Professional`                       | Needs professional referral                   |

## Type Values

| Type       | Description                     |
| ---------- | ------------------------------- |
| `internal` | Handled within the school       |
| `external` | Referred to external party      |

## Dynamic Counselor Assignment

The `counselor_id` column references `users` without restriction to a specific role. This means:

- A **Teacher** (role `counselor`) can be assigned as counselor
- A **Psychologist** (role `psychologist`) can be assigned as counselor
- A **Counselor/Admin** with appropriate privileges can also act as counselor

This eliminates the need for separate tables for teacher-led vs. psychologist-led sessions.

## SLA Cutdown Timer

The `cutdown_at` column serves as a deadline for the counselor to respond or act on the session:

- Set when the session is created or transitions to a status requiring action
- Can be used to trigger escalations or notifications when the deadline passes
- Nullable — not all statuses require a cutdown

## Eloquent Relationships

### Counseling Model (`app/Models/Counseling.php`)

```php
public function student()
{
    return $this->belongsTo(User::class, 'student_id');
}

public function counselor()
{
    return $this->belongsTo(User::class, 'counselor_id');
}
```
## Status Flow

```text
dijadwalkan → menunggu → selesai
           ↘ ditolak
```

- A session starts as `dijadwalkan` (scheduled).
- When awaiting counselor action, it moves to `menunggu`.
- Once completed, it reaches `selesai`.
- If rejected or cancelled, it becomes `ditolak`.

## Additional Notes

- Uses `SoftDeletes` for audit trail and data safety.
- Enum classes (`CounselingStatus`, `CounselingMethod`, `CounselingResolution`) provide type-safe status, method, and resolution management.
- `counselor_id` is intentionally role-agnostic — validation of who can be assigned is handled at the application/authorization layer.
- The `cutdown_at` field is used in conjunction with a scheduled task/command to enforce SLA deadlines.
