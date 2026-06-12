# KConsulting Hub

KConsulting Hub is an internal business management portal built with PHP and
MySQL. It gives each department (Finance, HR, Marketing, Business
Development, IT, Clients, Projects) a dedicated workspace, plus a shared
dashboard, reporting/insights views, and a public-facing job application
form.

## Tech stack

- **PHP 8.x** (PDO + MySQLi via `PDO`), no framework
- **MySQL 8.x** (database: `kconsulting`)
- **Apache** via WAMP (developed/tested on WAMP64)
- Plain **HTML/CSS/JS** — no build step, no Node dependencies
- **PHPMailer** (bundled under `includes/PHPMailer/`) for outgoing email
- **TCPDF/FPDF-style** PDF generation (`includes/pdf_generator.php`,
  `includes/generate_pdf.php`) for quotations, invoices, and reports

## Directory structure

```
Consulting-Hub/
├── index.php              # Redirects to auth/login.php
├── dashboard.php          # Main landing page after login
├── profile.php            # User profile / account settings
├── apply.php               # Public job application form (no login required)
├── job_postings.php        # Job postings list (HR-managed, portal-wide)
├── candidates.php          # Candidate pipeline (HR-managed, portal-wide)
├── style.css                # Legacy/global stylesheet
│
├── auth/
│   ├── login.php
│   ├── logout.php
│   ├── forgot_password.php
│   └── reset_password.php
│
├── config/
│   ├── app.php            # ★ Central config — DB, SMTP, APP_URL, APP_NAME
│   ├── database.php        # PDO connection wrapper (Database class)
│   ├── session.php          # Session bootstrap & cookie hardening
│   ├── security.php          # Security helpers (CSRF, role checks)
│   ├── mail.php               # Mail helper bootstrap
│   └── create_tables.php       # One-off table creation helper
│
├── departments/
│   ├── finance.php          # Quotations, Invoices, POs, Revenue, Expenses
│   ├── hr.php                # Employees, Leave, Reviews, Job Postings, Candidates
│   ├── marketing.php          # Social posts, Email campaigns, Blog posts, Campaigns
│   ├── bd.php                  # Leads, Activities, Tasks (Business Development)
│   ├── it.php                   # IT assets & software licenses
│   ├── clients.php               # Clients, Contacts, Meetings
│   ├── client_detail.php          # Single-client drill-down view
│   ├── projects.php                # Project list/board
│   ├── project_detail.php           # Single-project drill-down view
│   ├── marketing_detail.php          # Single marketing-item drill-down view
│   ├── insights.php                   # Cross-department analytics dashboard
│   ├── reports.php                     # Reports with filters/export
│   └── finance_pdf.php                 # Finance PDF (quotation/invoice) generation
│
├── includes/
│   ├── header.php            # Shared page header / topbar
│   ├── sidebar.php            # Shared navigation sidebar
│   ├── _calendar_widget.php    # Dashboard calendar widget
│   ├── _my_work_list.php        # Dashboard "my tasks" widget
│   ├── functions.php             # Shared helper functions
│   ├── file_upload.php            # FileUpload class (CV + department uploads)
│   ├── ActivityLogger.php          # Audit/activity log writer
│   ├── page_tracker.php             # Page-view tracking
│   ├── email_service.php             # App-level email sending wrapper
│   ├── MailService.php                # PHPMailer wrapper
│   ├── pdf_generator.php               # PDF generation helpers
│   ├── generate_pdf.php                 # PDF generation entry point
│   └── PHPMailer/                        # Bundled PHPMailer library
│
├── admin/
│   └── activity_log.php       # Admin-only activity log viewer
│
├── api/
│   └── notifications.php       # AJAX endpoint for in-app notifications
│
├── db/
│   ├── kconsulting.sql           # Full schema + data dump (reference)
│   ├── kconsulting_export.sql     # Export dump (reference)
│   ├── kconsulting_migration.sql   # Combined migration dump (reference)
│   ├── thekcaar_kconsulting.sql     # Schema/export for production deployment
│   └── migrations/                   # ★ Incremental migrations, run in order
│       ├── 001_add_blog_posts_columns.sql
│       ├── 002_projects_department_and_it_tables.sql
│       ├── 003_it_dummy_data.sql
│       ├── 004_hr_user_link.sql
│       ├── 005_notifications.sql
│       ├── 006_password_resets.sql
│       ├── 007_revenue_proof_and_updated_at.sql
│       ├── 008_expense_updated_at.sql
│       └── 009_candidates_extended_fields.sql
│
├── css/
│   ├── main.css               # Shared app styling (sidebar, cards, tables, pagination)
│   └── login.css               # Auth pages styling
│
├── js/
│   ├── notification.js          # In-app notification polling/UI
│   └── list-controls.js          # Shared pagination + date-range filter component
│
├── uploads/
│   ├── cv_files/                 # Candidate CV/résumé uploads
│   ├── finance/                   # Revenue proof-of-payment / expense receipts
│   └── marketing/                  # Blog post featured images, etc.
│
├── img/                            # Static images / logos
├── attached_assets/                 # Misc reference assets
└── spec.md                           # Original project spec (historical reference)
```

## Requirements

- WAMP (or any Apache + PHP 8 + MySQL 8 stack)
- PHP extensions: `pdo_mysql`, `gd`, `fileinfo`, `mbstring`
- A MySQL database named `kconsulting`

## Setup / Installation

1. **Clone/copy the project** into your web root, e.g.
   `C:\wamp64\www\development\Consulting-Hub`.

2. **Create the database** and import the base schema:
   ```sql
   CREATE DATABASE kconsulting CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
   Import `db/kconsulting.sql` (or the appropriate dump for your environment,
   e.g. `db/thekcaar_kconsulting.sql` for the production deployment) using
   phpMyAdmin or the `mysql` CLI:
   ```bash
   mysql -u root kconsulting < db/kconsulting.sql
   ```

3. **Run the migrations** in `db/migrations/`, in numeric order (001 → 010),
   against the `kconsulting` database. These add columns/tables introduced
   since the base dump was created (notifications, password resets, IT
   assets, revenue/expense proof attachments, extended candidate fields,
   etc.):
   ```bash
   for f in db/migrations/*.sql; do mysql -u root kconsulting < "$f"; done
   ```

4. **Configure the app** — edit `config/app.php`. This is the **only** file
   you should need to change between environments:
   - `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` — database
     credentials.
   - `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`, `MAIL_FROM`,
     `MAIL_ADMIN_ADDR`, `MAIL_ADMIN_NAME` — outgoing mail settings (used for
     notifications and password resets).
   - `APP_NAME` — display name used in page titles/emails.
   - `APP_URL` is **auto-detected** from the request (protocol, host, and
     base path) — no manual configuration needed. It works unchanged on
     `localhost`, LAN IPs, and production domains, and is used to build
     absolute URLs for content (e.g. blog post images) that may be viewed
     from a different domain.

5. **Set folder permissions** — ensure the web server user can write to
   `uploads/` and its subfolders (`cv_files/`, `finance/`, `marketing/`, and
   any new department folders, which are created automatically on first
   upload).

6. **Access the app** at your configured URL, e.g.
   `http://localhost:8090/development/Consulting-Hub/`. You'll be redirected
   to `auth/login.php`.

## Roles & access control

Users have a `role` (`admin`, `manager`, `employee`) and a `department`.
Access is enforced via `config/security.php`:

- **Admins** can access and write to every department.
- **Managers** can access and write within their own department; admins can
  bypass per-record ownership checks (e.g. edit/delete records created by
  other users in their department).
- **Employees** have read access to their department and write access
  limited to their own records.

All state-changing requests are protected with CSRF tokens
(`Security::getCSRFTokenField()` / `Security::validateCSRFToken()`), and all
database queries use parameterized PDO statements.

## Key features by department

- **Finance** — Quotations, Invoices, Purchase Orders, Project Revenue, and
  Expenses, each with status workflows, PDF generation, file
  attachments/proof-of-payment, and a KPI dashboard. Lists support search,
  status filters, date-range filters, and pagination.
- **HR** — Employee records, leave requests, performance reviews, job
  postings, and candidate pipeline management (linked to the public
  `apply.php` form).
- **Marketing** — Social media post planner, email campaigns, blog posts
  (with featured-image upload and live preview), and marketing campaigns.
- **Business Development (BD)** — Lead tracking, activity timeline, and task
  management.
- **IT** — Asset inventory and software license tracking.
- **Clients** — Client records, contacts, and meeting scheduling, with a
  per-client detail/drill-down view.
- **Projects** — Project list and per-project detail/drill-down view, shared
  across departments.
- **Insights & Reports** — Cross-department analytics dashboards and
  filterable/exportable reports.
- **Dashboard** — Personalized landing page with a calendar widget,
  "my work" task list, and in-app notifications.

## File uploads

`includes/file_upload.php` provides:
- `FileUpload::uploadFile()` — candidate CV/résumé uploads (PDF/DOC/DOCX, 5MB
  max) saved to `uploads/cv_files/`.
- `FileUpload::uploadDepartmentFile($file, $department, $prefix)` — generic
  optional upload for department records (PDF/JPG/PNG/GIF/WEBP, 5MB max),
  saved to `uploads/<department>/` and returned as a relative path for
  storage in the database. Used for finance proof-of-payment/receipts and
  marketing blog post featured images (stored as a full `APP_URL`-based URL
  so it renders correctly even when the content is displayed on a different
  domain).
- `FileUpload::deleteDepartmentFile($relativePath)` — removes a previously
  uploaded department file (used when replacing or removing an attachment).

## Security notes

- CSRF protection on all forms via `Security::getCSRFTokenField()` /
  `Security::validateCSRFToken()`.
- All database access uses prepared statements (PDO).
- Session cookies are configured with `httponly`, `samesite=Lax`, and
  `secure` (auto-enabled over HTTPS) — see `config/session.php`.
- Uploaded files are validated by extension **and** MIME type, with a 5MB
  size limit.
- Role/department-based access control on every department page via
  `Security::canAccessDepartment()` / `Security::canWriteInDepartment()`.
