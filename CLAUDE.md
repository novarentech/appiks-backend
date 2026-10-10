# CLAUDE.md

APPIKS.ID by Novaren Tech — a school mental-health monitoring and intervention platform for Indonesian high schools (SMA, kelas X/XI/XII). This repo is the **API-only** backend. The Next.js 16 web app and the mobile client live in separate repos.

Laravel 12 · PHP 8.4 · MySQL (`appiks`) · JWT via `tymon/jwt-auth` · `QUEUE_CONNECTION=database` · `timezone=Asia/Jakarta` · `locale=id`. Dev machine here is Windows with PowerShell.

## Coding standards

@agent/RULE_OF_ARCHITECT.md

Frontend integration contract: [`agent/technical-reference.md`](agent/technical-reference.md).

## Where to look things up

Start at [`docs/foundation/README.md`](docs/foundation/README.md). Read only the slice you need:

| Question | Document |
|---|---|
| What does this term mean? | [`docs/foundation/01-glossary.md`](docs/foundation/01-glossary.md) |
| Who are the actors, who sees what? | [`docs/foundation/02-actors-and-scoping.md`](docs/foundation/02-actors-and-scoping.md) |
| What is the shape of the data? | [`docs/foundation/07-data-model/`](docs/foundation/07-data-model/) |
| What are the valid status transitions? | [`docs/foundation/08-state-machines.md`](docs/foundation/08-state-machines.md) |
| What endpoints exist and who may call them? | [`docs/foundation/09-api-surface.md`](docs/foundation/09-api-surface.md), plus Scramble at `/docs` for payload schemas |
| How is authorization enforced? | [`docs/foundation/03-authorization.md`](docs/foundation/03-authorization.md) |
| How does a case move between people? | [`docs/foundation/05-user-flows.md`](docs/foundation/05-user-flows.md) |
| What can each role do? | [`docs/foundation/06-user-activities.md`](docs/foundation/06-user-activities.md) · [`04-user-stories.md`](docs/foundation/04-user-stories.md) |
| How do I run it, what demo accounts exist? | [`docs/foundation/11-environment-and-runbook.md`](docs/foundation/11-environment-and-runbook.md) |
| What does the UI call this value? | [`docs/foundation/12-ui-contract.md`](docs/foundation/12-ui-contract.md) |
| How is a case's history tracked? | [`docs/foundation/13-case-timeline.md`](docs/foundation/13-case-timeline.md) |

## Do not treat these as truth

`docs/tasks/**` and `agent/screens/**` are **archives of design intent**, not documentation of the system. They describe endpoints (`/api/v1/siswa/*`) and tables (`mood_checkins`, `referrals`, `data_access_consents`) that were never built. Use `docs/foundation/` for as-built facts.

## Domain invariants — do not break these

1. **Questionnaire answers are never persisted.** Answers become a letter key; only the AI interpretation is cached, in `ai_generated`. There is no per-student assessment history.
2. **The referral payload and AI summary are hard-gated on `ConsentStatus::GRANTED`**, and each data module is gated on its own scope.
3. **The AI must not diagnose**, label disorders, or recommend treatment. The system prompt enforces this and ends with a mandatory disclaimer.
4. **A counselor may reply to a curhat once only.**
5. **Creating a report requires `last_mood` to be `sad` or `angry`.**
6. **`counseling_log_histories` is append-only** — it is the clinical audit trail.
7. **`case_events` is append-only** — it is the case timeline, and the only place the *order* of events is kept. No runtime code may update or delete a row. Record new transitions through `RecordCaseEvent`, never with an observer: four write paths in the chain use query-builder mass updates and fire no model events.

## Naming traps

- `assesment_logs` is misspelled **on purpose and consistently**. Keep it.
- `ReportStatus` is shared by `reports.status` **and** `sharings.status` — and is cast to the enum on `Sharing` but **not** on `Report`.
- Comparing a cast enum column against `Enum::CASE->value` is always false. Compare against the case.
- `counselings.psychologist_id` → `users.id`, but `psychologist_slots.psychologist_id` → `psychologist_profiles.id`.
- `mood_records.recorded` (not `date`). `cutdown_for_report` is a deadline, not a duration.
- Guru Wali = `teacher` = `mentor_id`. Three names, one thing.
- `Priority` enum exists but is unused; the columns are plain MySQL enums.

## Authorization lives in four places

There is no `app/Http/Middleware` and no permission package. Before assuming an endpoint is open, check **all four**: the single `dashboard-data` Gate in `AppServiceProvider`, inline `Gate::allowIf()` calls in controllers, the 11 policies in `app/Policies` (many with non-standard ability names), and `FormRequest::authorize()`.

## Operational rules

- **Never** run `php artisan migrate:fresh` or hit `GET /reset` against a shared or deployed database — both wipe everything.
- Use `php artisan migrate:fresh-backup` locally: it preserves `gemini_api_token` and `ai_generated`, which cost real API quota to rebuild.
- NLP analysis and AI summaries are queued jobs. Without `php artisan queue:work` they silently never run.
- The scheduler drives two SLAs: `cutdown:clear` (every 10 min) and `referrals:expire-pending` (every 15 min). Without `schedule:work`, bookings never expire.
- Mail driver defaults to `log`. Notifications are not actually delivered locally.
- Never paste secrets or `.env` values into documentation or commits.

## Tests

There is **no `tests/` directory.** It was deleted in commit `266f860` (4 Pest feature files plus `Pest.php` and `TestCase.php`), while `phpunit.xml` still references `tests/Unit` and `tests/Feature`. Recover with `git checkout 266f860^ -- tests` if needed. **Never claim tests pass.**

## Keep documentation in step with code

Drift has already happened once in this repo. If you change the left column, update the right one in the same change:

| If you change… | Update… |
|---|---|
| `database/migrations/**` | the matching `docs/foundation/07-data-model/` slice and its table index |
| `routes/api.php`, `routes/web.php` | `docs/foundation/09-api-surface.md` |
| `app/Enums/**` | `docs/foundation/08-state-machines.md` (and `01-glossary.md` for a new term) |
| `app/Policies/**`, any `Gate::` or `authorize()` | `docs/foundation/03-authorization.md` |
| `app/Jobs/**`, `app/Observers/**`, `app/Listeners/**` | `docs/foundation/10-architecture-and-integrations.md` |
| `app/Enums/CaseEventType.php`, `app/Actions/RecordCaseEvent.php`, `app/Support/CaseTimelineVisibility.php` | `docs/foundation/13-case-timeline.md` |
| a new state transition anywhere in the case chain | record it via `RecordCaseEvent` **and** add its row to `13-case-timeline.md` |
| feature behaviour | the relevant row in `docs/foundation/04-user-stories.md` |

Also bump the `verified:` header comment at the top of every document you touch.
