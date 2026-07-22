# [BE-2.2] Submit Schedule Booking API

## User Story
As a Student, I need an API endpoint to submit a slot booking for my psychologist referral, so the system can lock the slot tentatively and start the 24-hour SLA countdown for psychologist confirmation.

---

## Context & Dependencies

- Depends on `BE-2.1` (slots browsing endpoints must exist).
- Depends on `BE-13.1` (`psychologist_slots` table must exist).
- Depends on `BE-SEED-1` + `BE-SEED-2` (seed data for testing).
- Requires a new table: `booking_schedules` (migration created in this ticket).
- The "referral" is the existing `counselings` record with `type = 'external'`. No separate referrals table is needed.

---

## New Table: `booking_schedules`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `counseling_id` | bigint | FK to `counselings` (cascade on delete) |
| `slot_id` | bigint | FK to `psychologist_slots` (cascade on delete) |
| `student_id` | bigint | FK to `users` (cascade on delete) |
| `status` | enum | `menunggu_konfirmasi`, `terkonfirmasi`, `ditolak`, `expired` — default `menunggu_konfirmasi` |
| `deadline_at` | datetime | SLA expiry = `created_at + 24 hours` (not nullable) |
| `location` | string | Filled by psychologist after confirmation (nullable) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |
| `deleted_at` | timestamp | Soft delete |

**New Enum:** `app/Enums/BookingStatus.php`
```
menunggu_konfirmasi | terkonfirmasi | ditolak | expired
```

**New Model:** `app/Models/BookingSchedule.php`
- `belongsTo(Counseling::class, 'counseling_id')`
- `belongsTo(PsychologistSlot::class, 'slot_id')`
- `belongsTo(User::class, 'student_id')`
- `scopePending` → status = `menunggu_konfirmasi`
- `scopeExpired` → status = `menunggu_konfirmasi` AND `deadline_at <= now()`
- Cast: `status` → `BookingStatus::class`, `deadline_at` → `'datetime'`

**Reverse relations to add:**
- `PsychologistSlot` → `bookingSchedule()` hasOne `BookingSchedule`
- `Counseling` → `bookingSchedule()` hasOne `BookingSchedule`

---

## Endpoint — Submit Booking

**Route:** `POST /api/student/bookings`  
**Auth:** JWT required | Role: `student`  
**Controller:** `StudentBookingController@store`  
**Action Class:** `CreateBookingScheduleAction`  
**Form Request:** `StoreBookingRequest` (2 fields — inline validation acceptable per RULE #3, but extracted for testability)

### Request Payload
```json
{
  "counseling_id": 1,
  "slot_id": 2
}
```

### Validation Rules
- `counseling_id`: required | integer | exists:counselings,id
- `slot_id`: required | integer | exists:psychologist_slots,id

### Business Logic (inside `CreateBookingScheduleAction`)
1. Verify `counseling.student_id` = authenticated user id → 403 if not.
2. Verify `counseling.type = 'external'` → 403 if internal session.
3. Verify slot's `psychologist_id` matches `counseling.psychologist_id` → 422 if mismatch.
4. **Race condition guard:** `DB::transaction()` + re-fetch slot with `lockForUpdate()`.
   - If slot `status != 'available'` → throw `ConflictHttpException` (HTTP 409).
5. Update `PsychologistSlot::status` → `tentative`.
6. Create `BookingSchedule`:
   - `counseling_id`, `slot_id`, `student_id` = auth id
   - `status` = `menunggu_konfirmasi`
   - `deadline_at` = `now()->addHours(24)`
7. Dispatch event `BookingScheduleCreated` (no Listener in this ticket).

### Response (success — HTTP 200)
```json
{
  "success": true,
  "message": "Jadwal berhasil diajukan. Menunggu konfirmasi psikolog.",
  "data": {
    "booking_id": 1,
    "status": "menunggu_konfirmasi",
    "deadline_at": "2026-07-29T08:00:00+07:00",
    "slot": {
      "date": "2026-07-28",
      "time_range": "08:00 - 09:00 WIB"
    }
  }
}
```

### Response (conflict — HTTP 409)
```json
{
  "success": false,
  "message": "Slot ini sudah diambil siswa lain. Silakan pilih slot lain."
}
```

---

## Endpoint — Get Booking Status Detail

**Route:** `GET /api/student/bookings/{booking}`  
**Auth:** JWT required | Role: `student`  
**Controller:** `StudentBookingController@show`

### Business Rules
- Only the student who owns the booking (`student_id` = auth user) can access it → 403 otherwise.
- Return booking detail including slot time, psychologist info, and `location` (only populated after psychologist confirms).

### Response
```json
{
  "success": true,
  "message": "Booking detail retrieved.",
  "data": {
    "booking_id": 1,
    "status": "menunggu_konfirmasi",
    "psychologist_name": "Dr. Sarah Wijaya, M.Psi., Psikolog",
    "facility_name": "Puskesmas Kec. Menteng",
    "counselor_name": "Sri Wahyuni, S.Pd",
    "time_slot_label": "08:00 - 09:00 WIB",
    "date_formatted": "Senin, 28 Juli 2026",
    "location": null,
    "deadline_at": "2026-07-29T08:00:00+07:00",
    "created_at_formatted": "Senin, 28 Juli 2026, 08:00 WIB"
  }
}
```

---

## New Event: `BookingScheduleCreated`
- Location: `app/Events/BookingScheduleCreated.php`
- Carries the `BookingSchedule` model.
- No Listener needed in this ticket. Future tickets will attach (notifications, AI summary).

---

## DoD
- `booking_schedules` table created and migration runs cleanly.
- `POST /api/student/bookings` with valid payload → HTTP 200, slot status in DB changes to `tentative`, `deadline_at` = now + 24h.
- `POST /api/student/bookings` with same `slot_id` a second time → HTTP 409.
- `GET /api/student/bookings/{id}` → HTTP 200 with correct detail.
- Non-owner student receives HTTP 403.
- `lockForUpdate()` used inside `DB::transaction()`.
- `BookingScheduleCreated` event dispatched on success.
