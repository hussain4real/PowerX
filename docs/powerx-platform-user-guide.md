# PowerX Platform User Guide

## Overview

PowerX is a training-center platform for promoting courses, capturing inquiries, registering students, managing manual payments, running batches, tracking attendance and learning progress, conducting exams, issuing certificates, and reviewing operational dashboards.

The current MVP focuses on the core PowerX workflow:

1. Visitors discover courses and submit inquiries or registrations.
2. Staff qualify leads and approve registrations.
3. Finance issues invoices and approves manual payments.
4. Instructors manage batches, attendance, and practical assessments.
5. Students complete lessons and exams.
6. Management reviews reports and issues certificates for eligible students.

## Main access points

| Area | Path | Used by |
| --- | --- | --- |
| Public landing page | `/` | Visitors, prospects |
| Course catalog | `/courses` | Visitors, prospects, students |
| Course details and registration | `/courses/{course-slug}` | Visitors, prospects |
| Certificate verification | `/certificates/verify/{token}` | Public verifiers |
| Dashboard | `/dashboard` | Authenticated users |
| Admin panel | `/admin` | Authorized PowerX staff |
| Style guide | `/style-guide` | Internal review during build |

## Local demo data

For local evaluation, seed the platform with realistic fictional records:

```bash
php artisan db:seed --class=PowerXDemoSeeder
```

`php artisan db:seed` also loads this demo data outside production. The seeder is idempotent, so rerunning it refreshes the same demo workspace instead of creating duplicate course slugs, invoice numbers, payment references, or certificate numbers.

All demo users use the password `password`:

| Persona | Email | Use for |
| --- | --- | --- |
| Management | `management@powerx.test` | Dashboard, reports, certificate oversight |
| Admin | `admin@powerx.test` | Course setup, registrations, operations |
| Sales | `sales@powerx.test` | Leads, follow-ups, campaigns |
| Finance | `finance@powerx.test` | Invoices, manual payment review |
| Instructor | `instructor@powerx.test` | Assigned batches, attendance, practical comments |
| Student | `student@powerx.test` | Student portal, lessons, exams, certificates |
| Corporate | `corporate@powerx.test` | Corporate quotation and coordinator scenarios |
| Support | `support@powerx.test` | Communications and support workflows |

The demo workspace is named `PowerX Training Center` and includes fictional companies, published courses, packages, LMS modules, lessons, batches, sessions, attendance, invoices, payments, exams, certificates, communications, audit events, and renewal opportunities.

Demo data must not be treated as real customer data. It uses fictional `.test` email addresses and public-safe content inspired by PowerX marketing themes.

## Roles and responsibilities

| Role | Main responsibilities |
| --- | --- |
| Management | Reviews dashboards, oversees revenue, enrollments, certificates, and operational performance. |
| Admin | Manages users, roles, permissions, courses, settings, and day-to-day platform configuration. |
| Sales | Captures and follows up on leads, manages inquiries, and supports registration conversion. |
| Finance | Issues invoices, records manual payments, verifies payment proofs, and updates payment status. |
| Instructor | Manages batches, attendance, practical assessment outcomes, and course delivery records. |
| Student | Registers for courses, accesses approved course materials, completes lessons and exams. |
| Corporate | Represents a company or group inquiry and tracks company-linked training needs. |
| Support | Helps users, monitors communications, and handles operational support requests. |

Access in the admin panel depends on the role and permissions assigned to the user. If a staff member cannot open `/admin` or cannot see a feature, confirm that they have the correct PowerX role and the `Access admin panel` permission.

## Public course and inquiry workflow

Visitors can browse the catalog at `/courses`, open a course detail page, and either submit an inquiry or register interest in a course.

For inquiries, collect enough information to follow up:

- Name
- Email
- Mobile number when available
- Company name when applicable
- Course interest
- Preferred schedule or message
- Source or campaign information when available

For registrations, collect the student profile details needed for admissions:

- Full name
- Email
- Mobile number
- Profession or job title
- Company name when applicable
- Qatar location when available
- Preferred course or schedule
- Requested package when applicable

When a company name is supplied, the platform links the lead, student profile, or enrollment to a company record. If no company is supplied, the record remains an individual inquiry or registration.

## Sales and admissions workflow

Sales and admissions staff should use the admin panel to review incoming leads and registrations.

Recommended process:

1. Review new leads and confirm the requested course or training requirement.
2. Update lead status, owner, notes, and follow-up date.
3. Convert qualified prospects into registrations or enrollments.
4. Review pending enrollments for the correct course, package, company, and student profile.
5. Approve active enrollments only when the student is ready to proceed.
6. Use notes and communications to keep a history of follow-up actions.

Enrollment status and payment status control access to paid course features. Do not mark an enrollment as active and paid unless the finance process has been completed.

## Communication templates and reminders

Staff can use communication records to prepare consistent PowerX messages for email, WhatsApp-ready follow-ups, SMS notes, or phone-call history. The MVP includes configurable templates for registration confirmations, payment reminders, class reminders, certificate issuance, renewal reminders, and lead follow-ups.

Use this process:

1. Select the correct communication template and channel.
2. Confirm the linked lead, student profile, company, or user.
3. Review the generated subject and message before sending externally.
4. For WhatsApp, copy the prepared text or use the generated click-to-chat link where available.
5. Keep the communication status updated as draft, scheduled, sent, or failed.

The platform prepares message copy only. WhatsApp Business API, automated mail sending, and real-time notification delivery remain deferred until PowerX approves the provider and operational policy.

## Finance workflow

Finance currently supports manual payment operations. Online payment gateway selection is intentionally deferred for this MVP.

Use this process for manual payments:

1. Issue an invoice for the enrollment.
2. Confirm subtotal, discount, tax, total, due date, and invoice metadata.
3. Record the manual payment when the student or company provides payment details.
4. Upload proof for bank transfers, cheques, or other manual payment evidence when available.
5. Approve the payment only after verification.
6. Confirm that the invoice, payment transaction, and enrollment payment status are synchronized.

Approved payments can activate access windows for the enrollment. Pending payments should not unlock paid lessons, exams, or certificates.

## Course, LMS, and content workflow

Courses are structured as packages, modules, and lessons.

- Courses define the public catalog item, category, pricing context, duration, and publish status.
- Packages define commercial options such as fee, validity, attempt limits, and certificate inclusion.
- Modules organize lessons.
- Lessons contain learning content, downloadable materials, preview flags, and completion tracking.

Recommended content process:

1. Create or update the course.
2. Add the relevant packages.
3. Add active modules in the intended learning order.
4. Add active lessons under each module.
5. Mark only approved public courses as published.
6. Use private media collections for course files and student documents.

Paid course content should only be available to students with active, paid enrollments inside the configured access window.

## Batch, attendance, and practical assessment workflow

Training batches represent scheduled course delivery.

For each batch, track:

- Course
- Instructor
- Start and end dates
- Capacity
- Venue or delivery mode
- Schedule details

For each training session, instructors or authorized staff can record attendance for enrolled students. Attendance records support statuses such as present, late, absent, excused, and cancelled.

Practical assessment outcomes can be recorded against attendance records. Use the outcome, score, and comments fields to document the instructor's practical evaluation.

## Exam workflow

Exams are connected to courses and use active questions from the course question bank.

Before a student can start an exam, the platform checks that:

- The exam is active.
- The enrollment belongs to the same course.
- The enrollment is active and paid.
- The enrollment access period has started.
- The enrollment access period has not expired.
- The student has not exceeded the allowed attempt limit.

When submitting an attempt, the platform checks that:

- The attempt has not already been submitted.
- The attempt has not expired.
- At least one answer is present.
- Submitted questions belong to the exam course and are active.

The platform stores normalized answers, score, pass or fail result, duration, and submission time.

## Certificate workflow

Certificates should be issued only after eligibility is satisfied.

The platform checks that:

- The enrollment is active and paid.
- The selected package includes a certificate.
- The access period is valid.
- Active lessons are completed.
- At least one active exam attempt is passed.
- No failed practical assessment blocks certification.

When a certificate is issued, the platform generates a certificate number and public verification token. Public verification exposes only safe certificate details, such as the student name, course title, certificate number, issue date, expiry date, and status.

Use `/certificates/verify/{token}` for public certificate verification.

## Renewal and growth workflow

The platform tracks issued certificates with expiry dates and highlights renewal opportunities when a certificate is expired or within the renewal window. Renewal opportunities include the graduate, linked company coordinator when available, expiry date, and recommended next courses from the published course catalog.

Use this process:

1. Review renewal opportunities from dashboard growth metrics or certificate reports.
2. Confirm the graduate or corporate coordinator contact details.
3. Generate a renewal reminder communication when follow-up is needed.
4. Review the WhatsApp-ready or email-ready reminder before sending externally.
5. Use recommended courses to propose refresher, advanced, or related PowerX training.

Campaign and referral source data on leads should be kept accurate because it feeds growth reporting and helps identify which campaigns produce qualified or converted leads.

## Dashboard and reporting

The dashboard summarizes operational performance for the current team.

Current dashboard metrics include:

- Lead totals and lead status breakdowns
- Enrollment totals and enrollment status breakdowns
- Weekly revenue
- Pending payments
- Attendance counts
- Exam pass rate
- Issued certificates
- Renewal opportunities, overdue renewals, and tracked campaigns

Management should use these metrics for daily oversight and follow up with the responsible teams when a metric needs attention, such as pending payments, low attendance, or weak exam pass rates.

Authorized management and reporting users can export the operational report from the dashboard:

1. Use **CSV export** for spreadsheet review of lead source, sales pipeline, weekly revenue, course enrollment, attendance and practical, exam performance, certificate, and corporate account sections.
2. Use **PDF report** for printable management summaries and formal review packs.
3. Treat every export as sensitive business data. The platform records an audit event when a report file is generated.

## Security and data handling

Follow these operating rules:

- Assign staff only the roles and permissions they need.
- Keep payment proofs, student documents, certificates, and course files private unless intentionally exposed.
- Use certificate verification tokens instead of sharing internal certificate records.
- Do not approve payments without verified evidence.
- Do not issue certificates manually outside the eligibility workflow.
- Export operational reports only for authorized business use.
- Keep public course information accurate before publishing.

## Current MVP limitations

The current MVP intentionally defers several advanced features from the broader technical specification:

- Online payment gateway integration
- WhatsApp Business API integration
- AI assistant features
- Excel export workflows
- Queue dashboard and Horizon operations
- Real-time classroom notifications
- Advanced analytics and BI exports

These can be added later without changing the core course, enrollment, payment, exam, and certificate workflows.
