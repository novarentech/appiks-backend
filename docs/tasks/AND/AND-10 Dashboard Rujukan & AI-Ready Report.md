10. Feature: Dashboard Rujukan & AI-Ready Report

The User's Goal


As a Partner Psychologist, the user needs a comprehensive interface to read the pre-generated AI-Ready Report, utilizing a layout that helps them rapidly grasp the student's clinical context through an automated narrative summary prior to initiating a counseling session.

What the User Sees (The Layout)

A dedicated detail page located at /psikolog/referrals/{id}/summary.

A Header section displaying the student's actual credentials (Name, NIS, Class), the date the report was generated, and the LLM provider used.

Section 1: The LLM-generated narrative summary representing the clinical overview, displayed as static, read-only text.

Section 2: A collapsible raw payload section allowing the Psychologist to verify the underlying structured data.

An explicit disclaimer text stating: "Ringkasan ini dihasilkan AI dan tidak menggantikan asesmen klinis Anda".

How the User Interacts (The Flow)

The Psychologist opens a confirmed referral case from their dashboard.

The user reads the static narrative summary identifying the specific student and their crisis triggers.

The user clicks to expand the collapsible raw payload section if they need to verify the actual data points.

Data and Administrative Logic

Explicit Identity Rendering: Because the student has successfully granted Digital Consent in the previous stage, the system bypasses anonymization and directly passes the student's credentials to the dashboard UI for seamless clinical identification.

Consent Filtering: The data payload fed to both the LLM and the raw view is strictly filtered based on the student's active Digital Consent scope.

LLM Constraints: The prompt rigorously instructs the LLM to output a maximum of 200 words in formal Indonesian, summarizing facts without offering clinical diagnoses.

