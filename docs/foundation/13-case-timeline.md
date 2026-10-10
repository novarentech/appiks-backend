# Case Timeline

<!-- verified: branch=dev commit=working-tree date=2026-10-10 scope=app/Actions/RecordCaseEvent.php,app/Enums/CaseEventType.php,app/Models/CaseEvent.php,app/Support/CaseTimelineVisibility.php,database/migrations -->
> **Verified against:** `dev` working tree · 2026-10-10
> **Sources:** [`app/Models/CaseEvent.php`](../../app/Models/CaseEvent.php) · [`app/Enums/CaseEventType.php`](../../app/Enums/CaseEventType.php) · [`app/Actions/RecordCaseEvent.php`](../../app/Actions/RecordCaseEvent.php) · [`app/Support/CaseTimelineVisibility.php`](../../app/Support/CaseTimelineVisibility.php) · [`app/Http/Controllers/CaseTimelineController.php`](../../app/Http/Controllers/CaseTimelineController.php)

## Why this exists

Every status column in the case chain is **overwritten in place**. `sharings.status`, `counselings.status`, `booking_schedules.status` and `psychologist_slots.status` record the current state and nothing about how it was reached. Of roughly fifteen lifecycle moments, only eight left a durable timestamp, and several of those collided with each other:

- `nlp_analyses.updated_at` served both "analysis completed" and "marked false positive".
- `sharings.acknowledged_at` served both "counselor started handling" and "follow-up action chosen".
- `clinical_summaries.updated_at` was returned to clients as the AI `generated_at`, yet the psychologist's feedback write overwrote it.
- `counselings.scheduled_at` was overwritten on re-proposal, destroying the previous proposal.
- Both SLA clocks were **erased** once elapsed: `cutdown:clear` nulls `cutdown_for_report`, and the principal's 2-hour acknowledge breach was computed on the fly and never stored. Neither left evidence that a breach happened.

`counseling_log_histories` was the only audit table, and it is always empty — nothing in the codebase ever updates a `CounselingLog`, which is the only thing its observer reacts to.

`case_events` is now the single place where the order of events is kept.

---

## Table: `case_events`

Migration: [`2026_10_10_000001_create_case_events_table.php`](../../database/migrations/2026_10_10_000001_create_case_events_table.php). **Append-only: no soft deletes, and no runtime code path updates or deletes a row.**

| Column | Type | Null | Notes |
|---|---|---|---|
| `id` | bigint | no | |
| `sharing_id` | bigint FK | yes | → `sharings.id`, cascade. **The head entity.** Always populated when it can be derived from the counseling. |
| `counseling_id` | bigint FK | yes | → `counselings.id`, null on delete. Set for counseling- and referral-stage events. |
| `event` | string | no | A [`CaseEventType`](../../app/Enums/CaseEventType.php) value. **Deliberately a string, not a MySQL enum** — adding an event type must not require an `ALTER TABLE`. |
| `actor_id` | bigint FK | yes | → `users.id`, null on delete. **Null means the system acted** (job, scheduler). |
| `actor_role` | string | yes | Snapshot of the actor's role at the time, because `users.role` can change. |
| `occurred_at` | datetime | no | Domain time of the event, separate from `created_at` (when it was recorded). Seeded fixtures set this to historical values. |
| `payload` | json | yes | **Structured metadata only.** Never a curhat transcript, never clinical notes. |
| `created_at`, `updated_at` | timestamp | yes | |

Indexes: `(sharing_id, occurred_at)`, `(counseling_id, occurred_at)`, `event`.

Both foreign keys are nullable because a counseling scheduled from a report has no sharing. At least one of the two is always set; the recorder returns `null` without inserting when neither is.

### Companion schema change

`counselings.sharing_id` was **NOT NULL with no default** while the application treated it as nullable — `CreateCounselingRequest` validates it as `nullable`, `CounselingObserver` tests `!= null`, and [`ScheduleReportCounselingAction`](../../app/Actions/ScheduleReportCounselingAction.php) creates a counseling without it. With MySQL in strict mode that meant `POST /api/report/{report}/schedule-meeting` failed with `SQLSTATE[HY000] 1364`. The column is now nullable and properly constrained with a foreign key.

Separately, `Sharing::counseling()` was a `hasOne` with no ordering over a column with no unique index, so for the six demo sharings that have more than one counseling it returned an arbitrary row. It is now `hasOne()->latestOfMany()` — same response shape, deterministic result — and [`Sharing::counselings()`](../../app/Models/Sharing.php) was added for the full set.

---

## Event types

28 cases in [`CaseEventType`](../../app/Enums/CaseEventType.php), each with `label()` (Indonesian, ready to render), `stage()` and `isSystemEvent()`.

### Stage `triage`

| Event | Label | Payload | Recorded at |
|---|---|---|---|
| `sharing_created` | Curhat dikirim siswa | — | `SharingController::store` |
| `nlp_analyzed` | Kasus terdeteksi sistem | `zone`, `priority`, `total_score`, `sla_hours`, `sla_deadline` | `ProcessNlpAnalysisJob` |
| `nlp_analysis_failed` | Analisis risiko gagal dijalankan | — | same, in the `catch` before rethrowing |
| `priority_escalated` | Prioritas dinaikkan | `from`, `to`, `trigger` | `UpdateRelatedSharingPriority` |
| `sla_deadline_elapsed` | Batas waktu tindak lanjut terlampaui | `deadline_at` | `ClearCutdown`, **before** the column is nulled |
| `sharing_acknowledged` | Guru BK mulai menangani | — | `SharingController::ack` and `::acknowledge` |
| `followup_action_chosen` | Keputusan tindak lanjut dipilih | `action` | `SharingController::acknowledge` |
| `sharing_replied` | Guru BK membalas curhat | — | `SharingController::reply` |
| `sharing_marked_false_positive` | Alert ditandai bukan urgent | `reason` | `SharingController::falsePositive` |

`sharing_acknowledged` and `followup_action_chosen` are now two rows. Previously both shared one `acknowledged_at`, so the moment handling started and the moment a decision was made were indistinguishable.

### Stage `konseling`

| Event | Label | Payload | Recorded at |
|---|---|---|---|
| `counseling_created` | Sesi konseling dibuat | `type`, `source_type` | `CounselingObserver::created` |
| `counseling_schedule_proposed` | Jadwal konseling diajukan ke siswa | `scheduled_at` | `ScheduleReportCounselingAction` |
| `counseling_accepted` | Siswa menyetujui jadwal | — | `CounselingController::acknowledge` |
| `counseling_declined` | Siswa menolak jadwal | — | same |
| `counseling_reproposed` | Jadwal konseling diajukan ulang | `from`, `to` | `CounselingController::repropose` |
| `counseling_cancelled` | Sesi konseling dibatalkan | — | `CounselingController::cancel` |
| `counseling_log_stored` | Hasil konseling dicatat | `resolution`, `method` | `StoreCounselingLogAction` |

`counseling_reproposed` captures `from` before the write, which is the only place the previous proposal survives — `scheduled_at` is overwritten in place.

### Stage `rujukan`

| Event | Label | Payload | Recorded at |
|---|---|---|---|
| `consent_requested` | Persetujuan akses data diminta | — | `CounselingObserver::created`, `CounselingController::sendConsent` |
| `consent_granted` | Siswa menyetujui akses data | `scopes`, `scopes_count` | `UpdateConsentAction` |
| `consent_rejected` | Siswa menolak akses data | — | same |
| `booking_created` | Siswa mengajukan jadwal konsultasi | `slot_date`, `slot_time`, `deadline_at` | `CreateBookingScheduleAction` |
| `booking_confirmed` | Psikolog mengonfirmasi jadwal | `slot_date`, `slot_time` | `DecideReferralAction` |
| `booking_rescheduled` | Psikolog menggeser jadwal | `reason`, `from`, `slot_date`, `slot_time` | same |
| `booking_rejected` | Psikolog menolak rujukan | `reason` | same |
| `booking_expired` | Jadwal konsultasi kedaluwarsa | `deadline_at` | `ExpirePendingReferrals` |
| `ai_summary_generated` | Ringkasan klinis AI dibuat | — | `GenerateGeminiReferralSummaryJob` |
| `ai_summary_failed` | Ringkasan klinis AI gagal dibuat | — | same |
| `case_closed` | Konseling selesai, kasus ditutup | — | `PsychologistSummaryController::storeFeedback` |

---

## How recording works

[`RecordCaseEvent`](../../app/Actions/RecordCaseEvent.php) is called **explicitly** at each transition site — 27 call sites across controllers, actions, jobs, the observer, the listener and two console commands.

### Why not observers

Four write paths in the case chain use a query-builder mass update and therefore fire **no Eloquent model events**:

| Path | What it writes |
|---|---|
| `ProcessNlpAnalysisJob` → `nlpable()?->update()` | the only writer of `sharings.priority` and `cutdown_for_report` |
| `SharingController::falsePositive` → `nlp()->update()` | the only writer of `nlp_analyses.status` and `reason` |
| `UpdateRelatedSharingPriority` | mass update of `priority` across a student's sharings |
| `ClearCutdown` | mass update nulling both SLA columns |

An observer-based recorder would silently miss exactly the transitions most worth tracking. Explicit calls are also greppable, which matters for a log whose completeness is the whole point.

> **Deviation from [`agent/RULE_OF_ARCHITECT.md`](../../agent/RULE_OF_ARCHITECT.md) §6**, which requires side effects to go through Events and Listeners. The alternative was ~28 new event classes for a single row insert each, and it still would not reach the mass-update paths. The deviation is recorded here deliberately. The four pre-existing domain events that had no listener (`BookingScheduleCreated`, `BookingExpired`, `CounselingScheduled`, `CounselingLogStored`) remain unlistened; recording happens next to where they are dispatched.

### Two properties of the recorder

**It never throws.** Everything is wrapped in `try/catch` with a `Log::error`. Losing one audit row is far cheaper than rejecting a student's curhat or aborting a psychologist's confirmation because the recorder failed.

**System events never carry an actor.** `CaseEventType::isSystemEvent()` returns true for the NLP, SLA, expiry and AI events, and the recorder discards `Auth::user()` for those — otherwise whoever happened to be logged in when a queued job ran would be recorded as the cause.

---

## Access

**Currently Kepala Sekolah only**, by product decision. Three places govern it, and opening a role up is a small, contained change:

| Where | What to change |
|---|---|
| [`CaseTimelineVisibility::RULES`](../../app/Support/CaseTimelineVisibility.php) | Add one entry: which events and which payload keys that role sees |
| [`SharingPolicy::viewTimeline`](../../app/Policies/SharingPolicy.php) | Replace the role's `false` branch with its condition — the intended rule for each role is written as a comment |
| [`CounselingPolicy::viewTimeline`](../../app/Policies/CounselingPolicy.php) | Same |

Verified behaviour today: `headteacher` → 200; `counselor`, `teacher`, `admin`, `super`, `psychologist` and even the sharing's own student → 403.

### Privacy model

Two layers, because the principal's dashboard carries an explicit disclaimer — *"Informasi ditampilkan terbatas untuk menjaga privasi siswa"* — while the counselor's and psychologist's own history tables legitimately show curhat transcripts and NLP keyword chips.

1. **`payload` holds metadata only.** No transcripts, no clinical notes. This is a property of what the recorder writes, not a filter.
2. **Per-role allowlist on top.** For the principal, `CaseTimelineVisibility::METADATA_PAYLOAD_KEYS` additionally strips human-written free text (`reason`) and detail that role does not need (`total_score`, the `scopes` list — only `scopes_count` survives). Actor names are omitted per step, matching the design, which shows the PIC once on the identity card rather than on every row.

---

## Endpoints

| Method | URI | Notes |
|---|---|---|
| GET | `api/sharing/{sharing}/timeline` | Primary entry point. Returns every event for the case, including all counselings derived from that sharing. |
| GET | `api/counseling/{counseling}/timeline` | Entry from the referral side. When the counseling has a sharing, it returns the **full** case timeline from the sharing, not just the slice from when the session was created. |

Response shape:

```json
{
  "sharing_id": 1,
  "opened_at": "2026-09-29T10:23:00+07:00",
  "events": [
    {
      "id": 12,
      "event": "nlp_analyzed",
      "label": "Kasus terdeteksi sistem",
      "stage": "triage",
      "is_system": true,
      "occurred_at": "2026-09-29T10:23:20+07:00",
      "payload": { "zone": "Red Zone", "priority": "tinggi" }
    }
  ]
}
```

`label` is server-rendered so the client does not maintain its own copy of 28 strings. `stage` groups rows into `triage`, `konseling` and `rujukan`. `is_system` tells the UI not to look for an actor. Events are returned in chronological order, with `id` breaking ties for events recorded within the same request.

---

## Demo data

Seeders write rows directly rather than through the actions, so the runtime recorder does not fire for them. Two pieces fill the gap:

- [`NlpAnalysisSeeder`](../../database/seeders/NlpAnalysisSeeder.php) emits `sharing_created` and `nlp_analyzed` for **every** sharing, so the principal's incident list never opens an empty timeline.
- [`CaseTimelineSeeder`](../../database/seeders/CaseTimelineSeeder.php) runs **last** in both `DatabaseSeeder` and `DemoCaseSeeder`. It uses [`SeedsCaseTimeline`](../../database/seeders/Concerns/SeedsCaseTimeline.php), which **derives** each chain from the fixture rows that already exist — their own `created_at`, `acknowledged_at`, `granted_at`, booking statuses and logs. No step list is maintained twice, and the result always matches the data. It is idempotent: it deletes a case's existing events before rewriting them. That delete is the only one in the codebase and exists only in seeder code.

After `migrate:fresh --seed` the demo contains roughly 230 events across 30 cases.

Two fixture inconsistencies were corrected so the demo timelines read forwards rather than backwards:

- `ReferralFlowSeeder::createReferralBase` adopted an existing sharing with an unrelated random date, which could place the curhat *after* its own counseling. The adopted sharing is now backdated to precede the report.
- Every external counseling received **two** consents — one from `CounselingObserver::created`, one written explicitly by the seeder. That inflated the student dashboard's pending-consent count and produced a duplicate `consent_requested` step. The seeder now updates the observer's row instead of inserting a second one.

---

## What is still not recorded

- **Slot status changes** (`available` → `tentative` → `confirmed`) have no events of their own. They are implied by the booking events.
- **The 2-hour acknowledge SLA breach** is still computed on the fly in `PrincipalDashboardController` and not persisted. `sla_deadline_elapsed` covers the NLP deadline (`cutdown_for_report`), which is a different clock.
- **Report lifecycle transitions** are not covered; those endpoints are unreachable anyway, see [`08-state-machines.md`](08-state-machines.md).
- **`counselings.cutdown_at`** remains a dead column. Nothing writes or reads it.
