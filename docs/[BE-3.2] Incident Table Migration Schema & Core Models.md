# [BE-3.2] Laravel: Incident Table Migration Schema & Core Models

## Overview

A new table named `nlp_analyses` has been created to store NLP analysis results from multiple features and modules, not limited to student counseling or curhat data.

This table acts as a centralized storage for NLP processing results and supports future NLP-based functionalities within the system.

---

# Table Structure

| Column       | Type      | Description               |
| ------------ | --------- | ------------------------- |
| id           | bigint    | Primary key               |
| text         | text      | Original text sent to NLP |
| response     | json      | NLP response result       |
| flag         | string    | NLP flag or label         |
| status       | enum      | Analysis status           |
| reason       | string    | Reason for status update  |
| nlpable_id   | bigint    | Polymorphic relation ID   |
| nlpable_type | string    | Polymorphic relation type |
| created_at   | timestamp | Created time              |
| updated_at   | timestamp | Updated time              |
| deleted_at   | timestamp | Soft delete timestamp     |

---

# Purpose

The `nlp_analyses` table is designed to:

* Store NLP processing results centrally
* Support multiple NLP use cases
* Allow result validation and review
* Improve NLP model quality over time

---

# Status Usage

The analysis result can be updated with statuses such as:

* `false-positive`
* `true-positive`
* `false-negative`
* `true-negative`

Each status update may include a `reason` field to provide context or feedback for future NLP model improvements and evaluation.

---

# Additional Notes

* Uses polymorphic relationships (`nlpable`) to support multiple modules/entities.
* Supports soft deletes for audit/history purposes.
* `response` column stores raw NLP output in JSON format for flexibility.
```
{
    "total_score": 7, 
    "zone_status": "Yellow Zone", 
    "matched_keywords": [
        {
            "stem": "mati", 
            "zone": "Red", 
            "weight": 7
        }
    ]
}
```
