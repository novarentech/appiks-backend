# [BE-5.2] API Endpoint for Counseling Submission & In-App Notification Trigger

## What It Does

When a BK counselor submits a new counseling session (internal with a teacher or external with a psychologist), this endpoint saves the data and automatically sends a notification to the student's Activity Center.

---

## Endpoint

`POST /api/counseling` — only BK counselors can access this.

---

## What Data Is Required

| Field | Required? | What to fill |
|-------|-----------|-------------|
| `date` | Yes | Session date (YYYY-MM-DD) |
| `time` | Yes | Session time (HH:MM) |
| `student_id` | Yes | Which student |
| `counselor_id` | Only if external | Which psychologist (leave empty if internal/teacher) |
| `room` | Only if internal | Room name/location |
| `notes` | No | Any notes |
| `reason` | Only if external | Why referring to psychologist |

**Internal** = teacher handles it → needs `room`, no `counselor_id`.  
**External** = psychologist handles it → needs `counselor_id` + `reason`, no `room` needed.

---

## How Data Flows

**Step 1 — Validation**  
The system checks that all required fields are present and formatted correctly. If not, it returns an error message telling you which field is wrong.

**Step 2 — Auto-fill**  
The system automatically:
- Combines `date` + `time` into a single `scheduled_at` timestamp
- Sets status to `menunggu` (waiting, for consent - future)
- If internal: assigns the BK counselor as the `counselor_id`
- If external: tags it as external type

**Step 3 — Save**  
A new counseling record is created with all the data.