AND-6 Catat Hasil Konseling (Audit Trail)
1. The User's Goal

As a Guru BK, the user needs a seamless bridge to initiate a face-to-face counseling meeting directly from a high-risk text incident (Red/Yellow Zone), and subsequently record the session's clinical outcome immutably. This structured pipeline is crucial for fulfilling compliance with student safety protocols and the Permendikbudristek 46/2023 mandate.

2. What the User Sees (The Layout)

The Contextual Trigger: Inside the chat detail modal ("Lihat Balasan") at /bk/incidents, a prominent action button labeled "Ajukan Pertemuan Konseling" appears dynamically only if the student's text is flagged as Red Zone or Yellow Zone by the Flask NLP microservice.The Main Schedule Board: Located at /bk/jadwal-konseling, displaying a data table of appointments. This layout features a specific tab filter or visual badge labeled "Curhatan (Sistem NLP)" to isolate and prioritize sessions born from critical text incidents over regular/routine schedule entries.The Session Outcome Form: Located at /bk/sessions/log/[appointment_id], containing input fields for Session Mode (Face-to-Face, Video Call, Chat), Clinical Summaries, and Follow-up Actions.

3. How the User Interacts (The Flow)

The Invitation Phase: While reviewing a critical student chat log, the Guru BK clicks "Ajukan Pertemuan Konseling" inside the modal. They input a proposed date, time, and location, then hit submit.The Student Notification: The system automatically dispatches an In-App Invitation to the student’s Activity Center (Pusat Aktivitas > Tab Jadwal Konseling). The initial row status in the Guru BK's dashboard reads "Menunggu Konfirmasi Siswa" (Pending Student Confirmation).The Execution Phase: Once the student approves the schedule and the physical counseling session is concluded, the Guru BK clicks "Catat Hasil" on that specific row to open the Outcome Form.The Post-Submission Phase: The Guru BK fills out the clinical notes and submits the form, knowing the action is final. If a correction or update is required later, the user cannot edit the cell directly; instead, they must append a correction entry which is saved as a new version block to maintain audit integrity.

4. Data and Administrative Logic

API & Storage Pipeline: * Dispatching a meeting triggers POST /api/bk/incidents/{id}/schedule-meeting, creating an appointment row with source_type = 'nlp_incident' and a default status of pending_student.Submitting the final outcome notes triggers POST /api/bk/counseling-logs.Immutability & Version Control: Once a counseling note is saved, it is strictly read-only. Any subsequent updates are handled via an append-only architecture, pushing snapshots to a counseling_log_histories tracking table to maintain a transparent audit trail.Data Security: The text notes column inside the database must utilize Laravel's native Attribute Encryption to prevent leaking sensitive student mental health data in the event of a database breach.Authorization: The backend enforces strict Role-Based Access Control (RBAC). Only the authentic authenticated Guru BK can dispatch meetings and log outcomes for their assigned students; access from student endpoints to write or modify these logs is strictly blocked (403 Forbidden).


BE-6.1 Database Migration Extend Scheduling Schema & Create Outcome Logs Table
User Story: As a Developer, I want to update the scheduling tables to track appointment origins and create an encrypted log schema, ensuring data integrity for medical audits.

Specific Description: * Buat file migrasi untuk memperbarui tabel counseling_appointments atau jadwal_konseling. Tambahkan kolom: source_type (enum: 'regular', 'nlp_incident', default: 'regular') dan incident_id (fk, nullable, terhubung ke tabel incidents).

Buat tabel baru bernama counseling_logs. Atribut wajib: id, appointment_id (fk), student_id (fk), counselor_id (fk), session_mode (enum), clinical_notes (text/encrypted), dan resolution_status (enum: 'escalated', 'false_red', 'false_yellow').

Data Protection Rule: Terapkan Laravel Native Attribute Encryption pada model CounselingLog di kolom clinical_notes untuk mengamankan rekam medis obrolan siswa dari potensi kebocoran pangkalan data.

DoD: File migrasi berhasil dijalankan di database lokal, relasi model Eloquent terbentuk dengan aman, dan enkripsi atribut lolos uji unit testing.


BE-6.2 Dispatch Internal Meeting Proposal
User Story: As a Front-End System, I need an endpoint to save a counselor's appointment proposal into the database and automatically issue an in-app system notification to the student.

Specific Description: * Buat endpoint: POST /api/bk/incidents/{id}/schedule-meeting.

Payload menerima: proposed_date, proposed_time, dan location.

Logika Bisnis: Validasi hak akses Guru BK. Masukkan data ke tabel jadwal_konseling dengan mengeset source_type => 'nlp_incident' dan status awal pending_student. Picu sistem Notification bawaan Laravel untuk menyuntikkan data kartu undangan ke tabel notifikasi milik target student_id (akan dibaca oleh Pusat Aktivitas siswa).

DoD: Endpoint API berhasil memvalidasi input, menyimpan baris janji temu berstatus pending, dan membuat rekam notifikasi sistem dengan benar.


BE-6.3 Store Final Counseling Outcome & Lock Records
*As a Front-End System, I need a secure endpoint to save final session summaries along with their dynamic resolution status verdict as read-only data blocks.

Specific Description: * Buat endpoint: POST /api/bk/counseling-logs.

Aturan validasi: appointment_id (required|exists), clinical_notes (required|string|min:30), dan resolution_status (required|in:escalated,false_red,false_yellow).

Logika Proteksi: Kunci otentikasi hanya untuk pengguna dengan role counselor. Blokir akses dari entitas siswa (403 Forbidden). Setelah baris berhasil disimpan, set status janji temu terkait di tabel jadwal_konseling menjadi completed.

DoD: API berhasil memproses penyimpanan rekam hasil konseling, memblokir akses ilegal non-counselor, dan mengembalikan respons status 201 Created.

