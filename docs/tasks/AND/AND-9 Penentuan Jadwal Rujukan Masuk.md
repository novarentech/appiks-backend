9. Feature: Penentuan Jadwal Rujukan Masuk

The User's Goal


As a Partner Psychologist, the user needs an interface to review incoming tentative schedule bookings made by students, verify them against their actual Puskesmas calendar, and officially confirm or reject the proposed slot.

What the User Sees (The Layout)

A /psikolog/referrals/pending dashboard section displaying a list of incoming tentative referrals.

Each item displays the student's anonymized identifier, the Guru BK's initial notes, and the specific schedule the student selected.

A countdown timer on each pending request indicating the 24-hour deadline to respond.

Two primary action buttons on each card: "Konfirmasi" (Confirm) and "Tolak" (Reject).

How the User Interacts (The Flow)

The Psychologist receives a notification and logs into their portal to view the pending booking.

The user cross-references the student's requested slot with their own external professional calendar.

The user clicks "Konfirmasi" to finalize the booking, or "Tolak" (which prompts for a required rejection reason) if there is a sudden schedule conflict.

Data and Administrative Logic

Confirmation Loop: Clicking confirm updates the slot and referral status to confirmed and automatically triggers the system to dispatch final notifications to both the Student and the Guru BK.

Auto-Expire Mechanism: If the Psychologist fails to respond within the 24-hour window, a scheduled background job will automatically expire the tentative slot, revert its availability status, and notify all parties that the booking has lapsed.

