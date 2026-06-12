# [BE-1.3] Background Job for AI Clinical Summary

## Overview

Queue-based processing system to generate and store high-quality clinical analysis summaries in the background once digital consent is granted.

---

## Workflow Execution

1. **Trigger**: Fired asynchronously immediately upon `[BE-1.2]` submission when `is_granted` is set to `true`.
2. **Sanitization Integration**: Receives anonymized text compiled by `HeadlessDataGenerator`.
3. **Execution**: Simulates/generates clinical assessment summaries in the background via `GenerateAISummaryJob`.
4. **Persistence**: Saves the output inside the `clinical_summaries` table.

---

## Database Schema (`clinical_summaries`)

| Column         | Type      | Description                                              |
| -------------- | --------- | -------------------------------------------------------- |
| `id`           | bigint    | Primary Key                                              |
| `counseling_id`| bigint    | FK to `counselings` table (cascade onDelete)             |
| `summary_data` | longText  | Generated clinical assessment output                     |
| `created_at`   | timestamp | Record creation timestamp                                |
| `updated_at`   | timestamp | Record last modified timestamp                           |
| `deleted_at`   | timestamp | Soft Delete support (standard)                           |
