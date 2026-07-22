# [BE-2.1] Fetch Available Psychologist Slots API

## User Story
As a Student, I need API endpoints to browse available consultation dates and time slots for the psychologist assigned to my referral, so I can pick a schedule that fits me.

---

## Context & Dependencies

- Depends on `BE-13.1` (`psychologist_slots` table must exist).
- Depends on `BE-SEED-2` (seed slots must exist for testing).
- Does NOT create or modify any database tables.
- The referral record is sourced from the existing `counselings` table (`type = 'external'`, with a `psychologist_id`). There is no separate `referrals` table at this stage — a `counseling` with `type = 'external'` IS the referral.

---

## Endpoints

### Endpoint 1 — Get Available Dates

**Route:** `GET /api/student/referrals/{counseling}/available-dates`  
**Auth:** JWT required | Role: `student`  
**Controller:** `StudentBookingController@availableDates`

#### Business Rules
- The `{counseling}` must be of `type = 'external'` and `student_id` must match the authenticated user.
- Only return dates from `psychologist_slots` where:
  - `psychologist_id` matches the counseling's `psychologist_id` (via `psychologist_profiles`)
  - `slot_date >= today + 2 days` (H+2 window per AND-2 spec)
  - `status = 'available'`
- Group slots by `slot_date`, count per day → `available_slots_count`.
- Return the earliest available date for the banner string.

#### Response
```json
{
  "success": true,
  "message": "Available consultation dates retrieved.",
  "data": {
    "psychologist": {
      "name": "Dr. Sarah Wijaya, M.Psi., Psikolog",
      "facility_name": "Puskesmas Kec. Menteng",
      "specialization": "Psikologi Klinis Anak & Remaja"
    },
    "earliest_available_date": "2026-07-28",
    "available_dates": [
      {
        "date_raw": "2026-07-28",
        "date_formatted": "Senin, 28 Juli 2026",
        "available_slots_count": 4,
        "slot_label": "4 slot tersedia",
        "is_selectable": true
      }
    ]
  }
}
```

---

### Endpoint 2 — Get Time Slots for a Specific Date

**Route:** `GET /api/student/referrals/{counseling}/available-slots?date=YYYY-MM-DD`  
**Auth:** JWT required | Role: `student`  
**Controller:** `StudentBookingController@availableSlots`

#### Business Rules
- Same ownership guard as Endpoint 1.
- `date` query param is required, format `Y-m-d`.
- Return `psychologist_slots` for the psychologist on that date where `status = 'available'`.
- A slot is `is_available: false` if it already has an active booking in `booking_schedules` with `status IN ('menunggu_konfirmasi', 'terkonfirmasi')`.
- Times must be returned in WIB format string (`08:00 - 09:00 WIB`).

#### Response
```json
{
  "success": true,
  "message": "Available time slots retrieved.",
  "data": {
    "selected_date": "2026-07-28",
    "selected_date_formatted": "Senin, 28 Juli 2026",
    "time_slots": [
      {
        "slot_id": 1,
        "time_range": "08:00 - 09:00 WIB",
        "is_available": true
      }
    ]
  }
}
```

---

## DoD
- Both endpoints respond with correct data from seed.
- Slots dated less than H+2 are excluded from Endpoint 1.
- A slot already booked by another student appears with `is_available: false` in Endpoint 2.
- Non-owner students receive HTTP 403.
