# PowerX Pending Implementation Plan

Prepared: June 24, 2026

This plan covers the BRS requirements that are not fully implemented in the current PowerX application. It is based on a review of `docs/powerx-brs.md`, the current routes, application schema, controllers, actions, Filament resources, Vue pages, and focused feature tests.

## Current Implementation Baseline

The application already includes the core training-center foundation:

- Public course catalog, lead inquiry, individual registration, corporate quotation request, and public certificate verification routes.
- Filament admin resources for leads, communications, companies, student profiles, enrollments, courses, packages, modules, lessons, lesson progress, batches, sessions, attendance records, invoices, payment transactions, questions, exams, attempts, certificates, and audit events.
- Role and permission gates for management, admin, sales, finance, instructor, student, corporate, and support users.
- Manual payment recording and approval for bank transfer, cheque, and cash.
- Student portal views for enrolled courses, schedules, signed lesson media downloads, manual progress, exams readiness, payments, and certificates.
- Instructor portal, corporate read-only portal, operational dashboard, CSV/PDF reports, PDF invoices, receipts, and certificates.
- Exam domain actions for starting and submitting attempts, but no complete student-facing exam-taking route/UI flow.
- Email delivery automation, WhatsApp-ready communication templates, configurable WhatsApp/SMS provider adapters, delivery webhooks, lifecycle reminder scheduling, opt-out enforcement, and click-to-chat fallback while external credentials remain unsigned.
- A payment gateway seam with a null provider, but no signed-off online payment provider.

## Implementation Principles

- Keep existing Laravel action classes as the primary home for business rules.
- Use Filament actions for staff workflows that need auditability.
- Use Inertia/Vue routes for student, corporate, and public workflows.
- Add or update feature tests for every workflow change.
- Keep online payment, WhatsApp, SMS, AI, Arabic, and government integrations behind explicit provider/client sign-off.
- Avoid hard-coded PowerX legal, certificate, pricing, tax, or accreditation wording.

## Phase 1: Admissions, CRM, And Free Preview Completion

### Status

Completed and verified on June 24, 2026.

### Goal

Close the remaining sales and admissions gaps around lead sources, follow-up history, enrollment approval, rejection, request-more-information workflows, and free preview usage tracking.

### BRS Coverage

- BO-01, BO-02
- BR-SM-01 through BR-SM-05
- BR-REG-01 through BR-REG-04
- BR-LMS-02
- Key business rules for lead tracking, free previews, and auditable enrollment decisions

### Checklist

- [x] Add a lead activity/follow-up history model or structured timeline on `leads.metadata` if a separate table is not warranted.
- [x] Normalize lead statuses to cover New, Contacted, Qualified, Quotation Sent, Payment Pending, Enrolled, Won, Lost, and Not Responsive.
- [x] Add Filament lead actions for contact logged, qualify, send quotation, mark not responsive, mark lost, and convert to enrollment.
- [x] Preserve UTM/source/campaign fields from public lead, registration, and course pages.
- [x] Track free preview starts/completions after lead capture, including course, lesson, source, campaign, and user/lead when known.
- [x] Add enrollment approval actions for approve, reject, and request more information instead of relying only on direct status edits.
- [x] Record audited before/after snapshots for enrollment approval, rejection, and request-more-information actions.
- [x] Create communication drafts for request-more-information, approval, rejection, and registration confirmation outcomes.
- [x] Add dashboard metrics for follow-ups due today, overdue follow-ups, approval queue, and request-more-information queue.
- [x] Update public and student UX copy only where needed to show approval state clearly.

### Deliverables

- Lead follow-up history/timeline.
- Updated lead status handling and Filament lead actions.
- Enrollment approval/rejection/request-info actions.
- Free preview tracking tied to leads, courses, and lessons.
- CRM/admissions dashboard metrics.
- Feature tests for lead pipeline transitions, free preview tracking, enrollment approval, rejection, request-info, and related audit events.

### Acceptance Criteria

- Staff can see every lead's source, campaign, owner, status, follow-up date, follow-up history, and conversion result.
- A prospect who watches preview material can be tied back to a lead or captured source/campaign where available.
- Admissions can approve, reject, or request more information from an enrollment without directly editing protected approval fields.
- Each admissions decision creates an audit event with actor, timestamp, before/after state, and linked enrollment.
- Students see an accurate admission state after registration.
- Focused feature tests pass for CRM and admissions workflows.

## Phase 2: Student Lesson Viewer, Exam-Taking, And Assessment Hardening

### Status

Completed and verified on June 24, 2026, with student lesson viewer, exam-taking flow, configurable certificate rules, scoped Larastan level 7 analysis, and 100% test coverage.

### Goal

Turn the current signed lesson media download flow into a full student lesson-viewing experience, then complete the exam-taking workflow and harden certificate eligibility for practical courses.

### BRS Coverage

- BO-04, BO-05, BO-06
- BR-LMS-02
- BR-LMS-03
- BR-EX-01 through BR-EX-04
- BR-CLS-03
- BR-CERT-01
- Key business rules for paid LMS access, preview lessons, exam access, and certificate eligibility

### Checklist

- [x] Add a dedicated lesson viewer route/page instead of rendering all lesson content inline in the course overview.
- [x] Render uploaded lesson videos in an authenticated player for paid students, with provider/storage behavior signed off before production.
- [x] Render uploaded PDFs in an authenticated in-browser PDF viewer for paid students, while preserving secure download behavior where allowed.
- [x] Decide whether preview lessons may expose video/PDF media before payment; if approved, add source-aware preview media access rules and tracking.
- [x] Replace manual-only lesson progress buttons with media-aware progress updates for video watch progress, PDF open/download events, and completion.
- [x] Add tests proving video/PDF links cannot be accessed outside paid or approved preview access, and that expired signed links fail.
- [x] Add authenticated student routes for exam start, active attempt view, answer autosave if needed, and final submission.
- [x] Build an Inertia/Vue exam-taking page with timer, question navigation, answer selection, confirmation, and locked submitted state.
- [x] Use existing `StartExamAttempt` and `SubmitExamAttempt` actions as the business-rule boundary.
- [x] Implement question selection from `question_count`, active questions, randomization settings, and course/topic filters.
- [x] Store the selected question set on the attempt so a randomized exam remains stable during the attempt.
- [x] Ensure time limit, access window, payment status, enrollment status, max attempts, and course ownership are rechecked server-side.
- [x] Add clear result views for pass/fail, score, duration, attempts used, and next allowed action.
- [x] Add instructor/admin review views for answer history where staff permissions allow it.
- [x] Add practical-course certificate rules so courses/packages can require a passed practical assessment, not only block failed practical outcomes.
- [x] Add a course/package setting for whether attendance, exam pass, lesson completion, and practical pass are required for certificate eligibility.
- [x] Add audit logging for exam configuration changes that affect attempts or certificate eligibility.

### Deliverables

- Student exam-taking routes, controllers, requests, and Vue pages.
- Student lesson viewer route and Vue page with embedded video and PDF viewing.
- Media-aware lesson progress events for video, PDF, download, and completion.
- Stable randomized question-set storage per attempt.
- Server-side exam enforcement for timing, attempts, paid access, and course ownership.
- Exam result and review screens.
- Configurable certificate eligibility rules for practical and non-practical courses.
- Tests for start, resume, submit, expired attempt, max attempts, randomized question stability, unauthorized access, and certificate eligibility.

### Acceptance Criteria

- A paid active student can open a lesson viewer from the student portal and view approved video/PDF lesson media without exposing private storage paths.
- Unpaid users can access only approved preview content and cannot access paid video/PDF lesson media.
- Lesson progress can be updated from media activity, not only from manual buttons.
- A paid active student can start an assigned mock exam from the student portal.
- The attempt shows only the selected active questions for that exam/course.
- The student can submit answers and receive score, pass/fail result, duration, and attempts-used status.
- A student cannot start or submit an exam without active paid access, within the valid access window, and remaining attempts.
- Randomized exams keep the same selected question set for the life of the attempt.
- Practical courses can require a passed practical assessment before certificate issuance.
- Certificate issuance tests prove payment, lessons, exam, attendance, and practical rules are enforced according to course/package configuration.

## Phase 3: Offline Payment And Finance Completion

### Status

Code implemented and verified on June 24, 2026, with offline proof intake, portal payment views, finance review actions, receipt/invoice references, `composer ci:check`, and 100% test coverage. PowerX business sign-off is still required for accepted methods, refund/adjustment policy, and legal receipt wording before production rollout.

### Goal

Complete finance-grade offline payment workflows for bank transfer, cheque, cash, and approved manual adjustments before any online gateway dependency is introduced.

### BRS Coverage

- BO-03
- BR-PAY-02 through BR-PAY-05
- Manual payment confirmation
- Payment data requirements and finance reporting requirements

### Checklist

- [ ] Confirm accepted offline methods, currencies, proof requirements, approval roles, rejection rules, adjustment rules, refund policy, and legal receipt wording with PowerX.
- [x] Add or harden student and corporate offline payment proof upload flows for invoices and enrollments.
- [x] Store offline payment method, payer, amount, reference number, proof file, received date, bank/deposit details where applicable, finance reviewer, review timestamp, and review notes.
- [x] Add finance review actions for approve, reject, request more information, mark duplicate, mark partial, void, refund/adjust, and attach internal notes.
- [x] Ensure paid access is unlocked only after approved offline payment and revoked or preserved correctly for rejected, voided, refunded, partial, or adjusted payments.
- [x] Add finance filters/reports for pending proof, approved, rejected, duplicate, partial, overdue, refunded, voided, adjusted, and unmatched payments.
- [x] Update student payments page to show invoice status, offline payment instructions, proof upload status, rejection/request-more-information notes, receipt links, and outstanding balance.
- [x] Update corporate portal to show company invoice status, offline payment instructions, proof upload status, receipt links, and outstanding balance without exposing internal finance notes.
- [x] Ensure invoice and receipt PDFs include payment method, amount, date, reference, payer, student/company, course/package, approver, and offline proof/audit reference.
- [x] Keep the online `PaymentGateway` seam disabled/null-provider-only until PowerX signs off a provider.

### Deliverables

- Offline payment proof upload workflow for students and corporate coordinators.
- Finance review queue and audited approval/rejection/adjustment actions.
- Offline payment reconciliation workflow and finance-facing status views.
- Student and corporate payment status views with receipt and outstanding-balance visibility.
- Updated invoice and receipt PDFs for offline payment references and approval details.
- Tests for proof upload, finance approval, rejection, request-more-information, duplicate detection, partial payment, adjustment/refund/void behavior, paid enrollment activation, access revocation rules, and receipt/invoice output.

### Acceptance Criteria

- Eligible students and corporate coordinators can submit offline payment proof for invoices without accessing private storage paths.
- Finance can approve, reject, request more information, mark duplicate, mark partial, void, refund, or adjust offline payments with audit history.
- Approved offline payment updates payment, invoice, enrollment access, receipt, and audit records exactly once.
- Rejected, duplicate, pending, partial, voided, or refunded offline payments do not incorrectly unlock paid lessons, exams, certificates, or access windows.
- Receipts and invoices show payment method, amount, date, reference, payer, course/company/student, approver, and offline proof/audit reference as applicable.
- Finance reports distinguish pending, approved, rejected, partial, overdue, refunded, voided, adjusted, and unmatched offline payments.
- Existing online gateway seam remains disabled until explicit provider sign-off.

## Phase 4: Communications Automation For WhatsApp, SMS, And Lifecycle Reminders

### Status

Code implemented and verified on June 24, 2026, with provider contracts, configurable HTTP provider adapters, delivery webhooks, opt-out enforcement, retry/dead-letter states, lifecycle reminder scheduling, disabled-provider fallback, and focused Phase 4 tests. PowerX sign-off is still required for WhatsApp Business API provider, sender identity, approved template categories, SMS MVP scope, and production credentials before external delivery is enabled.

### Goal

Move from WhatsApp-ready drafts and queued email to provider-backed messaging with reliable delivery tracking, opt-out handling, and operational reminders.

### BRS Coverage

- BR-SM-04
- BR-CERT-05
- BR-ADM-05
- WhatsApp, email, and optional SMS integrations
- Communication data requirements

### Checklist

- [ ] Confirm WhatsApp Business API provider, sender identity, template approval rules, and message categories.
- [ ] Confirm whether SMS is required for MVP or deferred.
- [x] Add provider contracts for outbound message delivery and delivery status callbacks.
- [x] Implement WhatsApp provider adapter behind feature flags.
- [x] Implement SMS provider adapter behind feature flags, with SMS disabled until PowerX approves the channel.
- [x] Add webhook endpoints for provider delivery events where supported.
- [x] Extend communication lifecycle states for provider accepted, delivered, read if available, failed, retried, and opted out.
- [x] Add opt-out enforcement for WhatsApp/SMS and clear staff visibility in Filament.
- [x] Convert renewal reminders, class reminders, payment reminders, registration confirmations, certificate-ready messages, and follow-up messages from drafts into scheduled sends where approved.
- [x] Keep click-to-chat URLs as fallback when provider delivery is disabled.
- [x] Add failed-message retry and dead-letter review workflow.

### Deliverables

- WhatsApp provider adapter behind feature flags, pending approved Business API credentials.
- Optional SMS provider adapter behind feature flags, disabled until PowerX confirms SMS scope.
- Delivery callback route and communication status updates.
- Communication scheduling, opt-out, retry, fallback, and dead-letter workflow.
- Filament visibility for failures, opt-outs, provider status, provider references, and last webhook state.
- Tests for provider send, disabled-provider fallback, opt-out, retries, failure handling, delivery callback validation, and scheduled reminder dispatch.

### Acceptance Criteria

- Approved templates can be sent through a configured WhatsApp provider endpoint when PowerX enables the channel and supplies production credentials.
- Email delivery continues through the existing queue.
- SMS sends only when PowerX explicitly enables the SMS provider configuration.
- Opted-out recipients are not sent WhatsApp/SMS messages.
- Delivery failures are visible to staff and can be retried or moved to dead-letter review.
- Renewal, payment, class, registration, certificate-ready, and lead follow-up reminders are scheduled and traceable to their source records.

## Phase 5: Growth Automation, AI Assistant, And Advanced Attribution

### Status

Pending.

### Goal

Implement the BRS growth features that help PowerX convert leads faster: AI-assisted course guidance, approved FAQ answers, lead handoff, referral tracking, renewal campaigns, and stronger campaign attribution.

### BRS Coverage

- BO-08, BO-09
- BR-SM-01, BR-SM-05
- BR-CERT-05
- AI assistant integration
- Analytics integration
- Renewal and growth business process

### Checklist

- [ ] Confirm AI assistant scope: FAQ only, course recommendation, registration help, or all three.
- [ ] Build an approved knowledge source from public course data, package data, schedule availability, pricing, FAQ content, and PowerX-approved disclaimers.
- [ ] Add a public AI assistant entry point on course/landing pages behind a feature flag (use laravel/ai sdk package and laravel/pennant both already installed).
- [ ] Create CRM leads from AI conversations when contact details or buying intent are captured.
- [ ] Add escalation handoff to sales/support with transcript summary and preferred course.
- [ ] Add guardrails so the assistant does not make unapproved certificate, government, price, legal, refund, or accreditation claims.
- [ ] Add referral source and referral contact tracking for leads/enrollments.
- [ ] Convert renewal opportunities into campaign workflows with scheduled reminders and recommended next courses.
- [ ] Improve campaign attribution with UTM capture, paid-ad cost inputs, channel grouping, and revenue matching.
- [ ] Add dashboards for source performance, conversion rate, cost, revenue, ROI, renewal opportunities, and referral conversion.

### Deliverables

- Feature-flagged AI assistant workflow.
- Approved FAQ/course knowledge source.
- AI lead handoff into CRM.
- Referral tracking fields/workflows.
- Renewal campaign workflow.
- Enhanced campaign attribution and dashboard/report updates.
- Tests for AI lead creation, escalation, guardrails, UTM capture, referral tracking, renewal campaign creation, and campaign ROI calculations.

### Acceptance Criteria

- The assistant answers only from approved PowerX course/FAQ data.
- The assistant creates or updates CRM leads when a prospect provides contact details or asks for registration help.
- Staff can see AI handoff summaries, source, course interest, and next action.
- Unapproved certificate/government/legal/pricing claims are blocked or answered with approved fallback copy.
- Management can compare channels by lead count, conversion, cost, revenue, and ROI.
- Renewal and referral campaigns are visible, scheduled, and reportable.

## Phase 6: Corporate Self-Service And Export Completion

### Status

Partially implemented foundation; pending self-service actions and export hardening.

### Goal

Expand the current read-only corporate portal into controlled corporate self-service, and complete Excel/XLSX export support promised in the BRS and technical specification.

### BRS Coverage

- BO-07, BO-08
- BR-REG-03, BR-REG-05
- BR-ADM-04
- Corporate account report
- Course enrollment, attendance, exam, certificate, and finance reports

### Checklist

- [ ] Confirm allowed corporate self-service actions with PowerX and define data-sharing levels.
- [ ] Add corporate coordinator actions to request additional seats, submit employee details, request schedule changes, and download approved reports.
- [ ] Add staff approval workflow for corporate-submitted employee lists before enrollment activation.
- [ ] Add corporate invoice payment actions after the online payment provider is available.
- [ ] Add corporate coordinator view of completion reports, attendance summaries, certificate verification links, and invoice/payment status.
- [ ] Keep private student contact details, documents, payment proofs, exam answers, audit metadata, and other-company records hidden.
- [ ] Add XLSX exports for operational report sections and corporate coordinator reports.
- [ ] Consider queued export generation for large reports.
- [ ] Add export audit events for CSV, PDF, and XLSX.
- [ ] Add permission tests for management, finance, sales, corporate, instructor, and student report boundaries.

### Deliverables

- Corporate self-service request forms.
- Corporate employee list submission and staff approval workflow.
- Corporate report download flow.
- XLSX export implementation.
- Queued export support if report size justifies it.
- Tests for corporate data isolation, self-service submissions, approval, XLSX contents, and export audit logging.

### Acceptance Criteria

- A corporate coordinator can request quotation updates, additional seats, and employee enrollments without seeing other-company data.
- Staff can approve or reject corporate submissions before enrollments become active.
- Corporate users can download only approved company-scoped reports.
- Management can export operational reports as CSV, PDF, and XLSX.
- Every export records actor, team, format, route, report sections, and timestamp.
- Permission tests prove student, instructor, finance, sales, management, and corporate boundaries remain intact.

## Phase 7: Launch Hardening, Localization, And Deferred Compliance Integrations

### Status

Pending.

### Goal

Prepare the application for production readiness and isolate post-MVP items that require client, legal, provider, or external-system approval.

### BRS Coverage

- NFR-01 through NFR-08
- Government/accreditation systems integration
- Brand/legal name, certification claims, payments, languages, content readiness, targets, and deployment open questions

### Checklist

- [ ] Confirm production domain, HTTPS, mail sender domain, passkey origin, queue worker, scheduler, storage disk, PDF runtime, and environment owner.
- [ ] Define and test database plus private media backup and restore procedures.
- [ ] Add a restore-test checklist covering login, catalog, registration, payment record, media download, exam attempt, certificate PDF, and public verification.
- [ ] Add production monitoring expectations for logs, failed jobs, uptime, errors, and queue health.
- [ ] Confirm legal/brand naming and approved certificate wording before launch.
- [ ] Confirm final course catalog, pricing, package names, currency, tax/VAT treatment, validity, certificate expiry, pass marks, and max attempts.
- [ ] Confirm whether Arabic/RTL is post-MVP or required before launch.
- [ ] If Arabic is approved, add translation files, RTL layout review, bilingual public pages, bilingual notifications, and PDF language handling.
- [ ] Keep Kahramaa/QCDD/government integrations out of MVP unless PowerX provides API access and legal approval.
- [ ] If formal online exam controls are required, define proctoring/invigilation separately from the basic mock-exam workflow.
- [ ] Add final go/no-go checklist and rollback plan.

### Deliverables

- Production readiness checklist with named owners.
- Backup and restore runbook.
- Restore-test evidence checklist.
- Launch configuration test coverage for production-sensitive settings.
- Approved legal/certificate wording and catalog/pricing configuration records.
- Arabic/RTL implementation plan or explicit deferral note.
- Separate discovery notes for government API or proctoring if approved.

### Acceptance Criteria

- Production cannot be marked ready without confirmed HTTPS, mail sender, queue worker, scheduler, storage, backup owner, restore test, support owner, and rollback plan.
- A restore test has been completed in a non-production environment and documented.
- Certificate wording and public claims have client/legal sign-off.
- Course/package/pricing/currency/tax/certificate rules are configured, not hard-coded.
- Arabic support is either implemented and reviewed or explicitly deferred with PowerX approval.
- Government/accreditation and proctoring work remains blocked until external approval and API/process details exist.

## Suggested Phase Order

1. Phase 1: Admissions, CRM, And Free Preview Completion
2. Phase 2: Student Exam-Taking And Assessment Hardening
3. Phase 3: Offline Payment And Finance Completion
4. Phase 4: Communications Automation For WhatsApp, SMS, And Lifecycle Reminders
5. Phase 6: Corporate Self-Service And Export Completion
6. Phase 5: Growth Automation, AI Assistant, And Advanced Attribution
7. Phase 7: Launch Hardening, Localization, And Deferred Compliance Integrations

Phase 7 should begin as a parallel readiness track once provider, domain, content, legal, and deployment decisions become available. It should not wait until all product phases are complete.

## Global Verification Gate

Each phase should finish with:

- Focused feature tests for new workflows and denial paths.
- Permission tests for every role touched by the phase.
- Audit-event assertions for sensitive actions.
- `vendor/bin/pint --dirty --format agent` if PHP files changed.
- Minimum relevant `php artisan test --compact ...` test run for the changed area.
- Frontend build or browser verification when Inertia/Vue pages changed.
