# [BE-7.2] RESTful API Endpoints for Psychologist Management

## Overview

A secure set of RESTful API endpoints is provided for Super Admins to perform full CRUD and status-toggle operations on Partner Psychologist (Psikolog Mitra) accounts.

---

## Authorization & Security

All psychologist management endpoints are restricted to users with the **Super Admin** role.

- **Policy**: `app/Policies/PsychologistPolicy.php`
- **Provider Registration**: Registered to the `PsychologistProfile` model in `app/Providers/AppServiceProvider.php`.
- **Enforcement**: Mapped using `Gate::authorize()` within the controller.

---

## Endpoint Specifications

### 1. List Partner Psychologists
- **Endpoint**: `GET /api/admin/psychologists`
- **Headers**: `Authorization: Bearer <token>`
- **Query Parameters**:
  - `search` (string, optional): Filter by name, email, STR number, or institution name.
  - `page` (integer, optional): Page number.
  - `limit` (integer, optional): Items per page (default: 10).
- **Behavior**:
  - If `page` or `limit` is present, returns paginated metadata with standard Laravel pagination links.
  - If no pagination parameters are present, returns a flat collection array of records to preserve backward compatibility.
- **Response (200 OK)**:
  ```json
  {
    "status": "success",
    "data": [
      {
        "id": 5,
        "name": "Dr. Budi Santoso, M.Psi., Psikolog",
        "username": "budi.santoso@klinik.id",
        "identifier": "STR-PSI-00201",
        "role": "psychologist",
        "psychologist_profile": {
          "id": 1,
          "user_id": 5,
          "str_number": "STR-PSI-00201",
          "specialization": "Mental Health",
          "institution_name": "Klinik Sejahtera",
          "phone_number": "081234567890",
          "is_active": true
        }
      }
    ]
  }
  ```

---

### 2. Create Partner Psychologist
- **Endpoint**: `POST /api/admin/psychologists`
- **Headers**: `Authorization: Bearer <token>`
- **Request Body**:
  ```json
  {
    "name": "Dr. Budi Santoso, M.Psi., Psikolog",
    "email": "budi.santoso@klinik.id",
    "str_number": "STR-PSI-00201",
    "institution_name": "Klinik Sejahtera",
    "specialization": "Mental Health", // optional
    "phone_number": "081234567890"     // optional
  }
  ```
- **Validation** (`StorePsychologistRequest`):
  - `email` must be valid and unique across `users.username`.
  - `str_number` must be unique across `users.identifier` and `psychologist_profiles.str_number`.
  - `phone_number` must be unique across `users.phone`.
- **Default Password**: Accounts are created with a default password of `'password123'`.
- **Response (200 OK)**:
  ```json
  {
    "status": "success",
    "message": "Success creating partner psychologist account",
    "data": { ... }
  }
  ```

---

### 3. Update Psychologist Profile
- **Endpoint**: `PUT /api/admin/psychologists/{psychologist_id}`
- **Headers**: `Authorization: Bearer <token>`
- **Request Body**:
  ```json
  {
    "name": "Dr. Budi Santoso, M.Psi., Psikolog",
    "email": "budi.santoso@klinik.id",
    "str_number": "STR-PSI-00201",
    "institution_name": "Klinik Sejahtera",
    "specialization": "Clinical Psychology",
    "phone_number": "081234567890",
    "password": "newsecurepassword" // optional
  }
  ```
- **Validation** (`UpdatePsychologistRequest`):
  - Enforces the same validation rules as the creation endpoint but safely ignores the current psychologist's IDs.
- **Response (200 OK)**:
  ```json
  {
    "status": "success",
    "message": "Success updating partner psychologist profile",
    "data": { ... }
  }
  ```

---

### 4. Toggle Active Status
- **Endpoint**: `PATCH /api/admin/psychologists/{psychologist_id}/toggle`
- **Headers**: `Authorization: Bearer <token>`
- **Behavior**: Toggles the profile's `is_active` boolean field to temporarily grant or revoke system access.
- **Response (200 OK)**:
  ```json
  {
    "status": "success",
    "message": "Success toggling partner psychologist active status",
    "data": { ... }
  }
  ```

---

### 5. Soft Delete Account
- **Endpoint**: `DELETE /api/admin/psychologists/{psychologist_id}`
- **Headers**: `Authorization: Bearer <token>`
- **Behavior**: Soft deletes both the `User` and `PsychologistProfile` records to keep historical references intact.
- **Response (200 OK)**:
  ```json
  {
    "status": "success",
    "message": "Success deleting partner psychologist account"
  }
  ```

---

## Under-the-Hood Actions

Logic operations are isolated into reusable single-responsibility Action classes to avoid controller bloat:

1. **`StorePsychologistAction`**: Executes inside a `DB::transaction()` block. Creates a core `User` (using email as `username` and STR as `identifier`) and inserts their child `PsychologistProfile`.
2. **`UpdatePsychologistAction`**: Executes inside a `DB::transaction()` block. Safely updates both `User` credentials (optionally hashing a new password) and their child `PsychologistProfile`.
3. **`DeletePsychologistAction`**: Performs soft-deletes on both `User` and `PsychologistProfile` models within a single database transaction.
4. **`TogglePsychologistStatusAction`**: Negates the value of the profile's `is_active` boolean attribute.
