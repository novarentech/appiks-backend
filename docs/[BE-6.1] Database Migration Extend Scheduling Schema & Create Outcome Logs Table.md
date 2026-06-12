# [BE-6.1] Database Migration Extend Scheduling Schema & Create Outcome Logs Table

## Overview

To fulfill Permendikbudristek 46/2023 compliance, we extend the counseling schedule tracking capabilities and create a secure, encrypted clinical logging database structure. This bridges high-risk student reports to their clinical outcomes with a strict append-only audit trail.

---

## Table Structure

### 1. `counselings` Table Modifications

Columns added to link the counseling session back to its source high-risk NLP-flagged student report.

| Column         | Type      | Description                                       |
| -------------- | --------- | ------------------------------------------------- |
| `source_type`  | enum      | `regular` or `nlp_incident` (default: `regular`)  |
| `report_id`    | bigint    | FK to `reports` table (nullable, null on delete)  |

### 2. New Table: `counseling_logs`

This table holds the clinical session outcome record. To prevent sensitive medical leaks, the clinical summaries are stored in encrypted format.

| Column              | Type      | Description                                       |
| ------------------- | --------- | ------------------------------------------------- |
| `id`                | bigint    | Primary key                                       |
| `counseling_id`     | bigint    | FK to `counselings` (cascade on delete)           |
| `student_id`        | bigint    | FK to `users` (cascade on delete)                 |
| `counselor_id`      | bigint    | FK to `users` (cascade on delete)                 |
| `session_mode`      | string    | Mode of counseling (e.g., Tatap Muka, Video Call, Chat) |
| `clinical_notes`    | text      | Encrypted rekam medis / clinical session summary  |
| `resolution_status` | string    | Outcome resolution (maps to `CounselingResolution`) |
| `created_at`        | timestamp | Created time                                      |
| `updated_at`        | timestamp | Updated time                                      |
| `deleted_at`        | timestamp | Soft delete timestamp                             |

### 3. New Table: `counseling_log_histories`

Acts as an append-only snapshot log. Modifying clinical outcomes is strictly restricted; instead, updates save previous versions to this audit trail table.

| Column              | Type      | Description                                       |
| ------------------- | --------- | ------------------------------------------------- |
| `id`                | bigint    | Primary key                                       |
| `counseling_log_id` | bigint    | FK to `counseling_logs` (cascade on delete)       |
| `clinical_notes`    | text      | Encrypted snapshot of previous clinical notes     |
| `updated_by`        | bigint    | FK to `users` (who performed the edit)            |
| `created_at`        | timestamp | Logged version timestamp                          |
| `updated_at`        | timestamp | Updated timestamp                                 |

---

## Data Protection & Encryption

To adhere strictly to data privacy mandates for student counseling, **Laravel Native Attribute Encryption** is enforced on clinical records.

- Stored in the database as a securely ciphered string (`eyJpdi...`).
- Decrypted dynamically on Eloquent retrieval without raw text leakage.

### Model Implementation (`app/Models/CounselingLog.php`)

```php
protected function casts(): array
{
    return [
        'clinical_notes' => 'encrypted',
    ];
}
```

---

## Model Relationships

The following Eloquent associations are defined to connect scheduling to reports and logs:

```php
// app/Models/Counseling.php
public function report()
{
    return $this->belongsTo(Report::class, 'report_id');
}

public function logs()
{
    return $this->hasMany(CounselingLog::class, 'counseling_id');
}

// app/Models/Report.php
public function counselings()
{
    return $this->hasMany(Counseling::class, 'report_id');
}
```
