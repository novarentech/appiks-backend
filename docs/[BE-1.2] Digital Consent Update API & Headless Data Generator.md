# [BE-1.2] Digital Consent Update API & Headless Data Generator

## Overview

Student consent submission mechanism designed to store granular permissions and programmatically strip all PII (Personally Identifiable Information) before triggering background AI data generators.

---

## API Endpoints

### 1. Fetch Current Active Consent Request
- **Endpoint**: `GET /api/student/counselings/{counseling}/consent`
- **Authentication**: Required (JWT Bearer Token)
- **Role**: `student`
- **Response Format**:
  ```json
  {
      "success": true,
      "message": "Active consent request details retrieved.",
      "data": {
          "id": 1,
          "counseling_id": 5,
          "status": "pending",
          "scopes": null,
          "granted_at": null,
          "rejected_at": null
      }
  }
  ```

---

### 2. Submit Digital Consent Choices
- **Endpoint**: `PATCH /api/student/consents/{consent}`
- **Authentication**: Required (JWT Bearer Token)
- **Role**: `student`
- **Payload**:
  ```json
  {
      "is_granted": true,
      "scopes": [
          "mood_history",
          "red_zone_confessions"
      ]
  }
  ```
- **Response Format**:
  ```json
  {
      "success": true,
      "message": "Digital consent submission recorded successfully.",
      "data": {
          "id": 1,
          "counseling_id": 5,
          "status": "granted",
          "scopes": [
              "mood_history",
              "red_zone_confessions"
          ],
          "granted_at": "2026-06-12 13:50:00",
          "rejected_at": null
      }
  }
  ```

---

## Headless Data Generator Service (`app/Services/HeadlessDataGenerator.php`)

Strips the student's actual Name, Email/Username, Phone, and Student ID/NIS from their raw venting/sharing history log text contents to maintain absolute data anonymity.

### Code Pattern
```php
$piiTerms = array_filter([
    $student->name,
    $student->username,
    $student->phone,
    $student->identifier,
]);
```
- Standardizes scrubbing routines via regular expressions matching email addresses and phone patterns.
