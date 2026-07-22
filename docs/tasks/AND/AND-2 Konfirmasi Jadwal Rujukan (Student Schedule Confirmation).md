2. Feature: Konfirmasi Jadwal Rujukan (Student Schedule Confirmation)

The User's Goal


As a student, the user needs an interface to browse the available consultation time slots of the targeted Partner Psychologist and tentatively book a schedule that fits their availability.

What the User Sees (The Layout)

A dedicated booking page located at /booking/{referral_id} displaying available slots for the targeted Psychologist.

A date filter that automatically restricts selections to slots that are at least two days in the future (H+2 window) to ensure preparation time.

An informative message stating "Slot tersedia paling cepat tanggal X" (Slots are available starting from date X) if no immediate slots are open.

A confirmation modal that appears upon clicking a slot, summarizing the selected Date, Time, and Psychologist.

How the User Interacts (The Flow)

After granting digital consent, the student is directed to the scheduling page.

The student browses the available dates and selects a time slot.

The student reviews the details in the confirmation modal and clicks to lock in the booking.

The system notifies the student that the schedule is awaiting final verification from the Psychologist.

Data and Administrative Logic

State Machine: Upon selection, the system updates the referral status and sets the specific slot to a tentative state.

Race Conditions: The backend (Laravel) implements pessimistic or optimistic locking to ensure two students cannot successfully book the exact same slot simultaneously.

Notification: The system instantly dispatches an automated alert to the targeted Psychologist to verify the tentative booking.

