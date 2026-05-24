Business Requirements Specification

PowerX Training Center Web Application

| **Client**        | PowerX Training Center / PowerX Electric Inc.                                                                                 |
| ----------------- | ----------------------------------------------------------------------------------------------------------------------------- |
| **Purpose**       | Draft BRS for turning the current website into a full training-center web application.                                        |
| **Version**       | 0.1 - Draft for client validation                                                                                             |
| **Date**          | May 23, 2026                                                                                                                  |
| **Prepared by**   | Aminu Hussain                                                                                                                 |
| **Source inputs** | Live website content from powerxelect.com, supplied website screenshot, flyer, notebook notes, and whiteboard planning notes. |

**Important validation note**

This document converts the available discovery notes into business requirements. Pricing, contact numbers, legal company name, certificate wording, payment provider, and exact accreditation claims must be confirmed with PowerX before development sign-off.

# 1\. Executive Summary

PowerX currently has a public website and marketing material for electrical training services in Qatar. The business wants to move from a mostly informational website to a full training-center software platform that manages the complete lifecycle from marketing lead to paid enrollment, course delivery, assessments, certificate issuance, renewals, and management reporting.

The web application should support both online and in-person training. It should help PowerX sell Kahramaa exam preparation, practical electrical training, corporate programs, and related certification-oriented courses while keeping operations, finance, marketing, and training delivery in one controlled system.

**Business intent**

Build one operational platform that converts traffic into qualified leads, converts leads into paid students or corporate customers, delivers learning content and exams, and produces auditable certificates and business reports.

# 2\. Business Context

## 2.1 Current Positioning

The live website positions PowerX Electric Inc. as Qatar's leading electrical training provider, focused on Kahramaa exam preparation, hands-on training, trusted professional instruction, and a 99% exam success message. Supplied flyer material uses PowerX Training Center branding and lists a broader training catalog for supervisors, technicians, engineers, and corporate/industrial customers.

## 2.2 Current Limitations

- The website mainly presents information and contact forms; it does not yet operate as an end-to-end training management system.
- Lead capture, follow-up, quotations, registrations, payment confirmation, batch scheduling, attendance, exam results, and certificates appear to depend on manual processes.
- Course pricing and package information is inconsistent across inputs, with website USD pricing, whiteboard QAR pricing, and notebook references to a \$95 instant-access offer.
- The business needs measurable control of sales, marketing, learning delivery, and certificate records to support weekly revenue and growth goals.

# 3\. Objectives

| **ID**    | **Priority** | **Business requirement**                                                                                                                                             |
| --------- | ------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **BO-01** | Must         | Increase inquiry-to-enrollment conversion by giving every marketing lead a tracked source, status, owner, follow-up history, and next action.                        |
| **BO-02** | Must         | Allow students and companies to register for courses online, submit required details, and receive confirmation without relying only on WhatsApp/manual coordination. |
| **BO-03** | Must         | Support online payments and manual payment confirmation for cash, cheque, and bank transfer workflows used by the business.                                          |
| **BO-04** | Must         | Deliver free and paid learning content, including introductory videos, course videos, PDFs, practical training material, and pass-question material.                 |
| **BO-05** | Must         | Manage exams, mock tests, pass-question practice, progress tracking, and course completion rules.                                                                    |
| **BO-06** | Must         | Issue, store, and verify PowerX certificates after the student satisfies completion and assessment rules.                                                            |
| **BO-07** | Should       | Support corporate and contractor clients through quotation, bulk enrollment, company contacts, and training batch management.                                        |
| **BO-08** | Should       | Provide management dashboards for leads, sales, revenue, payment collection, enrollment, attendance, pass rates, certificate issuance, and renewal opportunities.    |
| **BO-09** | Could        | Add AI chat support to help prospects choose courses, answer basic questions, and guide registration while escalating qualified leads to staff.                      |

# 4\. Scope

## 4.1 In Scope

- Public course catalog and landing pages for campaigns.
- Lead capture, CRM pipeline, source tracking, and marketing follow-up.
- Student and company registration workflows.
- Course, batch, instructor, classroom, and practical-session management.
- Learning content delivery for videos, PDFs, quizzes, and exam preparation material.
- Question bank, mock exams, attempts, scoring, and progress history.
- Online payment, manual payment recording, receipts, invoices, and payment approval.
- Certificate issuance, certificate verification, certificate renewal tracking, and certificate templates.
- Notifications through email, WhatsApp-ready workflows, and optional SMS.
- Role-based admin panels for sales, finance, instructors, operations, and management.
- Dashboards and exportable reports.

## 4.2 Out of Scope for MVP

- Native iOS/Android apps, unless added after the web application stabilizes.
- Creation of the actual course videos, PDFs, and question content; the system will store and deliver content supplied by PowerX.
- Guaranteed integration with Kahramaa, QCDD, or government systems unless PowerX confirms available APIs and legal permissions.
- Advanced accounting, payroll, HR, and inventory systems beyond training-center operations.
- Public claims that PowerX issues government certificates unless the claim is legally validated and reflected in approved certificate wording.

# 5\. Stakeholders and User Groups

| **User group**          | **Primary needs**                                                                                                         |
| ----------------------- | ------------------------------------------------------------------------------------------------------------------------- |
| Prospect / Lead         | Find a suitable course, watch free material, ask questions, submit inquiry, register, and pay.                            |
| Student / Candidate     | Access enrolled courses, view schedules, attend classes, take exams, track progress, download certificates.               |
| Corporate coordinator   | Request quotations, enroll multiple staff, coordinate payment, receive attendance and completion reports.                 |
| Sales / Marketing staff | Track campaign leads, follow up, qualify prospects, send offers, convert students, measure channel performance.           |
| Finance staff           | Verify payments, issue receipts/invoices, reconcile manual and online payments, view outstanding balances.                |
| Instructor / Trainer    | View assigned batches, upload or manage materials, mark attendance, record practical assessment, review exam performance. |
| Operations admin        | Manage courses, batches, schedules, rooms, trainers, enrollments, and certificate rules.                                  |
| Management              | Monitor revenue, lead volume, sales conversion, course performance, pass rates, certificate volume, and weekly targets.   |
| Support / AI assistant  | Answer common questions, route qualified leads, help users complete registration, and escalate issues.                    |

# 6\. Target Business Process

The discovery notes describe a funnel that starts with paid/organic traffic and ends with a trained, assessed, and certificated candidate. The software should formalize this workflow while allowing staff to intervene at each stage.

- Traffic generation: campaigns run through Facebook, Instagram, WhatsApp, email marketing, LinkedIn, referral partners, YouTube, and engineering groups.
- Lead capture: the prospect reaches a landing page, course page, WhatsApp link, or AI chat, then submits contact and course interest.
- Free learning preview: the prospect can watch selected free Kahramaa or course-introduction videos to build trust.
- Registration or quotation: individuals register directly; companies can request a quotation and bulk enrollment approval.
- Payment: the system records online payment or manual payment by bank transfer, cheque, or cash. Access is controlled by payment status.
- Course delivery: students receive instant online access where applicable and/or are assigned to scheduled classroom/practical batches.
- Assessment: students complete mock exams, pass-question practice, practical assessments, or final internal exams based on course rules.
- Certificate: eligible students receive a PowerX certificate with a unique number and verification link or QR code.
- Renewal and growth: the system tracks certificate renewals, repeat training, referrals, corporate follow-up, and future course recommendations.

# 7\. Course and Product Model

The platform must support multiple course/product types because PowerX sells both marketing-driven instant-access products and practical training programs. Pricing, currency, certificate wording, and package names should be admin-configurable.

| **Product type**      | **Business description**                                                                              | **System behavior**                                                                                                        |
| --------------------- | ----------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------- |
| Free lead magnet      | Free Kahramaa or course-introduction video used to attract and qualify prospects.                     | Requires lead capture before or after viewing; usage should be tracked for follow-up.                                      |
| Instant-access course | Paid online exam prep bundle referenced in notes as a \$95 offer.                                     | After payment confirmation, student gets immediate access to course videos, PDFs, pass-question material, and mock exams.  |
| Practical workshop    | In-person or blended practical training for technicians, supervisors, engineers, and corporate teams. | Requires batch scheduling, attendance, instructor assignment, capacity, practical evaluation, and certificate eligibility. |
| Corporate training    | Company-sponsored training for multiple staff or contractor groups.                                   | Requires quotation, company account, bulk enrollment, payment approval, attendance report, and completion report.          |
| Certificate renewal   | Follow-up workflow for certificates that expire or need renewal confirmation.                         | Requires certificate expiry tracking, renewal reminders, and repeat training options where applicable.                     |

## 7.1 Initial Course Catalog Inputs

| **Program area**           | **Courses or notes from supplied material**                                                                                              |
| -------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------- |
| Kahramaa exam preparation  | Electrical Kahramaa training for technicians and supervisors; Kahramaa exam preparation for engineers, technicians, and supervisors.     |
| Plumbing                   | Plumbing Kahramaa training for technicians and supervisors; whiteboard notes mention Plumbing & Drainage practical workshop.             |
| Cable jointing             | 11kV Cable Jointing Kit XLPE training; HV Cable Jointing & Termination; LV/MV Cable Jointing.                                            |
| Electrical safety          | Electrical Safety & Compliance; Electrical Safety Training Course for practical safety operation and maintenance; HV/LV Safety Training. |
| Power design               | Electrical Power Design for residential/commercial MV-LV-ELV systems; AutoCAD practical design/software training.                        |
| Substation and transformer | Substation & Switchgear Technical Training 33kV/11kV; Transformer Specialist Training HV/MV.                                             |
| QCDD-related training      | Fire Fighting Training with QCDD certification; Fire Alarm Training with QCDD certification.                                             |
| Other services             | Technical Manpower Supply and possible Chartered Engineer training listed in planning notes; validate exact commercial scope.            |

**Pricing normalization requirement**

The application must not hard-code prices. Website data shows USD course prices, while planning notes show QAR prices and a possible \$95 online offer. Admin users must be able to configure currency, price, discount, package, tax/VAT treatment if applicable, and validity period per course.

# 8\. Business Requirements

## 8.1 Sales, Marketing, and CRM

| **ID**       | **Priority** | **Business requirement**                                                                                                                                                     |
| ------------ | ------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **BR-SM-01** | Must         | Capture leads from website forms, course landing pages, WhatsApp links, AI chat, paid ads, email campaigns, referrals, LinkedIn, Instagram, YouTube, and manual staff entry. |
| **BR-SM-02** | Must         | Store lead source, campaign name, course interest, contact details, notes, assigned owner, status, follow-up date, and conversion outcome.                                   |
| **BR-SM-03** | Must         | Provide a lead pipeline with statuses such as New, Contacted, Qualified, Quotation Sent, Payment Pending, Enrolled, Won, Lost, and Not Responsive.                           |
| **BR-SM-04** | Should       | Allow staff to send or trigger follow-up messages through email and WhatsApp-ready templates.                                                                                |
| **BR-SM-05** | Should       | Track campaign performance including lead volume, cost input, conversion rate, enrollment count, revenue, and return on marketing spend.                                     |

## 8.2 Registration and Admissions

| **ID**        | **Priority** | **Business requirement**                                                                                                                                                     |
| ------------- | ------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **BR-REG-01** | Must         | Allow individual students to create an account and register for one or more courses.                                                                                         |
| **BR-REG-02** | Must         | Collect student profile data including name, mobile, email, profession, company, Qatar location, preferred course, preferred schedule, and required documents if applicable. |
| **BR-REG-03** | Must         | Support company/corporate registration with company profile, contact person, employee list, quotation request, and bulk course assignment.                                   |
| **BR-REG-04** | Must         | Allow admins to approve, reject, or request more information before confirming enrollment where business rules require approval.                                             |
| **BR-REG-05** | Should       | Generate quotations for corporate or offline sales cases and link quotations to payment and enrollment records.                                                              |

## 8.3 Payments, Receipts, and Finance

| **ID**        | **Priority** | **Business requirement**                                                                                                         |
| ------------- | ------------ | -------------------------------------------------------------------------------------------------------------------------------- |
| **BR-PAY-01** | Must         | Support online payment for eligible courses and packages, preferably in QAR unless PowerX confirms multi-currency checkout.      |
| **BR-PAY-02** | Must         | Support manual payment methods listed in discovery notes: bank transfer, cheque, and cash.                                       |
| **BR-PAY-03** | Must         | Control access to paid course material, exams, classes, and certificates based on payment status and admin approval.             |
| **BR-PAY-04** | Must         | Generate receipts and invoices with payment method, amount, date, reference number, student/company, course, and staff approver. |
| **BR-PAY-05** | Should       | Provide finance reports for paid, pending, overdue, refunded, partially paid, and manually adjusted payments.                    |

## 8.4 Learning Management and Course Delivery

| **ID**        | **Priority** | **Business requirement**                                                                                                                           |
| ------------- | ------------ | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| **BR-LMS-01** | Must         | Allow admins/instructors to create courses, modules, lessons, videos, downloadable PDFs, pass-question material, and practical-training resources. |
| **BR-LMS-02** | Must         | Support free preview content and paid content with access rules by package, payment status, enrollment status, and validity period.                |
| **BR-LMS-03** | Must         | Track student progress through lessons, videos, downloads, quizzes, exams, attendance, and practical completion.                                   |
| **BR-LMS-04** | Should       | Support blended delivery: online preparation material plus scheduled classroom or workshop sessions.                                               |
| **BR-LMS-05** | Should       | Allow staff to upload course updates and retire outdated material while preserving historical completion records.                                  |

## 8.5 Exams, Question Bank, and Assessments

| **ID**       | **Priority** | **Business requirement**                                                                                                             |
| ------------ | ------------ | ------------------------------------------------------------------------------------------------------------------------------------ |
| **BR-EX-01** | Must         | Maintain a question bank by course, topic, difficulty, answer, explanation, and active/inactive status.                              |
| **BR-EX-02** | Must         | Allow students to take mock exams or practice tests with configurable time limits, pass marks, attempts, and question randomization. |
| **BR-EX-03** | Must         | Record exam attempts, scores, pass/fail result, date/time, duration, and answer history.                                             |
| **BR-EX-04** | Should       | Provide exam analytics by student, batch, question, topic, course, and instructor to improve training outcomes.                      |
| **BR-EX-05** | Could        | Support controlled proctoring or invigilation workflow if PowerX later needs formal online exam controls.                            |

## 8.6 Class, Batch, and Practical Training Operations

| **ID**        | **Priority** | **Business requirement**                                                                                        |
| ------------- | ------------ | --------------------------------------------------------------------------------------------------------------- |
| **BR-CLS-01** | Must         | Create class batches with course, dates, time, venue, instructor, capacity, delivery mode, and enrollment list. |
| **BR-CLS-02** | Must         | Mark attendance for classroom, workshop, and practical sessions.                                                |
| **BR-CLS-03** | Must         | Record practical assessment outcomes and trainer comments for hands-on courses.                                 |
| **BR-CLS-04** | Should       | Manage rescheduling, student transfer between batches, cancellation, and make-up classes.                       |
| **BR-CLS-05** | Should       | Provide instructor views for assigned classes, student lists, attendance, assessment, and course resources.     |

## 8.7 Certificate Management

| **ID**         | **Priority** | **Business requirement**                                                                                                                            |
| -------------- | ------------ | --------------------------------------------------------------------------------------------------------------------------------------------------- |
| **BR-CERT-01** | Must         | Issue certificates only when payment, attendance, course completion, exam, and practical-assessment rules are satisfied.                            |
| **BR-CERT-02** | Must         | Generate unique certificate numbers and store certificate issue date, expiry/renewal date if applicable, student, course, result, and approver.     |
| **BR-CERT-03** | Must         | Provide a public certificate verification page or QR code that confirms certificate validity without exposing private student data.                 |
| **BR-CERT-04** | Must         | Separate PowerX-issued certificates from third-party/government certificates in all wording and templates unless legal approval confirms otherwise. |
| **BR-CERT-05** | Should       | Send renewal reminders and recommend next courses to graduates and corporate coordinators.                                                          |

## 8.8 Administration, Security, and Reporting

| **ID**        | **Priority** | **Business requirement**                                                                                                                             |
| ------------- | ------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| **BR-ADM-01** | Must         | Provide role-based access for management, admin, sales, finance, instructor, student, and corporate users.                                           |
| **BR-ADM-02** | Must         | Maintain audit logs for sensitive actions including payment approval, certificate issuance, exam edits, role changes, and data exports.              |
| **BR-ADM-03** | Must         | Provide dashboards for daily leads, conversion, weekly revenue, enrollments, payments pending, attendance, exam pass rate, and certificate issuance. |
| **BR-ADM-04** | Should       | Allow export of operational reports to Excel/CSV and printable PDF where needed.                                                                     |
| **BR-ADM-05** | Should       | Support configurable templates for email, WhatsApp message copy, receipts, quotations, certificates, and reminders.                                  |

# 9\. Reporting Requirements

| **Report**                      | **Purpose**                                                                                  | **Key fields**                                                                           |
| ------------------------------- | -------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| Lead source report              | Measure channel performance and support the business focus on lead generation.               | Source, campaign, lead count, qualified count, conversion rate, revenue.                 |
| Sales pipeline report           | Show inquiry-to-payment movement and sales team workload.                                    | Lead status, owner, course, follow-up date, quotation, payment status.                   |
| Weekly revenue report           | Track progress against planning note target of QAR 25,000 per week, subject to confirmation. | Paid amount, course, package, payment method, date, pending amount.                      |
| Course enrollment report        | Understand demand by course and batch.                                                       | Course, batch, enrolled students, seats available, cancellations.                        |
| Attendance and practical report | Verify class delivery and eligibility for certificates.                                      | Student, course, batch, session, attendance, practical result.                           |
| Exam performance report         | Monitor pass rates and weak topics.                                                          | Course, batch, attempt, score, topic, pass/fail, instructor.                             |
| Certificate report              | Maintain certificate records and renewal opportunities.                                      | Certificate number, student, course, issue date, expiry date, verification status.       |
| Corporate account report        | Manage company clients and repeat business.                                                  | Company, contact, employees trained, quotation value, payment status, completion status. |

# 10\. Non-Functional Requirements

| **ID**     | **Priority** | **Business requirement**                                                                                                                      |
| ---------- | ------------ | --------------------------------------------------------------------------------------------------------------------------------------------- |
| **NFR-01** | Must         | The web application must be responsive and usable on mobile because prospects will arrive from ads, WhatsApp, and social media links.         |
| **NFR-02** | Must         | The system must enforce authentication, role-based access, secure password handling, session protection, and least-privilege admin access.    |
| **NFR-03** | Must         | Student payment, profile, exam, and certificate data must be protected with encrypted transport and appropriate database access controls.     |
| **NFR-04** | Must         | The system must maintain reliable backups and restore procedures for student, payment, exam, and certificate records.                         |
| **NFR-05** | Should       | The application should load key public pages quickly for ad traffic and should support analytics/pixel tracking without slowing registration. |
| **NFR-06** | Should       | The system should support English first, with Arabic support planned or configurable if PowerX confirms bilingual requirements.               |
| **NFR-07** | Should       | The system should maintain clear audit trails for compliance, dispute handling, and internal control.                                         |
| **NFR-08** | Could        | The architecture should allow future native mobile app, government API, accounting integration, or advanced CRM integration if needed.        |

# 11\. Integrations

| **Integration**                  | **Need**                                                                      | **Notes**                                                                                           |
| -------------------------------- | ----------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------- |
| Payment gateway                  | Online course purchase and invoice payment.                                   | Provider to be selected; should support QAR, refunds/voids if needed, and payment status callbacks. |
| WhatsApp                         | Lead follow-up, registration help, payment reminders, class reminders.        | May start with click-to-chat and templates, then move to WhatsApp Business API.                     |
| Email                            | Receipts, registration confirmations, course access, reminders, certificates. | Use verified PowerX domain sender to protect deliverability.                                        |
| Analytics                        | Campaign measurement and conversion tracking.                                 | Google Analytics, Meta Pixel, and ad campaign UTM tracking should be supported.                     |
| Video hosting                    | Course videos and free preview content.                                       | Use secure hosting or private streaming to reduce unauthorized sharing.                             |
| AI assistant                     | Prospect support and registration guidance.                                   | Should answer from approved course/FAQ data and hand off qualified leads to staff.                  |
| Government/accreditation systems | Potential certificate or exam validation.                                     | Out of MVP unless PowerX confirms API access and legal approval.                                    |

# 12\. Key Business Rules

- A student may view free preview material before payment if the course/package allows it.
- Paid content, exams, batch attendance, and certificates must require an active enrollment and confirmed payment unless an admin grants approved exception.
- Corporate enrollments may be activated by quotation approval or manual payment confirmation according to finance rules.
- Certificates can only be issued after the student satisfies all required completion rules for the selected course.
- Certificate templates must avoid implying government-issued certification unless PowerX has documented approval for that wording.
- Course prices, discount rules, validity periods, certificate expiry, pass marks, and maximum attempts must be configurable per course/package.
- Every lead, payment approval, certificate issuance, and exam configuration change must have an auditable user and timestamp.

# 13\. Data Requirements

| **Data object** | **Minimum data needed**                                                                               |
| --------------- | ----------------------------------------------------------------------------------------------------- |
| Lead            | Name, phone, email, source, campaign, course interest, status, owner, notes, follow-up date, outcome. |
| Student         | Profile, contact details, profession, company, login, documents, enrollments, progress, certificates. |
| Company         | Company name, contact person, phone/email, employees, quotations, payments, training history.         |
| Course          | Name, category, description, price, currency, package type, content, exam rules, certificate rules.   |
| Batch           | Course, instructor, date/time, venue, capacity, students, attendance, status.                         |
| Payment         | Student/company, invoice, amount, currency, method, status, proof, gateway reference, approver.       |
| Question        | Course, topic, difficulty, question, answer options, correct answer, explanation, status.             |
| Exam attempt    | Student, course, exam, start/end time, score, answers, result, attempt number.                        |
| Certificate     | Certificate number, student, course, issue date, expiry date, status, verification URL/QR.            |
| Communication   | Recipient, channel, template, message, sent time, status, linked lead/student.                        |

# 14\. MVP Recommendation

The project should be delivered in phases so PowerX gets operational value early while reducing risk around course content, payments, exams, and certificates.

| **Phase**                                | **Recommended scope**                                                                                                                                            | **Business outcome**                                                                             |
| ---------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------ |
| Phase 1: Sales and operations foundation | Course catalog, lead capture, CRM pipeline, student/company registration, admin course setup, batch scheduling, manual payment recording, basic dashboards.      | PowerX can control inquiries, registrations, payments, and class operations in one system.       |
| Phase 2: LMS, exams, and certificates    | Content delivery, paid access rules, question bank, mock exams, progress tracking, attendance/practical assessment, certificate generation, public verification. | PowerX can sell online/blended training and automate completion-to-certificate workflow.         |
| Phase 3: Growth automation               | Online payment gateway, WhatsApp Business API, AI assistant, advanced marketing analytics, corporate client portal, renewal campaigns, referral tracking.        | PowerX can scale lead conversion, reduce manual follow-up, and grow recurring/corporate revenue. |

# 15\. Acceptance Criteria

- A prospect can land on a course page, submit an inquiry, and appear in the CRM with correct source and course interest.
- An admin can create a course, configure pricing, add batches, assign instructor, and publish the course.
- A student can register, complete payment or upload manual payment proof, and receive enrollment confirmation after approval.
- A paid student can access the correct videos, PDFs, pass-question material, and mock exams.
- An instructor can mark attendance and practical assessment for assigned batches.
- The system can calculate certificate eligibility from payment, attendance, progress, exam, and practical status.
- A certificate can be generated with a unique number and verified through a public verification page or QR link.
- Management can view lead, sales, enrollment, revenue, pass-rate, and certificate reports.
- Finance can see payment statuses, receipts, pending payments, and manual approvals with audit history.
- Sensitive admin actions are logged with user, timestamp, and change details.

# 16\. Assumptions and Open Questions

| **Area**             | **Question / assumption to validate**                                                                                                                   |
| -------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Brand/legal name     | Confirm whether the software should use PowerX Electric Inc., PowerX Training Center, or another legal/brand name.                                      |
| Contact details      | Website and flyer/contact notes show different phone information. Confirm official phone, WhatsApp number, email, and address.                          |
| Course catalog       | Confirm final course names, categories, duration, pricing, currency, certificate eligibility, and whether each course is online, in-person, or blended. |
| Certification claims | Confirm exact approved wording for PowerX certificates, Kahramaa exam prep, QCDD training, and any third-party/industry-recognized certificate claim.   |
| Payments             | Choose payment gateway, refund policy, bank-transfer verification method, receipt format, and whether invoices need tax/VAT fields.                     |
| Languages            | Confirm whether Arabic is required at MVP or can be phase 2.                                                                                            |
| AI chat              | Confirm whether AI should be used only for FAQ/lead capture or also for course recommendation and registration support.                                 |
| Content readiness    | Confirm whether videos, PDFs, question banks, and certificate templates already exist in usable digital format.                                         |
| Targets              | Validate planning targets such as QAR 25,000 weekly revenue and 4,000 daily leads before building dashboards around them.                               |
| Deployment           | Confirm hosting preference, domain/subdomain, email sender domain, backups, and admin ownership.                                                        |

# 17\. Source References

This BRS was prepared from the following inputs available during discovery:

- PowerX live website: <https://powerxelect.com/>.
- Supplied website screenshot showing current public positioning and navigation.
- Supplied PowerX Training Center flyer listing training categories and registration details.
- Supplied notebook notes describing the lead funnel, free video, payment, course delivery, exam, certificate, and candidate journey.
- Supplied whiteboard notes describing business vision, weekly revenue target, marketing channels, payment methods, course/pricing ideas, and USP list.

# 18\. Sign-Off

| **Role**               | **Name** | **Signature** | **Date** |
| ---------------------- | -------- | ------------- | -------- |
| Client sponsor         |          |               |          |
| Operations lead        |          |               |          |
| Finance lead           |          |               |          |
| Training lead          |          |               |          |
| Implementation partner |          |               |          |