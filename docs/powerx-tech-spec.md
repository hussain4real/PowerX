
Technical Specification

PowerX Training Center Web Application

| **Client / product** | PowerX Training Center / PowerX Electric Inc.                                                                        |
| -------------------- | -------------------------------------------------------------------------------------------------------------------- |
| **Prepared by**      | Aminu Hussain                                                                                                        |
| **Version**          | 0.1 - Draft for technical validation                                                                                 |
| **Date**             | May 24, 2026                                                                                                         |
| **Purpose**          | Technical handoff document describing the selected stack, packages, architecture, deployment model, and QA approach. |
| **Related BRS**      | PowerX_Training_Center_BRS.docx                                                                                      |

# 1\. Executive Technical Summary

PowerX should be implemented as a separate Laravel 13/Vue/Inertia application for training-center operations, CRM, LMS, exams, payments, certificates, and reporting. It will use the same approved technology baseline as Farmwell while keeping its own codebase, database, storage, deployment, and business rules.

**Primary technical decision**

PowerX keeps team support available for organization/admin grouping and future branches, but v1 treats PowerX as one client application. The main product UI is Inertia/Vue, internal operations use Filament, and public certificate verification remains accessible without exposing private student data.

# 2\. Shared Technology Baseline

Both products should be built with the same Laravel/Vue/Inertia architecture so the implementation team can reuse delivery patterns, deployment automation, testing strategy, and operational runbooks while keeping each product as a separate application.

| **Layer**           | **Selected technology**                                                                                     | **Purpose**                                                                                                                    |
| ------------------- | ----------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------ |
| Backend framework   | Laravel 13.x on PHP 8.3+.                                                                                   | Primary application framework, routing, validation, queues, scheduler, notifications, policies, and Eloquent ORM.              |
| Database            | PostgreSQL.                                                                                                 | Primary relational database for tenant data, reporting queries, JSON metadata, and future geospatial expansion where needed.   |
| Cache and queues    | Database queue for MVP; Redis/Horizon can be introduced later.                                              | Background jobs, email, report generation, media processing, notification retries, and queue visibility.                       |
| Frontend            | Laravel official Vue starter kit with Inertia, Vue 3 Composition API, TypeScript, Tailwind, and shadcn-vue. | Main authenticated app experience with server-side Laravel routes and modern Vue pages.                                        |
| Authentication      | Built-in Laravel auth through Fortify; passkeys enabled with Fortify and laravel/passkeys.                  | Email/password, session auth, passkeys, password reset, email verification, and auth throttling without WorkOS AuthKit.        |
| Tenancy             | Laravel starter-kit teams.                                                                                  | Team/workspace context for organization scoping, member invitations, current-team switching, and tenant-aware policies.        |
| Admin panels        | FilamentPHP 5.x.                                                                                            | Internal admin, operations, dashboards, tables, forms, and back-office workflows.                                              |
| Authorization       | spatie/laravel-permission v7 plus Laravel policies.                                                         | Role and permission management layered with model policies for tenant and record-level access control.                         |
| Media management    | spatie/laravel-medialibrary v11.                                                                            | Model-linked files, receipts, certificates, images, PDFs, private media collections, and derived thumbnails.                   |
| Audit logging       | spatie/laravel-activitylog v4.                                                                              | Sensitive action history for approvals, financial edits, certificate issuance, role changes, and data exports.                 |
| DTOs and typed data | spatie/laravel-data v4.                                                                                     | Structured request/response data, typed DTOs, validation support, and optional TypeScript generation.                          |
| Reports and exports | spatie/laravel-pdf v2 and maatwebsite/excel 3.1.x.                                                          | PDF statements, certificates, management reports, CSV/XLSX imports and exports.                                                |
| Realtime            | Laravel notifications by default; Laravel Reverb only for true realtime screens.                            | Realtime status updates are optional and should not be added until a workflow clearly benefits from live updates.              |
| Testing and quality | Pest v4, Pest browser tests, Laravel Pint, Larastan/PHPStan, TypeScript checks, and Vite build checks.      | Automated feature, unit, browser, static analysis, formatting, and build validation.                                           |
| Deployment          | Hetzner or Laravel Cloud deployment target.                                                                 | Production needs its own repo/domain, database, database queue worker, scheduler, S3-compatible object storage, backups, and monitoring. |

# 3\. Package Matrix

| **Area**            | **Packages / tools**                                                                                       | **Implementation note**                                                                                                                                   |
| ------------------- | ---------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Laravel core        | laravel/framework:^13.0, laravel/fortify, laravel/passkeys, inertiajs/inertia-laravel                      | Create each app from the official Vue/Inertia starter kit with teams enabled. Use built-in auth/Fortify, not WorkOS AuthKit.                              |
| Admin               | filament/filament:^5.0                                                                                     | Use for internal admin panels, resource management, dashboards, operational tables, and controlled back-office workflows.                                 |
| Access control      | spatie/laravel-permission:^7.0                                                                             | Use roles/permissions for broad capabilities; use Laravel policies for tenant, ownership, and record-level decisions.                                     |
| Media               | spatie/laravel-medialibrary:^11.0                                                                          | Store model-linked media in named collections with private disks and signed/temporary access where needed.                                                                                           |
| PDF and exports     | spatie/laravel-pdf:^2.0, maatwebsite/excel:^3.1                                                            | Use PDF generation for formal statements/certificates/reports; use Excel/CSV for operational exports and imports.                                         |
| Queues and realtime | Laravel database queue for MVP; laravel/horizon and laravel/reverb later if needed                         | Run email/report/media jobs on the database queue first. Horizon requires Redis and should be introduced only if queue volume or monitoring needs justify it. |
| Frontend            | @inertiajs/vue3, vue, typescript, tailwindcss, shadcn-vue, lucide-vue-next                                 | Use Vue pages for product UI, shadcn-vue for controls, and lucide icons for actions.                                                                      |
| Testing and quality | pestphp/pest:^4, pestphp/pest-plugin-laravel, pestphp/pest-plugin-browser, laravel/pint, larastan/larastan | Feature/unit/browser tests, code style checks, static analysis, TypeScript checks, and Vite build must pass before release.                               |

# 4\. Shared Architecture Pattern

- Use Laravel routes/controllers/actions as the primary application boundary, returning Inertia Vue pages for the authenticated web app.
- Keep domain logic in service/action classes rather than placing business rules inside controllers, Filament resources, or Vue components.
- Use Filament panels for admin and operations users; use Inertia/Vue for the main product experience seen by customers, students, investors, farmers, and staff.
- Store files through Media Library on private storage by default; expose public files only when the business requirement explicitly allows it.
- Deliver private lesson media through authenticated, signed application routes that re-check student ownership, enrollment payment, enrollment status, and access-window rules before streaming the file.
- Dispatch slow or retryable work to queues: emails, WhatsApp callbacks, media conversions, report generation, certificate/report PDFs, imports, exports, and webhook follow-up.
- Use policies, permissions, and current-team scope on every business model that belongs to a tenant or organization.

# 5\. Deployment and Operations Baseline

| **Component** | **VPS/Docker expectation**                                                    | **Operational requirement**                                                                                     |
| ------------- | ----------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------- |
| App runtime   | Nginx plus PHP-FPM container or equivalent VPS service layout.                | Deploy each product independently with its own environment variables and release directory/container image.     |
| Database      | PostgreSQL per application.                                                   | No shared production database between Farmwell and PowerX. Enable nightly backups and tested restore procedure. |
| Cache/Redis   | Database/cache defaults for MVP; Redis can be added later.                    | Redis is not required for MVP because the approved queue driver is database. Add Redis only for cache scale, Horizon, or Reverb. |
| Workers       | Dedicated database queue worker.                                              | Run under Supervisor/systemd/Docker service with restart policy and failed-job monitoring; add Horizon only if Redis is introduced. |
| Scheduler     | Laravel scheduler process or cron invoking schedule:run.                      | Required for reminders, renewals, report jobs, cleanup, and recurring notifications.                            |
| Storage       | S3-compatible object storage or isolated server storage path.                 | Separate buckets/prefixes per product and environment; private default visibility.                              |
| PDF runtime   | spatie/laravel-pdf driver selected during infrastructure setup.               | Browsershot/Chrome gives best CSS fidelity; DOMPDF can be used where zero external binaries are preferred.      |
| Monitoring    | Application logs, uptime checks, queue/failed-job alerts, and error tracking. | Sentry or equivalent is recommended but can be finalized during implementation procurement.                     |

# 5.1 Production Launch Readiness Checklist

This checklist covers the BRS NFR-04/NFR-05/NFR-06 launch hardening work. Production launch still requires named client owners, approver names, and dates.

| **Area** | **Readiness requirement** |
| -------- | ------------------------- |
| Environment | Confirm `APP_ENV=production`, `APP_DEBUG=false`, HTTPS `APP_URL`, passkey origin, database queue connection, mail sender, storage disk, PDF runtime, scheduler, and queue worker process. |
| Backups and restore (NFR-04) | Back up the database plus private media/PDF storage for student profiles, payments, invoices, exams, certificates, audit records, and lesson files. Record retention, alert owner, and restore-test cadence. |
| Restore test (NFR-04) | Restore into a non-production environment and spot-check login, catalog, registration, payment record, lesson media download, exam attempt, certificate PDF, and public certificate verification. |
| Access review | Re-check public, student, instructor, corporate, sales, finance, support, admin, and management routes so paid content, reports, Filament resources, team management, and private media stay least-privilege. |
| Performance and analytics (NFR-05) | Test homepage, course catalog, registration, corporate quotation, login, and certificate verification on mobile. GA/Meta Pixel scripts must stay optional and non-blocking. |
| Language scope (NFR-06) | English-only MVP is the approved default. Arabic/RTL public pages, forms, PDFs, and notifications stay post-launch unless PowerX assigns translation and RTL review owners. |
| Go/no-go | Launch only after legal issuer, catalog/pricing/tax, certificate wording, sender DNS, host/domain/SSL, backup owner, admin owners, support owner, and rollback plan are signed off. |

# 6\. Security and Engineering Controls

- Use HTTPS only in production and ensure passkeys are configured against the correct production origin/domain.
- Use Fortify rate limiting and Laravel validation on all auth and sensitive form endpoints.
- Use team-aware authorization checks for every tenant-scoped record and never trust client-supplied team IDs without policy verification.
- Use private media storage for receipts, investor documents, payment proofs, course materials, and certificates unless explicitly public.
- Use activity logs for financial approvals, payment status changes, certificate issuance, report exports, role changes, and destructive actions.
- Use soft deletes for core business records where auditability matters; permanent deletion should be restricted to platform administrators.
- Apply dependency review during project setup because package compatibility can drift after this specification date.

# 7\. Testing and Acceptance Baseline

| **Test type**   | **Required coverage**                                                                            | **Acceptance signal**                                                                                        |
| --------------- | ------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------ |
| Unit tests      | Domain calculations, status transitions, policies, and service/action classes.                   | Pest test suite passes locally and in CI.                                                                    |
| Feature tests   | Authenticated workflows, validation, permissions, media uploads, reports, webhooks, and exports. | Critical happy paths and denial paths are covered.                                                           |
| Browser tests   | Main Inertia/Vue flows that users depend on.                                                     | Pest browser tests cover login, dashboard, core create/edit flows, and public verification where applicable. |
| Static analysis | PHPStan/Larastan, TypeScript, Vite build, and Pint.                                              | CI fails on type, build, format, or analysis errors.                                                         |
| Security tests  | Unauthorized tenant access, private media access, role escalation, webhook signature handling.   | Regression tests prove records cannot leak across teams or roles.                                            |

# 8\. PowerX Product Architecture

| **Area**         | **Technical design**                                                                                                          | **Key controls**                                                                                                     |
| ---------------- | ----------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------- |
| Public website   | Laravel/Inertia public pages for course catalog, landing pages, lead forms, and certificate verification.                     | Public routes must be fast, cache-friendly, and safe for ad traffic.                                                 |
| Student app      | Inertia/Vue authenticated portal for registrations, course access, schedules, exams, results, certificates, and receipts.     | Course material and assessments require active enrollment and payment/approval status.                               |
| Admin operations | Filament panels for sales, finance, instructors, operations, and management.                                                  | Panel access is role-restricted and sensitive actions are logged.                                                    |
| LMS and exams    | Laravel domain services for course content, progress, question banks, exam attempts, attendance, and certificate eligibility. | Exam/certificate rules must be configurable per course/package.                                                      |
| Payments         | Internal PaymentGateway abstraction with gateway-specific adapters.                                                           | Provider can be Stripe/Cashier, MyFatoorah, PayTabs, or another approved gateway without rewriting enrollment logic. |
| Certificates     | Generated PDF certificates with unique number and public verification URL/QR.                                                 | Verification page exposes only approved certificate validity fields.                                                 |

# 9\. PowerX Module Map

| **Module**             | **Main responsibilities**                                                                                                | **Primary packages / services**                                                      |
| ---------------------- | ------------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------ |
| Users and roles        | Management, admin, sales, finance, instructor, student, corporate users, support users.                                  | Laravel starter auth, Fortify/passkeys, Spatie Permission, policies.                 |
| CRM and leads          | Lead capture, source tracking, campaign UTMs, pipeline statuses, follow-up tasks, conversion outcome.                    | Inertia/Vue, Filament, notifications, Activitylog.                                   |
| Registration           | Student profiles, company accounts, corporate bulk enrollment, quotations, course selection.                             | Laravel Data DTOs, policies, PDF/Excel exports.                                      |
| Course/LMS             | Courses, packages, modules, lessons, videos, PDFs, pass-question materials, access rules.                                | Media Library, private storage, Inertia/Vue student portal.                          |
| Batches and attendance | Class schedules, instructors, venues, capacity, attendance, practical assessments.                                       | Filament resources, notifications, exports.                                          |
| Exams                  | Question bank, mock exams, attempts, pass marks, randomization, analytics.                                               | PostgreSQL, service classes, Pest feature/browser tests.                             |
| Payments and invoices  | Online checkout adapter, manual payment proof, receipts, invoices, reconciliation, refunds where supported.              | PaymentGateway interface, Cashier/Stripe optional adapter, Media Library for proofs. |
| Certificates           | Eligibility rules, certificate numbers, PDF generation, QR/public verification, renewals.                                | spatie/laravel-pdf, Media Library, public verification route.                        |
| Reports                | Lead source, pipeline, weekly revenue, enrollment, attendance, exam performance, certificate, corporate account reports. | PDF/Excel exports, queued jobs, dashboards.                                          |

# 10\. PowerX Payment Gateway Design

PowerX includes online payment support, but the final provider remains a client decision. The implementation must isolate provider-specific logic behind an internal adapter so the application domain talks to a stable payment interface.

Current implementation note: PowerX only includes the internal `PaymentGateway` seam, checkout/webhook/reconciliation status DTOs, and a null adapter that logs redacted metadata without creating gateway sessions. Real provider credentials, checkout and webhook callback URLs, refunds/voids, legal/tax wording, and settlement/reconciliation details remain gated for client sign-off.

| **Interface / record** | **Required behavior**                                                                                                | **Notes**                                                                                           |
| ---------------------- | -------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------- |
| PaymentGateway         | Create checkout/session, verify callback/webhook, fetch payment status, request refund/void if provider supports it. | Concrete adapters may include Stripe/Cashier, MyFatoorah, PayTabs, or bank-transfer manual adapter. |
| PaymentTransaction     | Stores invoice, student/company, amount, currency, provider, reference, status, metadata, and timestamps.            | Do not use provider IDs as the internal primary key.                                                |
| ManualPayment          | Stores bank transfer, cheque, cash proof, finance approver, approval time, and notes.                                | Access to course content follows approved payment status.                                           |
| Webhook log            | Stores raw provider event ID, signature status, processing status, retry count, and linked transaction.              | Webhook handling must be idempotent and signed where provider supports signatures.                  |

# 11\. PowerX Data and Access Model

| **Data group**      | **Core records**                                                             | **Access rule**                                                                              |
| ------------------- | ---------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------- |
| CRM                 | Lead, source, campaign, follow-up, assigned owner, outcome.                  | Sales/management can manage; instructors/students do not see lead pipeline unless permitted. |
| Learning            | Course, package, module, lesson, media, enrollment, progress.                | Students see enrolled/eligible materials only; staff access depends on role.                 |
| Training operations | Batch, session, attendance, practical assessment, instructor comment.        | Instructors manage assigned batches; operations/admin manage all.                            |
| Assessment          | Question, exam, attempt, score, answer history, pass/fail result.            | Students see own attempts; staff access follows course/batch responsibility.                 |
| Finance             | Invoice, payment transaction, manual payment proof, refund/adjustment.       | Finance/admin manage; students/corporate users see own receipts/invoices.                    |
| Certificates        | Certificate number, template, issue/expiry, verification token, PDF, status. | Students see own certificates; public verification exposes limited validity data only.       |
| Audit               | Payment approval, certificate issuance, exam edits, role changes, exports.   | Visible only to authorized admin/management roles.                                           |
| Corporate portal    | Company profile, quotations, invoices, payments, employee enrollments, attendance summary, practical outcome, certificate links. | Coordinators see only matched company-scoped Level 2 operational fields; student contacts, documents, payment proofs, exam answers, audit metadata, and other-company records stay hidden. |

# 12\. PowerX Integrations

| **Integration** | **MVP approach**                                                                              | **Technical notes**                                                                                       |
| --------------- | --------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------- |
| Email           | Registration confirmations, receipts, course access, class reminders, certificates, renewals. | Use queued mail, database queue workers, and verified PowerX sender domain.                               |
| WhatsApp        | Manual/click-to-chat follow-up for now; Business API later.                                   | Keep message templates configurable and log outbound communication attempts.                              |
| Payment gateway | Deferred. Manual cash, bank transfer, and cheque are approved for MVP.                        | Keep adapter architecture inert until a future online provider is approved.                               |
| Video/storage   | Private S3-compatible object storage for MVP; secure streaming provider later if needed.      | Use signed application routes or provider access controls to reduce unauthorized sharing.                 |
| Analytics       | GA/UTM and Meta Pixel support on public pages via environment-configured IDs.                 | Avoid blocking registration if analytics script fails.                                                    |
| AI assistant    | Optional FAQ/course recommendation and lead capture assistant.                                | Must answer only from approved course/FAQ data and create CRM lead handoff when needed.                   |

# 13\. PowerX Reporting and Document Generation

- Generate certificates as PDF files with stored certificate number, issue date, expiry/renewal date if configured, approver, and verification token.
- Generate receipts, invoices, quotations, corporate completion reports, and management summaries as PDF where formal sharing is needed.
- Export lead, payment, enrollment, attendance, exam, certificate, and corporate account reports to XLSX/CSV.
- Use queued report generation for heavy reports and certificate batches so user-facing requests stay responsive.

# 14\. PowerX Delivery Phases

| **Phase** | **Technical deliverables**                                                                                                                           | **Exit criteria**                                                                                    |
| --------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------- |
| Phase 1   | Starter app, auth/passkeys, roles, course catalog, landing pages, CRM, registration, course setup, batches, manual payment proofs, basic dashboards. | PowerX can manage leads, registrations, manual payments, and class operations in one system.         |
| Phase 2   | LMS content, private media, question bank, mock exams, progress, attendance, practical assessment, certificates, public verification, reports.       | Paid students can access course materials and eligible students can receive verifiable certificates. |
| Phase 3   | Online payment adapter, WhatsApp API, AI assistant, advanced analytics, expanded corporate self-service, renewal and referral automation.             | Sales, student, and corporate operations become more automated and measurable.                       |

# 15\. PowerX Assumptions and Risks

| **Area**           | **Decision / assumption**                                              | **Risk control**                                                                                                 |
| ------------------ | ---------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------- |
| Payment gateway    | Online payment is required, but provider is not fixed.                 | Build PaymentGateway interface first and implement the selected adapter after client/provider confirmation.      |
| Tenancy            | Team support remains enabled, but v1 is a single PowerX organization.  | Use team scoping where useful without forcing unnecessary tenant UX on students.                                 |
| Course media       | PowerX supplies videos, PDFs, question banks, and certificate wording. | System stores/delivers content; content production is outside technical scope.                                   |
| Certificate claims | Certificate wording must be legally approved.                          | Keep certificate templates configurable and separate PowerX-issued from third-party/government claims.           |
| Activitylog        | Use v4 per approved stack.                                             | Confirm Laravel 13 compatibility during composer install; document any need to move to v5 before implementation. |

# 15\. Technical Reference Anchors

| **Source**                     | **Used for**                                                                   | **URL**                                                             |
| ------------------------------ | ------------------------------------------------------------------------------ | ------------------------------------------------------------------- |
| Laravel starter kits and teams | Vue/Inertia starter kit and team support.                                      | <https://laravel.com/docs/13.x/starter-kits>                        |
| Laravel Fortify and passkeys   | Fortify auth backend, passkey routes, and authentication features.             | <https://laravel.com/docs/13.x/fortify#passkeys>                    |
| FilamentPHP 5.x                | Laravel admin panels, forms, tables, schemas, and dashboards.                  | <https://filamentphp.com/docs/5.x>                                  |
| Spatie Permission v8           | Role and permission package for Laravel.                                       | <https://spatie.be/docs/laravel-permission/v8/introduction>         |
| Spatie Media Library v11       | Eloquent-linked file/media management.                                         | <https://spatie.be/docs/laravel-medialibrary/v11/introduction>      |
| Laravel Reverb                 | Laravel WebSocket server for broadcasting when realtime is needed.             | <https://laravel.com/docs/reverb>                                   |
| Pest v4                        | PHP testing framework with Laravel and browser testing support.                | <https://pestphp.com/docs/pest-v4-is-here-now-with-browser-testing> |
| Laravel Pint                   | Laravel code style fixer.                                                      | <https://laravel.com/docs/13.x/pint>                                |
| Inertia.js                     | Modern monolith bridge between Laravel and Vue.                                | <https://inertiajs.com/docs>                                        |
| shadcn-vue                     | Vue component registry for Tailwind-based UI components.                       | <https://www.shadcn-vue.com/docs/>                                  |
| Spatie Laravel PDF             | PDF generation package with multiple drivers.                                  | <https://spatie.be/docs/laravel-pdf/v2/requirements>                |
| Laravel Cashier Stripe         | Optional Stripe integration reference for PowerX payment gateway adapter.      | <https://laravel.com/docs/13.x/billing>                             |

# 17\. Sign-Off

| **Role**               | **Name** | **Signature** | **Date** |
| ---------------------- | -------- | ------------- | -------- |
| Client sponsor         |          |               |          |
| Technical lead         |          |               |          |
| Implementation partner |          |               |          |