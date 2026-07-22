# [BE-2.2] Submit Schedule Booking API

## Overview

Endpoints enabling students to submit tentative booking requests for consultation slots and view booking status/details. Built with race-condition prevention via database pessimistic locking (`lockForUpdate`).

---

## API Endpoints

### 1. Submit Booking Request
- **Endpoint**: `POST /api/student/bookings`
- **Authentication**: Required (JWT Bearer Token)
- **Role**: `student` (Must own the external counseling record)
- **Request Body**:
  ```json
  {
      "counseling_id": 1,
      "slot_id": 101
  }
  ```
- **Description**: Places a 24-hour tentative hold on the requested consultation slot. Prevents double-booking via `lockForUpdate()`. Returns HTTP 409 Conflict if the slot is no longer available. Dispatches `BookingScheduleCreated` event.
- **Success Response Format (HTTP 200)**:
  ```json
  {
      "success": true,
      "message": "Jadwal berhasil diajukan. Menunggu konfirmasi psikolog.",
      "data": {
          "booking_id": 15,
          "status": "pending",
          "deadline_at": "2026-07-23T09:00:00+07:00",
          "slot": {
              "date": "2026-07-28",
              "time_range": "09:00 - 10:00 WIB"
          }
      }
  }
  ```
- **Conflict Response Format (HTTP 409)**:
  ```json
  {
      "success": false,
      "message": "Slot ini sudah diambil siswa lain. Silakan pilih slot lain."
  }
  ```

---

### 2. View Booking Detail
- **Endpoint**: `GET /api/student/bookings/{booking}`
- **Authentication**: Required (JWT Bearer Token)
- **Role**: `student` (Must own the booking record)
- **Description**: Fetches comprehensive detail of a specific booking schedule including psychologist profile, counselor info, and formatted date/time.
- **Response Format**:
  ```json
  {
      "success": true,
      "message": "Booking detail retrieved.",
      "data": {
          "booking_id": 15,
          "status": "pending",
          "psychologist_name": "Dr. Sarah Wijaya, M.Psi",
          "facility_name": "Klinik Psikologi Sejahtera",
          "counselor_name": "Bpk. Bambang, S.Pd",
          "time_slot_label": "09:00 - 10:00 WIB",
          "date_formatted": "Selasa, 28 Juli 2026",
          "location": null,
          "deadline_at": "2026-07-23T09:00:00+07:00",
          "created_at_formatted": "Senin, 22 Juli 2026, 09:00 WIB"
      }
  }
  ```
