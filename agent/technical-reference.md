# Technical Reference & Integration Map

This document serves as the central knowledge base for the APPIKS system, detailing the Backend architecture (Laravel) and its integration with the Frontend (Next.js).

## 1. System Overview

*   **Backend:** Laravel (API Layer)
*   **Frontend:** Next.js 16 App Router (Consumer Layer)

## 2. Integration Map (Backend ↔ Frontend)

### 2.1 Communication Protocol
*   The Frontend communicates with the Backend primarily via a centralized Axios/Fetch wrapper located in `frontend/src/lib/api.ts`.
*   **Base URL:** Configured via `API_BASE_URL` in `frontend/src/lib/config.ts`.
*   **Authentication Flow:**
    *   Backend utilizes **Manual JWT** generation (`Auth::claims()`).
    *   Frontend consumes this token using **NextAuth.js v5 Beta**.
    *   Token handling: There is **NO Refresh Token mechanism**. The token is manually parsed and stored in the NextAuth session.

### 2.2 API Response Contract
*   The Backend employs a unified response format utilizing the `ApiResponder` trait (`App\Traits\ApiResponder`).
*   **Standard Success Response:**
    ```json
    {
      "success": true,
      "message": "Success",
      "data": { ... } // or []
    }
    ```
*   **Legacy Data Format:** Existing endpoints may return *mixed/camelCase* formats (e.g., `isSafe`, `studentAnalyzer`). Frontend accommodates this in `frontend/src/types/api.ts`.
*   **Pagination Contract:**
    *   **Dynamic Pagination:** To maintain backward compatibility, endpoints return a flat array (`->get()`) by default.
    *   If a query parameter is provided (e.g., `?page=...` or `?search=...`), the Backend must return paginated metadata alongside the data. The Frontend client-side will handle this paginated payload.

## 3. Backend (Laravel) Architecture Details

### 3.1 Known Structural Patterns (To be Refactored based on Rules)
*   **Current State:**
    *   Fat Controllers containing business logic and complex Eloquent queries.
    *   Traits (`QuestionnaireTrait.php`, `GeminiTrait.php`) misused as Service/Logic layers.
    *   Misplaced classes (e.g., `StudentAnalyzer` within the Controllers directory).
*   **Target Architecture:**
    *   Migration towards **Action Classes** for business logic.
    *   Extensive use of built-in Laravel features (Local Scopes, Policies, Events) over custom Service-Repository abstractions.

### 3.2 Database Characteristics
*   **Soft Deletes:** System-wide requirement. All major models are required to utilize Laravel's `SoftDeletes` trait to prevent accidental data loss. This applies to existing tables and future schemas.

### 3.3 Notable Traits
*   `ApiResponder`: Handles consistent JSON response structures.
*   *(Legacy)* `QuestionnaireTrait`: Contains questionnaire processing logic (slated for Action class migration).
*   *(Legacy)* `GeminiTrait`: Contains AI interaction logic (slated for Action class migration with async considerations).
