# RULE OF ARCHITECT

This document defines the strict engineering standards and architectural decisions for the Laravel Backend. Agents and developers MUST adhere to these rules when modifying or creating new features.

## Philosophy
**"The Laravel Way"**: Maximize built-in Laravel features. Avoid over-engineering (e.g., Service-Repository patterns). Keep it simple, maintainable, and aligned with core framework capabilities.

---

## 1. Logic Separation (Replacing Fat Controllers)

*   **Action Classes:** Complex business logic (e.g., AI analysis, complex data transformation, multi-step operations) MUST be extracted into Action Classes.
    *   **Location:** `app/Actions/`
    *   **Structure:** Single public method (e.g., `handle()` or `execute()`).
*   **Traits:** Traits are STRICTLY for horizontal code reuse (e.g., helper methods like `ApiResponder`). Do NOT place domain or business logic inside Traits. Existing logic traits (`QuestionnaireTrait`) are considered technical debt.
*   **Data Passing:** Pass data from Controllers to Actions using pure arrays (e.g., `$request->validated()`), NOT the Request object.

## 2. Eloquent & Database

*   **Local Scopes:** Controller queries MUST NOT chain extensive conditions. Use Laravel Local Scopes within the Model to abstract queries (e.g., `User::withDetails()->active()->get()`).
*   **Soft Deletes:** ALL major tables (both existing and future) MUST utilize Laravel's `SoftDeletes` trait. Hard deletion is forbidden unless explicitly approved.
*   **Naming Convention:**
    *   **New APIs:** Database columns and API response keys MUST use `snake_case`.
    *   **Existing APIs:** Maintain the current format (mixed/camelCase) strictly to prevent breaking the Next.js Frontend.

## 3. Request & Validation

*   **Form Requests:** If validation requires more than 5 fields, it MUST be extracted into a Form Request (`app/Http/Requests/`).
*   **Inline Validation:** Simple validation (<= 5 fields) is permitted directly within the Controller, PROVIDED the validation logic occupies fewer than 10 lines of code.

## 4. Response & Pagination

*   **Dynamic Pagination & Search:**
    *   Endpoints returning lists must support dynamic pagination.
    *   If query parameters (e.g., `?page=`, `?search=`) are present, return paginated data with meta links.
    *   If no specific pagination/search query parameters are provided, fallback to returning the flat array (`->get()`) to maintain 100% backward compatibility with the existing Frontend.

## 5. Security & Authorization

*   **Laravel Policies:** All role-based access control and model-specific authorization MUST utilize Laravel Policies (`app/Policies/`). Do not use inline Gates for model CRUD logic.
*   **JWT Auth:** The system uses manual JWT. No refresh token mechanism is to be implemented.

## 6. Code Modernization

*   **PHP Enums:** Magic strings and hardcoded arrays for statuses, types, or categories are FORBIDDEN. Use PHP 8.1+ Enums.
*   **Exception Handling:** Controller methods should focus purely on the "Happy Path". Handle API errors globally (via Global Exception Handler) rather than cluttering Controllers with `try-catch` blocks.
*   **Side-Effects:** Post-action side-effects (e.g., sending emails, logging notifications) MUST be isolated using Laravel Events and Listeners. Do not stack unrelated operations within an Action Class.

## 7. Testing
*   Unit tests for Action classes are required ONLY if explicitly requested. Otherwise, rely on Feature tests for standard endpoint verification.
