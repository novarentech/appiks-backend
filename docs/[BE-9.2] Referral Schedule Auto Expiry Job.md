# [BE-9.2] Referral Schedule Auto Expiry Job

## Summary
The automated console command `referrals:expire-pending` has been implemented and scheduled to run every 15 minutes to automatically expire pending student booking schedules that have exceeded their 24-hour SLA window.

## Architectural Components

### 1. Console Command
- **`App\Console\Commands\ExpirePendingReferrals`**: Queries `BookingSchedule::expired()`, updates booking status to `expired`, reverts associated slot status to `available`, and dispatches the `BookingExpired` event inside database transactions.

### 2. Scheduler
- **`routes/console.php`**: `Schedule::command('referrals:expire-pending')->everyFifteenMinutes();`

## Final Verdict
**APPROVED** — Automated background job verified via console execution and unit/feature test suite.
