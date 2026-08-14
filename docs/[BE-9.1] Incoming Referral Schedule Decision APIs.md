# [BE-9.1] Incoming Referral Schedule Decision APIs

## Summary
The API endpoints for Partner Psychologists to view pending incoming student referral schedules (`GET /api/psychologist/referrals/pending`) and submit confirmation/rejection decisions (`PATCH /api/psychologist/referrals/{booking}/decide`) have been implemented and documented.

## Architectural Components

### 1. Authorization & Policy
- **`App\Policies\BookingSchedulePolicy`**: Method `decide(User $user, BookingSchedule $booking)` ensures only the partner psychologist who owns the slot referenced by the booking can decide on the referral.
- Registered in `AppServiceProvider`: `Gate::policy(BookingSchedule::class, BookingSchedulePolicy::class)`.

### 2. Form Request & Validation
- **`App\Http\Requests\DecideReferralRequest`**: Validates `action` (`in:confirm,reject`) and enforces `required_if:action,reject` for `reject_reason`.

### 3. API Resource & Scramble Documentation
- **`App\Http\Resources\BookingScheduleResource`**: Custom JsonResource mapping loaded relations (`student`, `counseling`, `slot`). Enables Scramble OpenAPI generator to extract the full JSON schema for referrals in the interactive API documentation.
- **`App\Http\Controllers\PsychologistReferralController`**: Annotate with `#[Group('Psychologist')]` and return `BookingScheduleResource` collections / items via `ApiResponder` trait methods.

## Endpoints Summary

| Method | Path | Auth Guard | Description | Status Codes |
|--------|------|------------|-------------|--------------|
| `GET` | `/api/psychologist/referrals/pending` | `auth:api` (Psychologist) | List pending student referral schedules | 200 OK, 403 Forbidden |
| `PATCH` | `/api/psychologist/referrals/{booking}/decide` | `auth:api` (Psychologist) | Confirm or reject a pending referral schedule | 200 OK, 403 Forbidden, 422 Unprocessable |

## Final Verdict
**APPROVED** — Full resource schema display enabled for Scramble API docs.
