User Story: As a System, I want to automatically expire pending requests that have been ignored for 24 hours, so that slots are not held hostage indefinitely.

Specific Description: Create a Laravel Console Command or Job (e.g., ExpirePendingReferralsJob) scheduled to run via cron every 15 to 30 minutes. The logic must query all referrals with a pending_psychologist status where the difference between created_at and now() exceeds 24 hours. For each found record, update the referral status to expired, revert the associated time slot status back to available, and dispatch an "Expired Schedule" notification to both the Student's and Counselor's dashboards.

DoD: The scheduler is proven to automatically detect and expire lapsed schedule requests accurately in a staging/testing environment.

