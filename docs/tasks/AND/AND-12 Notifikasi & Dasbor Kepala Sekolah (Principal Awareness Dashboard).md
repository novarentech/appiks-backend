12. Feature: Notifikasi & Dasbor Kepala Sekolah (Principal Awareness Dashboard)

The User's Goal


As a School Principal (Kepala Sekolah), the user needs a high-level monitoring dashboard and an urgent notification system to be instantly aware of critical (Red Zone) emergencies within the school. This ensures school leadership is informed and can oversee the intervention process without breaching the granular, sensitive privacy details handled exclusively by the Guru BK.

What the User Sees (The Layout)

A dedicated overview page located at /headteacher/dashboard displaying aggregate school statistics (e.g., total active cases, resolved interventions, and pending referrals).

An "Insiden Darurat Aktif" (Active Emergency Incidents) widget at the top of the dashboard.

In-app bell notifications and urgent email alerts specifically triggered for Red Zone cases.

A high-level detail view of an incident that shows the Timestamp, the current Status (e.g., "Sedang Ditangani", "Dirujuk ke Psikolog"), and the specific Guru BK assigned to the case.

Crucial Privacy Layout: The student's raw confession text (transcript curhat) and the Guru BK's specific clinical notes are explicitly hidden/masked from this view.

How the User Interacts (The Flow)

A student submits a confession that triggers the NLP Red Zone alert.

Simultaneously with the Guru BK, the Kepala Sekolah receives an urgent email and an in-app push notification.

The Principal clicks the notification and logs into the system.

The Principal views the high-level summary of the crisis to ensure a Guru BK has acknowledged and is actively handling the situation.

The Principal monitors the status tag of the incident over time until it is safely marked as resolved or referred, stepping in offline only if the Guru BK SLA (Service Level Agreement) for response time is breached.

Data and Administrative Logic

Parallel Dispatch: The backend ZoneNotificationDispatcher pushes the alert to the Principal's channel concurrently with the Guru BK's alert via Laravel Queue.

Strict Data Masking (RBAC): Role-Based Access Control logic rigorously enforces that the headteacher role cannot execute GET requests for sensitive database columns (like confession_text, ai_summary, or bk_sessions). They are strictly limited to viewing metadata and state machine statuses.

Escalation Monitoring: The system tracks the time elapsed since the Red Zone trigger. If the assigned Guru BK does not click "Acknowledge" within a specific timeframe 24 hours, the dashboard highlights the case in a critical color, prompting the Principal to intervene administratively.

Read Receipts: The system records an audit trail of when the Principal opens the notification, ensuring leadership accountability in crisis situations.

