# [BE-2.1] Fetch Available Psychologist Slots API

## Overview

Endpoints allowing students to view available consultation dates and time slots for external referrals assigned to a partner psychologist.

---

## API Endpoints

### 1. Retrieve Available Consultation Dates
- **Endpoint**: `GET /api/student/referrals/{counseling}/available-dates`
- **Authentication**: Required (JWT Bearer Token)
- **Role**: `student` (Must own the counseling record)
- **Description**: Returns all distinct dates with available slots starting at least 2 days from today, excluding slots that are already booked with `pending` or `confirmed` status.
- **Response Format**:
  ```json
  {
      "success": true,
      "message": "Available consultation dates retrieved.",
      "data": {
          "psychologist": {
              "name": "Dr. Sarah Wijaya, M.Psi",
              "facility_name": "Klinik Psikologi Sejahtera",
              "specialization": "Psikologi Anak & Remaja"
          },
          "earliest_available_date": "2026-07-28",
          "available_dates": [
              {
                  "date_raw": "2026-07-28",
                  "date_formatted": "Selasa, 28 Juli 2026",
                  "available_slots_count": 4,
                  "slot_label": "4 slot tersedia",
                  "is_selectable": true
              }
          ]
      }
  }
  ```

---

## 2. Retrieve Available Time Slots for a Specific Date
- **Endpoint**: `GET /api/student/referrals/{counseling}/available-slots?date=YYYY-MM-DD`
- **Authentication**: Required (JWT Bearer Token)
- **Role**: `student` (Must own the counseling record)
- **Query Parameters**:
  - `date`: Required, `date_format:Y-m-d` (e.g. `2026-07-28`)
- **Description**: Retrieves time slots for the specified date, marking each slot with `is_available: false` if a tentative or confirmed booking already exists.
- **Response Format**:
  ```json
  {
      "success": true,
      "message": "Available time slots retrieved.",
      "data": {
          "selected_date": "2026-07-28",
          "selected_date_formatted": "Selasa, 28 Juli 2026",
          "time_slots": [
              {
                  "slot_id": 101,
                  "time_range": "09:00 - 10:00 WIB",
                  "is_available": true
              },
              {
                  "slot_id": 102,
                  "time_range": "10:30 - 11:30 WIB",
                  "is_available": false
              }
          ]
      }
  }
  ```
