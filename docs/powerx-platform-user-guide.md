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

## Main access points and access boundaries

| Area | Path | Used by |
| --- | --- | --- |
| Public landing page | `/` | Visitors, prospects |
| Course catalog | `/courses` | Visitors, prospects, students |
| Course details and registration | `/courses/{course-slug}` | Visitors, prospects |
| Certificate verification | `/certificates/verify/{token}` | Public verifiers |
| Operations dashboard | `/{team}/dashboard` | Management and Admin only |
| Student portal | `/{team}/student-portal` | Students with a student profile or student role |
| Instructor portal | `/{team}/instructor-portal` | Instructors and authorized attendance users |
| Corporate portal | `/{team}/corporate-portal` | Corporate coordinators |
| Admin panel | `/admin` | Authorized PowerX staff |
| Team settings | `/settings/teams` | Management/Admin team owners only |
| Style guide | `/style-guide` | Internal review during build |

Students and corporate coordinators should not see operations dashboards, report exports, admin resources, team creation, or team-management pages. Staff roles without report permission land in the admin panel instead of the management dashboard.

## Local demo data

For local evaluation, seed the platform with realistic fictional records:

```bash
php artisan db:seed --class=PowerXDemoSeeder
```

`php artisan db:seed` also loads this demo data outside production. The seeder is idempotent, so rerunning it refreshes the same demo workspace instead of creating duplicate course slugs, invoice numbers, payment references, or certificate numbers.

All demo users use the password `password`:

| Persona | Email | Use for |
| --- | --- | --- |
| Test management user | `test@example.com` | Local management dashboard with populated demo records |
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

## Role-based operating guide

### Management

Management users use `/{team}/dashboard` as their landing page. They can see the operations dashboard, CSV/PDF report exports, admin panel, and team settings when they own or manage the team. Use this role for executive oversight, revenue review, enrollment trends, attendance performance, exam outcomes, certificate issuance, renewal opportunities, and audit-sensitive reporting.

Management should not bypass finance approval, certificate eligibility, or audit workflows. Use `test@example.com` locally when you want an immediately populated management dashboard.

### Admin

Admin users can access `/admin` and the operations dashboard when assigned the seeded Admin role. They manage platform configuration, PowerX courses, packages, modules, lessons, batches, exams, certificates, settings, and team administration where their team role allows it.

Admins should use team settings only for PowerX workspace administration. They should not use personal teams for operational demo review.

### Sales

Sales users land in `/admin` and see CRM/admissions resources such as leads, companies, student profiles, enrollments, and communication follow-ups. They should qualify prospects, update campaign/source information, maintain follow-up dates, and support registration conversion.

Sales users do not see the management dashboard, report exports, finance approval tools, team settings, student portal, instructor portal, or corporate portal.

### Finance

Finance users land in `/admin` and see admissions context plus finance resources such as invoices and payment transactions. They issue invoices, review manual payment evidence, approve verified payments, and confirm invoice/payment/enrollment synchronization.

Finance users do not see the management dashboard, report exports, team settings, student portal, instructor portal, or corporate portal unless their role is intentionally expanded.

### Instructor

Instructor users land on `/{team}/instructor-portal`. They can review assigned batches, session rosters, attendance status, practical outcomes, comments, and course resources. Instructors also have admin access to training delivery resources that support their assigned operational work.

Instructors do not see the management dashboard, report exports, student portal, corporate portal, or team settings.

### Student

Student users land on `/{team}/student-portal`. They can see only their own student profile, enrollments, paid access status, lesson progress, assigned schedule, exams, invoice/payment summary, certificate verification links, and time-limited lesson media links for active paid enrollments.

Students do not see team-level operational records, management metrics, report exports, admin panel, team switcher, team creation, team settings, instructor rosters, or other students' records.

### Corporate

Corporate users land on `/{team}/corporate-portal`. The MVP corporate portal uses Level 2 operational sharing: matched company profile, quotations, invoice/payment status, employee names, enrollment/course status, attendance summary, practical outcome, and issued certificate verification links.

Corporate users do not see student portal records by default, operations dashboards, report exports outside their company-scoped CSV, admin panel, team settings, team creation, instructor views, student contact details, documents, payment proofs, exam answers, audit metadata, internal approval notes, or finance internals.

### Support

Support users land in `/admin` and focus on communications, support follow-up, leads, registrations, and user assistance workflows. They help keep communication history accurate and route issues to sales, finance, instructors, or management.

Support users do not see the management dashboard, report exports, finance approval tools, team settings, student portal, instructor portal, or corporate portal.

Access in the admin panel depends on the role and permissions assigned to the user. If a staff member cannot open `/admin` or cannot see a feature, confirm that they have the correct PowerX role and the `Access admin panel` permission. If a user sees a menu item outside their role, treat that as an access-control bug and review the shared Inertia `can` flags and backend policy/permission gates.

## Admin form conventions

Filament admin forms are organized by workflow instead of raw database order. Use the visible sections, tabs, and wizard steps to complete records in the intended sequence: customer or learner first, then course/package or financial details, then approval, scheduling, publishing, or review state.

Status, type, method, currency, result, delivery mode, question type, and document status fields use controlled choices to prevent inconsistent values. Advanced metadata is available only in collapsed structured fields for support/import context.

Some fields are intentionally read-only in forms because they are controlled by audited workflows: payment approval status and approver, delivery sent timestamps, enrollment approval markers, attendance marker/assessor fields, exam attempt payloads, certificate approver, and certificate PDF timestamps. Use the relevant table action or workflow instead of manually editing those fields.

Upload fields in Filament use private Media Library collections. Staff can attach course cover images, course syllabi, lesson videos, lesson learning materials, student documents, and payment proof files from the relevant resource forms. Generated invoice and certificate PDFs remain read-only in forms and should be created or replaced through the controlled finance/certificate workflows.

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
4. Use email for automated delivery. WhatsApp-ready copy may be prepared for manual follow-up only until WhatsApp provider approval.
5. Keep the communication status updated as draft, scheduled, queued, delivered, retry, opted out, or failed.

The platform queues scheduled email communications with the database queue driver and records delivery lifecycle timestamps. WhatsApp Business API, SMS automation, and real-time notification delivery remain deferred until PowerX approves the provider and operational policy.

## Finance workflow

Finance currently supports manual payment operations. Online payment gateway selection is intentionally deferred for this MVP.

Use this process for manual payments:

1. Issue an invoice for the enrollment.
2. Confirm subtotal, discount, tax, total, due date, and invoice metadata.
3. Record the manual payment when the student or company provides cash, bank-transfer, or cheque payment details.
4. Upload proof for bank transfers, cheques, cash receipts, or other approved manual payment evidence when available.
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

Paid course content should only be available to students with active, paid enrollments inside the configured access window. Lesson media in the student portal is delivered through signed, authenticated download links and should not be shared as raw storage URLs.

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

Authorized staff can also manage schedule changes without bypassing the operational record:

1. Reschedule a session when the date, time, or venue changes.
2. Cancel a session with a reason when it should no longer appear as an active class.
3. Transfer an enrollment to another batch for the same team and course when capacity is available.
4. Record make-up classes for missed sessions, optionally linking the original missed attendance record.
5. Review the generated communication drafts before sending class updates externally.

These workflows create audit events and keep student/instructor portal schedules aligned with the latest batch and session records.

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

Exam analytics aggregate outcomes by course, exam, student, batch, instructor, question, topic, and weak-topic signals. Use these analytics to identify topics that need additional revision, classes that need instructor follow-up, and question-bank items that may need review.

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
- Exam pass rate, average score, and top weak topic
- Issued certificates
- Renewal opportunities, overdue renewals, and tracked campaigns

Management should use these metrics for daily oversight and follow up with the responsible teams when a metric needs attention, such as pending payments, low attendance, or weak exam pass rates.

Authorized management and reporting users can export the operational report from the dashboard:

1. Use **CSV export** for spreadsheet review of lead source, sales pipeline, weekly revenue, course enrollment, attendance and practical, exam performance, exam analytics, certificate, and corporate account sections.
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
- Advanced BI exports beyond GA/Meta Pixel and internal campaign ROI

These can be added later without changing the core course, enrollment, payment, exam, and certificate workflows.
