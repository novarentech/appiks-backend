# Authorization

<!-- verified: branch=dev commit=working-tree date=2026-10-10 scope=app/Policies,app/Providers/AppServiceProvider.php,app/Http/Controllers,app/Http/Requests,routes -->
> **Verified against:** `dev` working tree · 2026-10-10
> **Sources:** [`app/Providers/AppServiceProvider.php`](../../app/Providers/AppServiceProvider.php) · [`app/Policies/`](../../app/Policies/) (11 files) · [`app/Http/Controllers/`](../../app/Http/Controllers/) · [`app/Http/Requests/`](../../app/Http/Requests/)

There is **no permission package** (no spatie/laravel-permission), **no permissions table**, and **no `app/Http/Middleware` directory at all**. Authorization is a single `users.role` string column, enforced in four scattered places.

The routing layer has exactly two middleware contexts: public, and `auth:api`. **No route carries a role check.** Every role decision happens inside a controller, a policy, or a form request.

> **Before you conclude that an endpoint is open, check all four sites below.** Checking only policies will mislead you — most endpoints are not protected by one.

---

## The four enforcement sites

### 1. One Gate: `dashboard-data`

Defined in [`AppServiceProvider::boot`](../../app/Providers/AppServiceProvider.php):

```php
Gate::define('dashboard-data', fn (User $user) => $user->role != UserRole::STUDENT->value);
```

It means "any authenticated non-student", with **no school scoping**. Used in six places: five in [`RoomController`](../../app/Http/Controllers/RoomController.php) (`index`, `store`, `show`, `byLevel`, `getLevel`, `roomStudentCount`) and once in [`MoodRecordController::getMoodGraph`](../../app/Http/Controllers/MoodRecordController.php).

Because it ignores `school_id`, a teacher at school A passes this gate for a room belonging to school B. Any school filtering on those endpoints is the controller's own job.

### 2. Inline `Gate::allowIf()` closures in controllers

The most common pattern in this codebase — roughly twenty call sites. These are plain role equality checks written at the top of a controller method.

| Controller · method | Condition |
|---|---|
| `DashboardController::super` | `role == super` |
| `DashboardController::headteacher` | `role == headteacher` |
| `DashboardController::teacher` | `role == teacher` |
| `DashboardController::counselor` | `role == counselor` |
| `DashboardController::admin` | `role == admin` |
| `DashboardController::contentStatistics` | `role == admin` |
| `GameController::cirrus` / `buy` / `claim` | `role == student` |
| `QuestionnaireController::analyzeQuestionnaire` | `role == student` |
| `QuoteController::getByType` | `role == student` **and** `last_mood !== null` |
| `QuoteController::show` / `destroy` | `role == admin` **and** same `school_id` as the quote |
| `RoomController::roomOfSchool` | `role == super` |
| `RoomController::update` / `destroy` | `role == admin` **and** same `school_id` as the room |
| `SelfHelpController::getByType` | `role == teacher` **and** caller is the target student's `mentor_id` |
| `SelfHelpController::createDaily` / `createGratitude` / `createGrounding` / `createSensory` | `role == student` |
| `UserController::getUsersByType` | `role != student` |
| `UserController::edit` | (`role == admin` **and** same school) **or** `role == super` |
| `UserController::destroy` | admin → target is same school and **not** super/admin · super → target **must be** an admin · otherwise deny |

Note the asymmetry in `destroy`: a superadmin can delete **only** admins, while an admin can delete anyone in their school except superadmins and other admins. Neither can delete a psychologist through this endpoint.

### 3. Policies

Eleven policy classes in [`app/Policies/`](../../app/Policies/). Three are registered explicitly in `AppServiceProvider` (`PsychologistPolicy`, `PsychologistSlotPolicy`, `BookingSchedulePolicy`); the rest resolve by Laravel's naming convention.

**Many abilities have non-standard names** — `recapPerMonth`, `viewSchoolTrend`, `viewHistory`, `export`, `viewGraph`, `viewStudentReports`, `viewLatest`, `scheduleMeeting`, `storeLog`, `viewStudent`, `acknowledge`, `manageSchedule`, `viewTimeline`, `falsePositive`, `manage`, `decide`. These are invoked explicitly via `Gate::authorize('name', ...)` and will **not** be picked up by `authorizeResource()` or implicit resource authorization.

#### Policy inventory

| Policy | Ability | Condition |
|---|---|---|
| [`SchoolPolicy`](../../app/Policies/SchoolPolicy.php) | `viewAny`, `create`, `update`, `delete` | `super` only |
| | `view` | `super` **or** the user's own school |
| [`PsychologistPolicy`](../../app/Policies/PsychologistPolicy.php) | `viewAny`, `view`, `create`, `update`, `delete` | `super` only — all five |
| [`PsychologistSlotPolicy`](../../app/Policies/PsychologistSlotPolicy.php) | `manage` | `psychologist` **and** has a `psychologistProfile` |
| | `delete` | `manage` **and** the slot belongs to the caller's profile |
| [`BookingSchedulePolicy`](../../app/Policies/BookingSchedulePolicy.php) | `decide` | the booking's slot belongs to the caller's `psychologistProfile` |
| [`CounselingPolicy`](../../app/Policies/CounselingPolicy.php) | `storeLog` | caller **is** the counseling's `counselor_id` |
| | `viewStudent` | caller is the counseling's `student_id`, `psychologist_id`, **or** `counselor_id` |
| | `acknowledge` | caller **is** the counseling's `student_id` |
| | `manageSchedule` | caller **is** the counseling's `counselor_id` — cancel and re-propose |
| | `viewTimeline` | **headteacher of the student's school only.** Other roles exist as commented branches, ready to enable |
| [`CounselingConsentPolicy`](../../app/Policies/CounselingConsentPolicy.php) | `view`, `update` | caller is the consent's counseling `student_id` — **students only, by construction** |
| [`SharingPolicy`](../../app/Policies/SharingPolicy.php) | `create` | `role == student` |
| | `view` | caller owns the sharing **or** is the author's `counselor_id` |
| | `update` | `role == counselor` **and** caller is the author's `counselor_id` |
| | `falsePositive` | caller is the author's `counselor_id` **and** `status == ReportStatus::MENUNGGU_TINJAUAN` |
| | `viewGraph` | `counselor` or `super` |
| | `viewStudentSharing` | `super` **and** target is a student |
| [`ReportPolicy`](../../app/Policies/ReportPolicy.php) | `create` | `role == student` **and** `last_mood ∈ {angry, sad}` |
| | `view` | caller owns the report **or** is the author's `counselor_id` |
| | `update` | `role == counselor` **and** same school as the report's author |
| | `viewGraph` | `counselor`, `super`, or `headteacher` |
| | `viewStudentReports` | `super` **and** target is a student |
| | `viewLatest` | `role == student` |
| | `scheduleMeeting` | `super`, `admin`, `headteacher`, **or** the author's assigned `counselor` |
| [`MoodRecordPolicy`](../../app/Policies/MoodRecordPolicy.php) | `store`, `recapPerMonth` | `role == student` |
| | `viewHistory` | (`counselor` **and** target's `counselor_id`) **or** (`teacher` **and** target's `mentor_id`) **or** `super` |
| | `viewSchoolTrend` | `super` only |
| | `export` | `teacher` **or** `counselor` |
| [`VideoPolicy`](../../app/Policies/VideoPolicy.php) | `create` | `role == admin` |
| | `view` | same `school_id` as the video |
| | `update`, `delete` | `role == admin` **and** same `school_id` |
| [`ArticlePolicy`](../../app/Policies/ArticlePolicy.php) | same four abilities, same conditions as `VideoPolicy` | |

> `SharingPolicy::falsePositive` compares `$sharing->status` against the **enum case**, not its `->value`. That is correct precisely because `Sharing` casts the column. The `->value` form was the bug fixed in commit `26ac4b2`.

### 4. `FormRequest::authorize()`

Several endpoints are gated entirely by their form request, with nothing in the controller.

| Request | Condition |
|---|---|
| [`CreateAdminRequest`](../../app/Http/Requests/CreateAdminRequest.php) | `role == super` |
| [`CreateUserRequest`](../../app/Http/Requests/CreateUserRequest.php) | `role == admin` |
| [`CreateStudentRequest`](../../app/Http/Requests/CreateStudentRequest.php) | `role == admin` |
| [`CreateRoomRequest`](../../app/Http/Requests/CreateRoomRequest.php) | `role == admin` |
| [`CreateQuoteRequest`](../../app/Http/Requests/CreateQuoteRequest.php) | `role == admin` |
| [`CreateCounselingRequest`](../../app/Http/Requests/CreateCounselingRequest.php) | `role == counselor` |
| [`MoodRecordSendRequest`](../../app/Http/Requests/MoodRecordSendRequest.php) | `role == student` **and** has not recorded today |
| [`AnalyzeQuestionnaireRequest`](../../app/Http/Requests/AnalyzeQuestionnaireRequest.php) | `role == student` |
| [`UserFirstLoginRequest`](../../app/Http/Requests/UserFirstLoginRequest.php) | `!$user->verified` — so the endpoint works exactly once |
| [`ReplySharingRequest`](../../app/Http/Requests/ReplySharingRequest.php) | blocks a second reply to the same sharing |

Several other requests return `true` from `authorize()` and delegate to the controller: `UpdateConsentRequest`, `StoreBookingRequest`, `DecideReferralRequest`, `StorePsychologistRequest`, `UpdatePsychologistRequest`, `UpdateUserRequest`, `GetSharingRequest`. That is correct for those endpoints **today**, but it means the request class offers no protection if a future controller forgets its gate.

### 5. Bare `abort(403)` (a de facto fifth site)

Three controllers do their role check with a private helper and a raw abort rather than a gate or policy:

| Controller | Check | Message |
|---|---|---|
| [`PrincipalDashboardController`](../../app/Http/Controllers/PrincipalDashboardController.php) | private `ensurePrincipal()` → `role == headteacher` | *"Akses ditolak. Hanya Kepala Sekolah yang diizinkan."* |
| [`PsychologistReferralController`](../../app/Http/Controllers/PsychologistReferralController.php) | has a `psychologistProfile` | *"Hanya psikolog yang dapat mengakses halaman ini."* |
| [`PsychologistSummaryController`](../../app/Http/Controllers/PsychologistSummaryController.php) | same, plus "is this referral mine", plus consent-scope checks | three distinct messages |

These are invisible to `Gate::` greps and to policy-based reasoning. Worth remembering when auditing.

---

## Consent-scope authorization

A fifth dimension, unique to the psychologist's data-recap endpoints. `PsychologistSummaryController::authorizeConsentScope` aborts 403 unless the counseling's `latestConsent` is `granted` **and** its `scopes` array contains the specific scope:

| Endpoint | Required scope |
|---|---|
| `GET /api/psychologist/recap/{counseling}/monthly/mood` | `mood_history` |
| `GET /api/psychologist/recap/{counseling}/monthly/sharing` | `sharing_history` |
| `GET /api/psychologist/recap/{counseling}/monthly/counseling` | `assesment_logs` |

This is authorization **by the data subject**, not by role — the student decides. See [`07-data-model/04-referral-and-consent.md`](07-data-model/04-referral-and-consent.md).

---

## Role capability matrix

Legend: ✅ allowed · ⚠️ allowed under a condition (stated) · ❌ denied.

| Capability | super | admin | headteacher | teacher | counselor | student | psychologist |
|---|---|---|---|---|---|---|---|
| **School CRUD** (`api/school`) | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| View own school (`school/{school}`) | ✅ | ⚠️ own | ⚠️ own | ⚠️ own | ⚠️ own | ⚠️ own | ⚠️ own |
| **Create admin** (`user/admin`) | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Create staff** (`dashboard/users`) | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Create student** (`user/student`, `user/bulk`) | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Edit any user (`edit-user/{user}`) | ✅ | ⚠️ same school | ❌ | ❌ | ❌ | ❌ | ❌ |
| Delete user (`user/{user}`) | ⚠️ admins only | ⚠️ same school, not super/admin | ❌ | ❌ | ❌ | ❌ | ❌ |
| List users by type | ✅ system-wide | ✅ own school | ✅ own school | ✅ own school | ✅ own school | ❌ | ✅ own school |
| **Room CRUD** (create/update/delete) | ❌ | ⚠️ own school | ❌ | ❌ | ❌ | ❌ | ❌ |
| Read rooms (`dashboard-data` gate) | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| **Content CRUD** (video, article, quote) | ❌ | ⚠️ own school | ❌ | ❌ | ❌ | ❌ | ❌ |
| View content | ⚠️ same school | ⚠️ same school | ⚠️ same school | ⚠️ same school | ⚠️ same school | ⚠️ same school | ⚠️ same school |
| **Own dashboard** (`dashboard/{role}`) | ✅ | ✅ | ✅ | ✅ | ✅ | — | — |
| **Mood check-in** | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| Mood recap / streaks (own) | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| Mood history of a student | ✅ | ❌ | ❌ | ⚠️ own mentees | ⚠️ own counselees | ❌ | ❌ |
| School-wide mood trend | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Mood Excel export | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ | ❌ |
| **Questionnaire** | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| **Self-help: write** | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| Self-help: read a student's | ❌ | ❌ | ❌ | ⚠️ own mentees | ❌ | ❌ | ❌ |
| **Cirrus game** | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| **Write curhat** | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| View a curhat | ⚠️ | ❌ | ❌ | ❌ | ⚠️ own counselees | ⚠️ own | ❌ |
| Reply / ack / acknowledge curhat | ❌ | ❌ | ❌ | ❌ | ⚠️ own counselees | ❌ | ❌ |
| Mark false positive | ❌ | ❌ | ❌ | ❌ | ⚠️ own counselees, status `Belum Ditinjau` | ❌ | ❌ |
| All sharings of a student | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Create report** | ❌ | ❌ | ❌ | ❌ | ❌ | ⚠️ mood `sad`/`angry` | ❌ |
| Report confirm/reschedule/close/cancel | ❌ | ❌ | ❌ | ❌ | ⚠️ same school | ❌ | ❌ |
| Schedule meeting from report | ✅ | ✅ | ✅ | ❌ | ⚠️ own counselees | ❌ | ❌ |
| Report graph | ✅ | ❌ | ✅ | ❌ | ✅ | ❌ | ❌ |
| **Create counseling / referral** | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| Write counseling log | ❌ | ❌ | ❌ | ❌ | ⚠️ assigned counselor | ❌ | ❌ |
| List own counselings | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| Acknowledge proposed counseling | ❌ | ❌ | ❌ | ❌ | ❌ | ⚠️ own, while awaiting them | ❌ |
| Cancel / re-propose counseling | ❌ | ❌ | ❌ | ❌ | ⚠️ assigned counselor | ❌ | ❌ |
| **Read / submit consent** | ❌ | ❌ | ❌ | ❌ | ⚠️ read only | ✅ | ⚠️ read only |
| **Browse slots, create booking** | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ | ⚠️ read dates/slots |
| **Publish / delete own slots** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |
| Incoming referrals, decide | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ⚠️ own slots |
| AI clinical summary + feedback | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ⚠️ assigned + consent granted |
| Consent-scoped recaps | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ⚠️ per-scope |
| **Psychologist account CRUD** | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Principal dashboard & incidents** | ❌ | ❌ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Case timeline** (`/timeline`) | ❌ | ❌ | ⚠️ own school | ❌ | ❌ | ❌ | ❌ |

The matrix describes the checks that exist. Where a cell says ✅ with no condition and the endpoint returns a list, assume **no school scoping unless the controller adds it** — see the `dashboard-data` note above.

---

## Known holes

Facts, verified at the cited locations. Not fixed in this documentation pass.

### `GET /api/counseling/{counseling}` has no authorization at all

[`CounselingController::show`](../../app/Http/Controllers/CounselingController.php) loads the model and returns it. No gate, no policy, no ownership check. Any authenticated user — including any student — can read any counseling session by incrementing the id, which exposes the student's name, the counselor, the psychologist, the linked curhat, and the clinical summary via [`CounselingResource`](../../app/Http/Resources/CounselingResource.php).

By contrast, `index` on the same controller is restricted to students and scoped to `student_id = Auth::id()`.

### `GET /api/counseling/{counseling}` still has no authorization

`CounselingController::show` loads the model and returns it with no gate, policy, or ownership check. Any authenticated user can read any counseling session by incrementing the id.

> `PATCH /api/student/counselings/{counseling}/acknowledge` **was** in the same state and is now fixed: it calls `Gate::authorize('acknowledge', $counseling)` (student owner only) and additionally refuses unless the counseling is in a status that awaits the student (`CounselingStatus::needsStudentAction()`).

### `User::$guarded = []` plus `editProfile` allows self-escalation

[`User`](../../app/Models/User.php) guards nothing, and [`UserController::editProfile`](../../app/Http/Controllers/UserController.php) calls `Auth::user()->update($request->all())`. A student can send `role=super`, `school_id`, `counselor_id` or `verified` in that request body and have it persisted. Nothing in the route, the controller, or a form request filters the payload.

### `GET /api/user/bulk/template` is public

It sits outside the `auth:api` group in [`routes/api.php`](../../routes/api.php), so the bulk-import Excel template is downloadable without a token.

### `GET /reset` wipes the database

[`routes/web.php`](../../routes/web.php) registers a closure that runs `migrate:fresh --seed --seeder=DemoCaseSeeder`, protected only by HTTP Basic auth whose credentials default to `novaren` / `secret` when `RESET_AUTH_USER` / `RESET_AUTH_PASSWORD` are unset.

### Dead guards in the report lifecycle

`CloseReportRequest`, `RescheduleReportRequest` and `ConfirmReportRequest` compare the report status against string literals `'disetujui'`, `'menunggu'` and `'dijadwalkan'`. None of those is a [`ReportStatus`](../../app/Enums/ReportStatus.php) value, so those guards cannot pass as written. `[DEAD]`

### Role-agnostic ownership checks

`ReportPolicy::view` and `SharingPolicy::view` grant access when `$record->user->counselor_id == $user->id`, without checking that the caller's role is `counselor`. In practice `counselor_id` only ever references a counselor, so this is not currently exploitable — but the check expresses identity, not role, and would break if `counselor_id` were ever reused.

### `authorize(): true` form requests

Seven form requests delegate authorization entirely to their controller. Correct today; silent failure mode tomorrow if a controller gate is removed or a new method reuses the request.

---

## Tension with the architecture contract

[`agent/RULE_OF_ARCHITECT.md`](../../agent/RULE_OF_ARCHITECT.md) §5 states:

> All role-based access control and model-specific authorization MUST utilize Laravel Policies. Do not use inline Gates for model CRUD logic.

The codebase does the opposite in roughly twenty places, including model CRUD (`RoomController::update`/`destroy`, `QuoteController::show`/`destroy`). Both facts are recorded here; this document does not resolve the contradiction. If you are adding an endpoint, follow the contract and write a policy — that is also the only way the authorization surface becomes greppable.
