# [BE-1.1] Student Dashboard & Counseling List APIs

## Overview

Secure endpoints designed to fetch dashboard summary widgets and counseling session records for students.

---

## API Endpoints

### 1. Retrieve Dashboard Summary Widgets
- **Endpoint**: `GET /api/student/dashboard/widgets`
- **Authentication**: Required (JWT Bearer Token)
- **Role**: `student`
- **Response Format**:
  ```json
  {
      "success": true,
      "message": "Student widget summary statistics retrieved.",
      "data": {
          "active_counselings_count": 2,
          "completed_counselings_count": 5,
          "pending_consents_count": 1
      }
  }
  ```

---

### 2. Retrieve Counseling Lists
- **Endpoint**: `GET /api/student/counselings`
- **Authentication**: Required (JWT Bearer Token)
- **Role**: `student`
- **Query Parameters (Optional)**:
  - `page`: Integer (page number for dynamic pagination)
  - `per_page`: Integer (records per page)
- **Response Format (Flat List)**:
  ```json
  {
      "success": true,
      "message": "Student counseling list retrieved.",
      "data": [
          {
              "id": 1,
              "student_id": 15,
              "counselor_id": 4,
              "psychologist_id": 8,
              "notes": "Academic anxiety",
              "reason": "Needs professional clinical attention",
              "type": "external",
              "status": "menunggu",
              "scheduled_at": "2026-06-15 10:00:00",
              "counselor": { ... },
              "psychologist": { ... },
              "latest_consent": { ... }
          }
      ]
  }
  ```
