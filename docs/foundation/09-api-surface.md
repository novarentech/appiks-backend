# API Surface

<!-- verified: branch=dev commit=working-tree date=2026-10-10 scope=routes/api.php,routes/web.php,app/Http/Controllers -->
> **Verified against:** `dev` working tree · 2026-10-10 — generated from `php artisan route:list --json`
> **Sources:** [`routes/api.php`](../../routes/api.php) · [`routes/web.php`](../../routes/web.php) · [`app/Http/Controllers/`](../../app/Http/Controllers/) (26 controllers)

**164 routes total: 157 under `/api`, 7 elsewhere.** Exactly two middleware contexts exist — public, and `auth:api`. **No route carries a role check**; see [`03-authorization.md`](03-authorization.md) for where role decisions actually live.

> **Non-goal: this document does not describe request or response bodies.** Those are generated at runtime by Dedoc Scramble and are always current:
> - `GET /docs` — browsable UI with Try-It
> - `GET /api/docs.json` — OpenAPI JSON
>
> Duplicating payload schemas here would create a second source of truth that rots. The purpose of this page is the part Scramble cannot tell you: **who may call each endpoint, and where that is enforced.**

Response envelope for every endpoint is `{success, message, data}` via [`ApiResponder`](../../app/Traits/ApiResponder.php). List endpoints follow the pagination-with-flat-array-fallback rule in [`agent/RULE_OF_ARCHITECT.md`](../../agent/RULE_OF_ARCHITECT.md) §4. Key naming is mixed: legacy endpoints use camelCase, new ones `snake_case`.

Legend for **Enforced by**: `Gate` = inline `Gate::allowIf` · `Policy::ability` = `Gate::authorize` against a policy · `Request` = `FormRequest::authorize()` · `abort` = bare `abort(403)` in the controller · **`NONE`** = no check found.

---

## Public surface (outside `auth:api`)

Nine API endpoints require no token.

| Method | URI | Controller@action | Note |
|---|---|---|---|
| GET | `api` | closure | Returns `"OK"` |
| POST | `api/login` | `AuthController@login` | Credentials are `username` + `password` |
| GET | `api/tag` | `TagController@index` | Content tag list |
| GET | `api/province` | `LocationController@province` | Geo cascade |
| GET | `api/city/{province}` | `LocationController@city` | |
| GET | `api/district/{city}` | `LocationController@district` | |
| GET | `api/village/{district}` | `LocationController@village` | |
| GET | `api/user/bulk/template` | `UserController@getTemplate` | ⚠️ **Downloads the bulk-import Excel template without authentication.** Almost certainly unintended. |
| GET | `api/coba` | `QuestionnaireController@coba` | `[DEAD]` — the method does not exist on the controller; calling this throws |

## Non-API routes

| Method | URI | Target | Note |
|---|---|---|---|
| GET | `/` | redirect | → `docs` |
| GET | `docs` | Scramble UI | |
| GET | `api/docs.json` | Scramble OpenAPI | Registered as a closure |
| GET | `up` | health check | Laravel default |
| GET\|PUT | `storage/{path}` | closure | Local filesystem `serve` route. `PUT` is part of the framework default; it is not an upload endpoint for clients. |
| GET | `sanctum/csrf-cookie` | Sanctum | Dead — the API guard is JWT; `laravel/sanctum` is installed but unused |
| GET | **`reset`** | closure | 🛑 **Runs `migrate:fresh --seed --seeder=DemoCaseSeeder`** — wipes the entire database. Protected only by HTTP Basic auth defaulting to `novaren` / `secret`. See the danger note at the end of this page. |

---

## Auth & profile

| Method | URI | Action | Enforced by | Actors |
|---|---|---|---|---|
| GET | `api/me` | `AuthController@me` | `auth:api` | all |
| POST | `api/logout` | `AuthController@logout` | `auth:api` | all |
| POST | `api/refresh` | `AuthController@refresh` | `auth:api` | all |
| GET | `api/check-username` | `AuthController@checkUsername` | `auth:api` | all |
| PATCH | `api/profile` | `UserController@profile` | `Request` — `!$user->verified` | all, once only |
| PATCH | `api/edit-profile` | `UserController@editProfile` | **NONE beyond `auth:api`** | all — ⚠️ mass-assignment escalation, see [`03-authorization.md`](03-authorization.md) |

`POST /api/refresh` exists even though [`agent/RULE_OF_ARCHITECT.md`](../../agent/RULE_OF_ARCHITECT.md) §5 says no refresh mechanism is to be implemented.

## User management

| Method | URI | Action | Enforced by | Actors |
|---|---|---|---|---|
| POST | `api/user/admin` | `UserController@adminCreate` | `Request` | super |
| POST | `api/dashboard/users` | `UserController@store` | `Request` | admin |
| POST | `api/user/student` | `UserController@studentCreate` | `Request` | admin |
| POST | `api/user/bulk` | `UserController@bulkCreate` | `Request` | admin |
| PATCH | `api/edit-user/{user}` | `UserController@edit` | `Gate` | admin (same school), super |
| DELETE | `api/user/{user}` | `UserController@destroy` | `Gate` | admin (same school, not super/admin), super (admins only) |
| GET | `api/dashboard/users` | `UserController@getUsers` | — | non-student |
| GET | `api/dashboard/users/{username}` | `UserController@getUserDetail` | — | |
| GET | `api/dashboard/users/type/{type}` | `UserController@getUsersByType` | `Gate` — `role != student` | non-student; super sees all schools |
| GET | `api/dashboard/student` | `UserController@getStudents` | — | teacher sees own mentees |
| GET | `api/dashboard/latest-user` | `UserController@getLatestUser` | — | |
| GET | `api/dashboard/today-user` | `UserController@getTodayUser` | — | |

## Schools & rooms

| Method | URI | Action | Enforced by | Actors |
|---|---|---|---|---|
| GET | `api/school` | `SchoolController@index` | `Policy::viewAny` | super |
| POST | `api/school` | `SchoolController@store` | `Policy::create` | super |
| GET | `api/school/{school}` | `SchoolController@show` | `Policy::view` | super, or own school |
| PUT\|PATCH | `api/school/{school}` | `SchoolController@update` | `Policy::update` | super |
| DELETE | `api/school/{school}` | `SchoolController@destroy` | `Policy::delete` | super |
| GET | `api/school/me` | `SchoolController@me` | — | all |
| GET | `api/room` | `RoomController@index` | `Gate::dashboard-data` | non-student |
| POST | `api/room` | `RoomController@store` | `Request` | admin |
| GET | `api/room/{room}` | `RoomController@show` | `Gate::dashboard-data` | non-student — ⚠️ **not school-scoped** |
| PUT\|PATCH | `api/room/{room}` | `RoomController@update` | `Gate` | admin, same school |
| DELETE | `api/room/{room}` | `RoomController@destroy` | `Gate` | admin, same school |
| GET | `api/room/level` | `RoomController@getLevel` | `Gate::dashboard-data` | non-student |
| GET | `api/room/level/{level}` | `RoomController@byLevel` | `Gate::dashboard-data` | non-student |
| GET | `api/room/school/{school}` | `RoomController@roomOfSchool` | `Gate` | super |
| GET | `api/room-student-count` | `RoomController@roomStudentCount` | `Gate::dashboard-data` | non-student |
| GET | `api/dashboard/room-count` | `RoomController@getRoomCount` | — | |

## Mood records

| Method | URI | Action | Enforced by | Actors |
|---|---|---|---|---|
| POST | `api/mood_record` | `MoodRecordController@store` | `Request` | student, once/day |
| GET | `api/mood_record` | `@index` | — | |
| GET | `api/mood_record/{mood_record}` | `@show` | — | |
| GET | `api/mood_record/check` | `@check` | — | student |
| GET | `api/mood_record/today` | `@today` | — | student |
| GET | `api/mood_record/streaks` | `@streaks` | — | student |
| GET | `api/mood_record/recap/{month}` | `@recapPerMonth` | `Policy::recapPerMonth` | student |
| GET | `api/mood-record/student/{user}` | `@recordsOfStudent` | — | |
| GET | `api/mood-record/pattern/{user}/{type}` | `@moodHistory` | `Policy::viewHistory` | own counselor, own mentor, super |
| GET | `api/mood-trends/{school}/{type}` | `@getMoodTrendSchool` | `Policy::viewSchoolTrend` | super |
| GET | `api/dashboard/mood-trends` | `@getMoodTrend` | `Policy::viewSchoolTrend` | super |
| GET | `api/dashboard/mood-graph` | `@getMoodGraph` | `Gate::dashboard-data` | non-student |
| GET | `api/dashboard/mood-statistics` | `@moodStatistics` | — | `[DEAD]` — method does not exist |
| GET | `api/mood_record/export/today` | `@exportToday` | `Policy::export` | teacher, counselor |
| GET | `api/mood_record/export/{username}/weekly` | `@exportWeekly` | `Policy::export` | teacher, counselor |
| GET | `api/mood_record/export/{username}/monthly` | `@exportMonthly` | `Policy::export` | teacher, counselor |

`{type}` on the pattern endpoint is `weekly` or `monthly`.

## Questionnaire

| Method | URI | Action | Enforced by | Actors |
|---|---|---|---|---|
| GET | `api/questionnaire` | `@getAllQuestionnaires` | — | student — bank chosen by today's mood |
| POST | `api/questionnaire/{type}` | `@analyzeQuestionnaire` | `Gate` + `Request` | student. `{type}` ∈ `secure` \| `insecure` |

## Self-help

| Method | URI | Action | Enforced by | Actors |
|---|---|---|---|---|
| POST | `api/self-help/daily-journaling` | `@createDaily` | `Gate` | student |
| POST | `api/self-help/gratitude-journaling` | `@createGratitude` | `Gate` | student |
| POST | `api/self-help/grounding-technique` | `@createGrounding` | `Gate` | student |
| POST | `api/self-help/sensory-relaxation` | `@createSensory` | `Gate` | student |
| GET | `api/self-help/{type}/{user}` | `@getByType` | `Gate` | **teacher only**, and only for own mentees |

Note the read endpoint excludes counselors — a Guru BK cannot see a student's journals.

## Gamification (Cirrus)

| Method | URI | Action | Enforced by | Actors |
|---|---|---|---|---|
| GET | `api/cirrus` | `GameController@cirrus` | `Gate` | student |
| POST | `api/buy` | `GameController@buy` | `Gate` | student |
| POST | `api/claim` | `GameController@claim` | `Gate` | student |

## Sharing (curhat)

| Method | URI | Action | Enforced by | Actors |
|---|---|---|---|---|
| POST | `api/sharing` | `@store` | `Policy::create` | student |
| GET | `api/sharing` | `@index` | — | counselor — filters `room`, `status`, `priority` |
| GET | `api/sharing/{sharing}` | `@show` | `Policy::view` | author, or author's counselor |
| GET | `api/sharing/student/{user}` | `@sharingOfStudent` | `Policy::viewStudentSharing` | super |
| PATCH | `api/sharing/ack/{sharing}` | `@ack` | — | counselor — sets `acknowledged_at` |
| PATCH | `api/sharing/acknowledge/{sharing}` | `@acknowledge` | — | counselor — three-way follow-up decision |
| PATCH | `api/sharing/reply/{sharing}` | `@reply` | `Request` — `Policy::update` **and** no existing reply | counselor, once only |
| PATCH | `api/sharing/false-positive/{sharing}` | `@falsePositive` | `Policy::falsePositive` | counselor, only while `Belum Ditinjau` |
| GET | `api/dashboard/sharing-count` | `@getSharingCount` | `Policy::viewGraph` | counselor, super |
| GET | `api/notification/latest-sharing` | `@latestOfStudent` | — | student — latest 2 |

## Reports

| Method | URI | Action | Enforced by | Actors |
|---|---|---|---|---|
| POST | `api/report` | `@store` | `Policy::create` | student with mood `sad`/`angry` |
| GET | `api/report` | `@index` | — | |
| GET | `api/report/{report}` | `@show` | `Policy::view` | author, or author's counselor |
| GET | `api/report/student/{user}` | `@reportOfStudent` | `Policy::viewStudentReports` | super |
| PATCH | `api/report/confirm/{report}` | `@confirm` | `Request` | `[DEAD]` — guard compares against `'menunggu'` |
| PATCH | `api/report/reschedule/{report}` | `@reschedule` | `Request` | `[DEAD]` — guard compares against `'disetujui'` |
| PATCH | `api/report/close/{report}` | `@close` | `Request` | `[DEAD]` — same legacy vocabulary |
| PATCH | `api/report/cancel/{report}` | `@cancel` | `Request` | `[DEAD]` — reuses `CloseReportRequest` |
| POST | `api/report/{report}/schedule-meeting` | `@scheduleMeeting` | `Policy::scheduleMeeting` | super, admin, headteacher, assigned counselor |
| GET | `api/dashboard/report-count` | `@getReportCount` | — | |
| GET | `api/dashboard/report-graph` | `@getReportGraph` | `Policy::viewGraph` | counselor, super, headteacher |
| GET | `api/notification/latest-report` | `@latestOfStudent` | `Policy::viewLatest` | student |

See [`08-state-machines.md`](08-state-machines.md) for why four of these are unreachable.

## Counseling

| Method | URI | Action | Enforced by | Actors |
|---|---|---|---|---|
| POST | `api/counseling` | `@store` | `Request` | counselor |
| GET | `api/counseling` | `@index` | inline `role == student` check | student, own records only |
| GET | `api/counseling/{counseling}` | `@show` | **NONE** | ⚠️ any authenticated user, any id |
| POST | `api/counseling-logs` | `@storeLog` | `Policy::storeLog` | assigned counselor |
| POST | `api/counseling/{counseling}/consent` | `@sendConsent` | `Policy::storeLog` | assigned counselor |
| PATCH | `api/counseling/{counseling}/cancel` | `@cancel` | `Policy::manageSchedule` | assigned counselor — status must not be terminal |
| PATCH | `api/counseling/{counseling}/repropose` | `@repropose` | `Policy::manageSchedule` | assigned counselor — status must be `ditolak` |
| PATCH | `api/student/counselings/{counseling}/acknowledge` | `@acknowledge` | `Policy::acknowledge` | the counseling's student, while it is awaiting their response |

## Student app surface

| Method | URI | Action | Enforced by |
|---|---|---|---|
| GET | `api/student/dashboard/widgets` | `StudentDashboardController@getWidgets` | — |
| GET | `api/student/counselings` | `StudentDashboardController@getCounselings` | — |
| GET | `api/student/counselings/{counseling}/consent` | `StudentConsentController@show` | `Policy::viewStudent` — student, psychologist, or counselor on that counseling |
| PATCH | `api/student/consents/{consent}` | `StudentConsentController@update` | `Policy::update` — the consent's student |
| GET | `api/student/referrals/{counseling}/available-dates` | `StudentBookingController@availableDates` | controller check |
| GET | `api/student/referrals/{counseling}/available-slots` | `StudentBookingController@availableSlots` | controller check |
| POST | `api/student/bookings` | `StudentBookingController@store` | controller — own counseling, `type=external` |
| GET | `api/student/bookings/{booking}` | `StudentBookingController@show` | controller check |

## Psychologist surface

| Method | URI | Action | Enforced by |
|---|---|---|---|
| GET | `api/psychologist/slots` | `PsychologistSlotController@index` | `Policy::manage` |
| POST | `api/psychologist/slots` | `@store` | `Policy::manage` — supports weekly `repeat` up to a year |
| DELETE | `api/psychologist/slots/{slot}` | `@destroy` | `Policy::delete` — own slot, no active booking |
| GET | `api/psychologist/referrals` | `PsychologistReferralController@index` | `abort` — filters `search`, `status`, `priority`, `batas_waktu`, `per_page` |
| GET | `api/psychologist/referrals-overview` | `@overview` | `abort` |
| GET | `api/psychologist/referrals/pending` | `@pending` | `abort` |
| PATCH | `api/psychologist/referrals/{booking}/decide` | `@decide` | `Policy::decide` — `action` ∈ `confirm` \| `reschedule` \| `reject` |
| GET | `api/psychologist/referrals/{counseling}/summary` | `PsychologistSummaryController@getSummary` | `abort` + assigned + consent granted |
| POST | `api/psychologist/referrals/{counseling}/feedback` | `@storeFeedback` | same |
| GET | `api/psychologist/recap/{counseling}/monthly/mood` | `@getMoodMonthlyRecap` | consent scope `mood_history` |
| GET | `api/psychologist/recap/{counseling}/monthly/sharing` | `@getStudentSharing` | consent scope `sharing_history` |
| GET | `api/psychologist/recap/{counseling}/monthly/counseling` | `@getLatestCounseling` | consent scope `assesment_logs` |

## Psychologist account administration

| Method | URI | Action | Enforced by |
|---|---|---|---|
| GET | `api/admin/psychologists` | `PsychologistController@index` | `Policy::viewAny` — super |
| POST | `api/admin/psychologists` | `@store` | `Policy::create` — super |
| GET | `api/admin/psychologists/{psychologist}` | `@show` | `Policy::view` — super |
| PUT\|PATCH | `api/admin/psychologists/{psychologist}` | `@update` | `Policy::update` — super |
| DELETE | `api/admin/psychologists/{psychologist}` | `@destroy` | `Policy::delete` — super |
| PATCH | `api/admin/psychologists/{psychologist}/toggle` | `@toggleStatus` | `Policy::update` — super |

## Principal dashboard

| Method | URI | Action | Enforced by |
|---|---|---|---|
| GET | `api/headteacher/dashboard/stats` | `PrincipalDashboardController@stats` | `abort` — headteacher |
| GET | `api/headteacher/incidents` | `@incidents` | `abort` — headteacher |
| PATCH | `api/headteacher/notifications/{id}/read` | `@markNotificationRead` | `abort` — headteacher |

`incidents` deliberately selects **metadata columns only** — `title`, `description` and `reply` are omitted so the principal never reads a student's words. It computes `is_sla_breached` (unacknowledged for ≥ 2 hours) and supports `?page`, `?per_page`, `?name`, `?status` (accepts fuzzy Indonesian aliases), `?is_breached`, and `?counselor_name` / `?counselor` / `?assigned_counselor`.

## Role dashboards

| Method | URI | Action | Enforced by |
|---|---|---|---|
| GET | `api/dashboard/super` | `DashboardController@super` | `Gate` — super |
| GET | `api/dashboard/headteacher` | `@headteacher` | `Gate` — headteacher |
| GET | `api/dashboard/admin` | `@admin` | `Gate` — admin |
| GET | `api/dashboard/teacher` | `@teacher` | `Gate` — teacher |
| GET | `api/dashboard/counselor` | `@counselor` | `Gate` — counselor |
| GET | `api/dashboard/content` | `@content` | — |
| GET | `api/dashboard/content-statistics` | `@contentStatistics` | `Gate` — admin |

## Content

| Method | URI | Action | Enforced by |
|---|---|---|---|
| GET | `api/video` | `VideoController@index` | — |
| POST | `api/video` | `@store` | `Policy::create` — admin |
| PUT\|PATCH | `api/video/{video}` | `@update` | `Policy::update` — admin, same school |
| DELETE | `api/video/{video}` | `@destroy` | `Policy::delete` — admin, same school |
| GET | `api/video/{video}` | `@getVideoDetailId` | `Policy::view` — same school. Bound by `video_id` (YouTube id) |
| GET | `api/video/tag/{tag}` | `@getByaTag` | — |
| GET | `api/content` | `@allContents` | — mixed video + article feed |
| GET | `api/dashboard/latest-content` | `@getLatestContent` | — |
| GET | `api/dashboard/today-content` | `@getTodayContent` | — |
| GET | `api/articles` | `ArticleController@index` | — |
| POST | `api/articles` | `@store` | `Request` |
| POST | `api/article-update/{article}` | `@update` | — POST, not PATCH, because of multipart thumbnail upload |
| DELETE | `api/articles/{article}` | `@destroy` | `Policy::delete` — admin, same school |
| GET | `api/article/{article}` | `@getArticle` | `Policy::view` — same school. Bound by `slug` |
| GET | `api/article/tag/{tag}` | `@getByTag` | — |
| GET | `api/quote` | `QuoteController@index` | — |
| POST | `api/quote` | `@store` | `Request` — admin |
| GET | `api/quote/{quote}` | `@show` | `Gate` — admin, same school |
| DELETE | `api/quote/{quote}` | `@destroy` | `Gate` — admin, same school |
| GET | `api/quote/mood` | `@getByType` | `Gate` — student **with a mood recorded today** |
| GET | `api/quote/daily` | `@getDaily` | — |

There is no `PATCH`/`PUT` route for quotes — they can only be created and deleted.

---

## Counseling payload: how the frontend learns a booking expired

`CounselingResource` returns a `latest_booking` object alongside the counseling, so one call to `GET /api/counseling?type=external` (or `GET /api/student/counselings`) is enough to render every card in the design, including `Konseling Terlewat` with its **Pilih Jadwal Baru** button.

```json
{
  "id": 24,
  "type": "external",
  "status": "menunggu_jadwal",
  "room": "Puskesmas Jetis",
  "slot": { "id": 44, "slot_date": "...", "slot_start_time": "09:00" },
  "latest_booking": {
    "id": 88,
    "status": "expired",
    "deadline_at": "2026-10-09T02:00:00+07:00",
    "reject_reason": null,
    "location": null,
    "is_expired": true,
    "was_rescheduled": false
  }
}
```

Two fields are derived so the client does not reimplement the rules:

- **`is_expired`** — `status = expired`, **or** `status = pending` with `deadline_at` in the past. The second branch matters because `referrals:expire-pending` only runs every 15 minutes, so there is a window where the row still reads `pending`.
- **`was_rescheduled`** — an earlier booking on the same counseling is `rescheduled`. This is the source of the design's **Perubahan Jadwal** badge; reschedule is auto-approved and therefore has no counseling-level status.

`deadline_at` is serialized in `Asia/Jakarta` (`+07:00`), matching `StudentBookingController`, not the UTC form the default cast would produce.

Collection endpoints add `withCount(['bookingSchedule as rescheduled_bookings_count' => …])` so `was_rescheduled` costs no extra query per row. `latest_booking` only appears when `latestBookingSchedule` is eager-loaded.

Status-to-badge mapping is in [`12-ui-contract.md`](12-ui-contract.md).

---

## Dead routes

| Route | Why |
|---|---|
| `GET api/coba` | `QuestionnaireController@coba` does not exist |
| `GET api/dashboard/mood-statistics` | `MoodRecordController@moodStatistics` does not exist |
| `PATCH api/report/confirm/{report}` | guard compares status against `'menunggu'` |
| `PATCH api/report/reschedule/{report}` | guard compares against `'disetujui'` |
| `PATCH api/report/close/{report}` | guard compares against `'disetujui'`/`'dijadwalkan'` |
| `PATCH api/report/cancel/{report}` | reuses `CloseReportRequest` |
| `GET sanctum/csrf-cookie` | Sanctum is installed but the API guard is JWT |

---

## 🛑 `GET /reset`

[`routes/web.php`](../../routes/web.php) registers a closure that runs, in the background:

```
php artisan migrate:fresh --seed --seeder=DemoCaseSeeder
```

It is guarded only by HTTP Basic auth whose credentials fall back to `novaren` / `secret` when `RESET_AUTH_USER` and `RESET_AUTH_PASSWORD` are not set. Anyone who reaches this URL with those defaults destroys every record in the database, including all clinical notes.

If you are documenting, deploying, or demoing this application: know that this route exists and where it points.
