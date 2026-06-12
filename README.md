# Appiks.id Backend - Mental Health Monitoring System

[![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![JWT](https://img.shields.io/badge/Authentication-JWT-000000?style=for-the-badge&logo=json-web-tokens&logoColor=white)](https://jwt.io)
[![Google Gemini](https://img.shields.io/badge/AI-Google%20Gemini-4285F4?style=for-the-badge&logo=google-gemini&logoColor=white)](https://ai.google.dev/)
[![Docker](https://img.shields.io/badge/Deployment-Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://www.docker.com/)

> **A project by Novaren Tech**

Appiks.id is a comprehensive backend solution developed to help schools foster safety, inclusivity, and emotional well-being among students. This RESTful API serves as the backbone for the Appiks ecosystem, providing secure data management, AI-driven insights, and detailed psychological reporting.

## 🏗 Architecture & Standardization

This project enforces strict architectural guidelines defined in the [Agent Rule of Architect](agent/RULE_OF_ARCHITECT.md). All modifications must adhere to these standards, including:
- **Action Classes**: Primary layer for complex business logic and third-party integrations (e.g., YouTube Meta, AI Analysis).
- **Laravel Policies**: Centralized authorization layer for all resource-based access control.
- **Form Requests**: Dedicated validation layer for handling complex input rules and data normalization.
- **PHP Enums**: Type-safe management of user roles (`UserRole`) and record statuses (`MoodStatus`, `ReportStatus`).
- **Event-Driven Design**: Side-effects (like updating priorities or rotating tokens) are isolated using Laravel Events & Listeners.
- **SoftDeletes**: Enforced across major tables to ensure data safety and audit trails.

*See `agent/technical-reference.md` for integration details with the Next.js frontend.*

## ⚙️ Environment Requirements

To run this project successfully, ensure the following environment variables are configured:
- `YOUTUBE_API_KEY`: Required for fetching video metadata from YouTube API.
- `JWT_SECRET`: Secret key for signing JSON Web Tokens.
- `DEFAULT_PASSWORD`: Default password used for generated student/teacher accounts.

## 🚀 Key Modules

### 🧠 Mental Health & Wellness
- **Mood Ecosystem**: Advanced mood tracking with streaks, trends, and history patterns (Weekly/Monthly).
- **Self-Help Suite**: Integrated journaling (Daily & Gratitude), Grounding Techniques, and Sensory Relaxation tools.
- **Digital Consent (Granular)**: Data-sharing permissions system allowing students to control individual access scopes with automated PII sanitization.
- **AI Integration**: Powered by **Google Gemini AI** for analyzing student input and generating background clinical reports.
- **Educational Content**: Management of therapeutic videos, articles, and mood-synced inspirational quotes.

### 📊 Institutional Analytics
- **Role-Based Dashboards**: Specific data visualizations for Super Admins, Headteachers, Teachers, and Counselors.
- **Mood Analytics**: Real-time mood distribution graphs and school-wide emotional trend analysis.
- **Reporting System**: Formal incident/report management with confirmation, scheduling, and closure workflows.
- **Sharing Platform**: A safe space for student expressions with moderation and reply capabilities.

### 🛡️ Core Infrastructure
- **Role-Based Access Control (RBAC)**: Secure multi-role architecture (Student, Teacher, Counselor, Admin, Psychologist, Super Admin).
- **Partner Psychologist Referral**: Standardized mappings to connect school counseling coordinates directly to external professional psychologists.
- **JWT Authentication**: Robust stateless authentication for mobile and web frontends.
- **Automated Documentation**: Live API documentation powered by **Dedoc Scramble**.
- **Data Operations**: Bulk user management and record exports via Excel (`maatwebsite/excel`).

---

## 🏢 About Novaren Tech
Novaren Tech is a digital innovation company that turns ideas into scalable, secure, and reliable technology.

- **Website**: [novarentech.com](https://novarentech.com)
- **Email**: [business@novarentech.com](mailto:business@novarentech.com)

Developed with passion by the Novaren Tech team.
