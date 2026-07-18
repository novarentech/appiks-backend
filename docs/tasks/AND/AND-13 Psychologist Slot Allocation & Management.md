13. Feature: New Feature (Psychologist Slot Allocation & Management)

This feature ensures the Psychologist controls their own availability, feeding the available options into the Student's booking interface.

The User's Goal


As a Partner Psychologist, the user needs an interface to proactively allocate and manage specific consultation time slots dedicated exclusively to APPIKS school referrals. This allows them to separate school counseling availability from their general Puskesmas duties, ensuring students can only pick from pre-approved time blocks.

What the User Sees (The Layout)

A dedicated schedule management page located at /psikolog/slots featuring a weekly calendar or list view.

A "Tambah Jadwal" (Add Slot) form containing input fields for the Date, Start Time, and End Time.

A visual directory of existing allocated slots, color-coded by their current status: Tersedia (Available), Menunggu Konfirmasi (Tentative/Pending), and Terkonfirmasi (Confirmed).

A "Hapus" (Delete) action icon specifically for unbooked slots.

How the User Interacts (The Flow)

The Psychologist logs into their portal and navigates to the schedule management module.

The user reviews their upcoming week and uses the form to input specific hours they are available to receive school referrals.

The user submits the form, publishing these slots to the system.

If the user's real-world availability suddenly changes, they can click the delete icon on an available slot to immediately remove it from the student-facing booking interface.

Data and Administrative Logic

Database Storage: The published schedules are saved into a dedicated psychologist_slots table containing the psychologist_id, slot_date, slot_start_time, slot_end_time, and status.

Validation Rules: The backend strictly validates inputs to ensure Psychologists cannot create slots in the past.

Data Integrity (Safe Delete): The system completely restricts the "Delete" action to slots that possess an available status. If a slot is currently tentative (a student has selected it) or confirmed, the delete button is disabled or hidden to prevent accidental data loss and scheduling conflicts.

