AND-7 Feature: CRUD Akun Psikolog Mitra
7. Feature: CRUD Akun Psikolog Mitra

The User's Goal As a Super Admin, the user needs a centralized management interface to securely register and manage the accounts of a new user role: the Partner Psychologist (Psikolog Mitra). This ensures external medical professionals from the Puskesmas can access the system to receive referrals without compromising the data isolation of the school's internal database.

What the User Sees (The Layout)

A directory table within the master control panel listing all registered Partner Psychologists, displaying columns for Name, Email, Assigned Puskesmas, and Action buttons (Edit/Delete).

A primary "Tambah Psikolog" (Add Psychologist) button to initiate the creation of a new account.

A dedicated modal or creation form requiring mandatory input fields: Full Name, associated Puskesmas, and a valid Email address.

How the User Interacts (The Flow)

The Super Admin logs into the system and navigates to the Master Management module.

To register a new psychologist, the user clicks "Tambah Psikolog" and fills out the required professional details and email address.

The user submits the form, and the system provisions the new account.

The Super Admin can utilize the Action buttons on the directory table to update a psychologist's details or revoke/delete their system access if the partnership concludes.

Data and Administrative Logic

Role Hierarchy: The Partner Psychologist accounts are strictly categorized under a new dedicated role located beneath the Super Admin hierarchy to prevent unauthorized access to administrative school data.

Authentication Basis: Account creation and future portal logins for the Psychologist are strictly validated using their registered email credentials.

Data Integrity: The backend ensures that the provided email address is unique across the entire platform to prevent credential overlapping or duplicate accounts.


[BE-7.1] Database Migration Schema for Psychologist Profiles
User Story: As a Developer, I want to extend the database schema to handle psychologist credentials and metadata, ensuring tight relational data integrity with the core user tables.

Specific Description: * Write and run database migrations for a separate profile mapping table: psychologist_profiles.

Attributes: id (bigint, pk), user_id (fk, users table with cascade delete), str_number (string, unique, indexed), specialization (string), institution_name (string, indexed), phone_number (string), and is_active (boolean, default: true).

Ensure the users table contains a corresponding role enum value for psychologist.

DoD: Migration files are successfully pushed to database environments, and Eloquent inverse relationships (User hasOne PsychologistProfile) pass structural unit testing.


[BE-7.2] RESTful API Endpoints for Psychologist Management
User Story: As a Front-End System, I need a standard set of secure RESTful endpoints to perform Create, Read, Update, and Delete operations on psychologist records.

Specific Description: * Build a resourceful controller handling endpoints:

GET /api/admin/psychologists (with built-in Eloquent pagination and optional keyword query string filtering).

POST /api/admin/psychologists (Creates a User row + PsychologistProfile row wrapped in a DB transaction).

PUT /api/admin/psychologists/{id} (Updates both credentials and medical attributes).

DELETE /api/admin/psychologists/{id} (Soft deletes or toggles is_active status to preserve historic data references in past referrals).

Enforce strict request validation: str_number must be unique, email must be a unique valid address inside the system.

DoD: API controller methods satisfy basic CRUD functionality, respond with standard JSON response formats, and successfully throw 422 errors for validation breaches.

