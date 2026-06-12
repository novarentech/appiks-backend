# AND-1 Manajemen Persetujuan Data (Digital Consent Granular)
The User's Goal As a student, the user needs a secure interface to grant granular Digital Consent, controlling exactly which specific personal data scopes a Partner Psychologist is allowed to view.

What the User Sees (The Layout)

A dedicated consent page located at /consent/{referral_id}.

A list of specific data scopes: "Riwayat mood 30 hari terakhir" (mood history of the last 30 days), "Kutipan teks curhat yang memicu Red Zone" (masked Red Zone confession excerpts), "Catatan asesmen Guru BK" (Counselor assessment notes), "Log minta konseling" (logs of requesting counseling), and "Log menolak konseling" (logs of refusing counseling).

A "Setuju dan Lanjutkan" (Agree and Continue) primary button that remains visually disabled and unclickable until the user selects at least one checkbox.

A separate consent management page at /consent/{id}/manage displaying currently active permissions alongside a prominent revoke button.

How the User Interacts (The Flow)

The student receives a notification and opens the consent page.

The student reviews the options and checks the boxes corresponding to the data they feel comfortable sharing with the psychologist.

The student clicks "Setuju dan Lanjutkan" to submit their preferences and proceed with the referral process.

Data and Administrative Logic

Granting Consent & Identity Release: When the user agrees, the system hits the POST /api/consents endpoint. By granting this consent, the student officially authorizes the system to unmask their basic credentials (Name, NIS) to the destination Psychologist.

Asynchronous AI Trigger: The successful submission of this consent immediately triggers a background job (Queue) that feeds the strictly selected data scopes—alongside the student's real identity—into the LLM to generate the AI-Ready Report before the student even books a slot.

Security & Authorization: Strict backend validation ensures that only the specific student tied to the referral_id can view, grant, or revoke these permissions.


## [BE-1.1] Dashboard & Activity Center API (Read-Only)
User Story: As a Front-End System, I need secure endpoints to fetch the student's referral list and the associated psychologist data to render the UI.

Specific Description: * Create GET /api/siswa/dashboard/widgets to supply summary data for the Homepage.

Create GET /api/siswa/referrals for the Activity Center tab.

Mandatory Validation: The query must be strictly filtered using where('student_id', auth()->id()) to prevent data leakage between students.

Eager load the relations: psychologist_profile (for name and institution) and counselor (the referring teacher).

DoD: API successfully returns structured JSON matching the UI Card requirements, with strict authorization checks passing.


## [BE-1.2] Consent Update API & Headless Data Generator
User Story: As a Student, I want my personally identifiable information (PII) stripped from the system the moment I consent, so that my identity is completely hidden from the AI model.

Specific Description: * Create PATCH /api/siswa/referrals/{id}/consent accepting a boolean is_granted payload.

If false: Update the DB status to consent_rejected.

If true: Update the DB status to consent_granted.

Headless Logic (Critical): Fetch the student's raw journal/chat logs. Programmatically strip all PII (True Name, Student ID/NIS, Class/Grade). Package this sanitized text into a Headless Data variable/DTO.

DoD: Database status updates correctly, and the headless generator function is unit-tested to prove zero PII is passed forward.


## [BE-1.3] Background Job for AI Clinical Summary
User Story: As a System, I want to generate the AI summary in the background so that the student doesn't experience long loading screens after clicking "Agree".

Specific Description: * Create a Laravel Job GenerateAISummaryJob that is dispatched immediately after [BE-1.2] completes successfully (if consent was granted).

Pass the sanitized Headless Data into this job, construct the prompt, and asynchronously hit the LLM API (Gemini/OpenAI).

Catch the JSON response and store it in the clinical_summaries table, linked back to the referral_id.

DoD: Job successfully enters the Queue/Worker, LLM API executes using only anonymized data, and the clinical report saves to the database.

