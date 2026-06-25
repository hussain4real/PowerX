<?php

namespace App\Actions\PowerX;

use App\Models\AttendanceRecord;
use App\Models\Certificate;
use App\Models\Company;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\Team;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BuildCorporatePortal
{
    /**
     * @return array{
     *     companies: array<int, array<string, mixed>>,
     *     summary: array<string, mixed>,
     *     quotations: array<int, array<string, mixed>>,
     *     enrollments: array<int, array<string, mixed>>,
     *     finance: array{invoices: array<int, array<string, mixed>>, payments: array<int, array<string, mixed>>},
     *     attendance: array<int, array<string, mixed>>,
     *     certificates: array<int, array<string, mixed>>,
     *     report: array<string, mixed>,
     *     dataSharingGate: array<string, mixed>
     * }
     */
    public function handle(User $coordinator, Team $team): array
    {
        $companies = $this->visibleCompanies($coordinator, $team);
        $companyIds = $companies->pluck('id')->values();
        $enrollments = $this->enrollments($team, $companyIds);
        $invoices = $this->invoices($team, $companyIds);
        $payments = $this->payments($team, $companyIds);
        $attendance = $this->attendance($team, $companyIds);
        $certificates = $this->certificates($team, $companyIds);

        return [
            'companies' => $this->companyRows($companies),
            'summary' => $this->summary($companies, $enrollments, $invoices, $payments, $attendance, $certificates),
            'quotations' => $this->invoiceRows($invoices->where('type', 'quotation'), $team),
            'enrollments' => $this->enrollmentRows($enrollments),
            'finance' => [
                'invoices' => $this->invoiceRows($invoices, $team),
                'payments' => $this->paymentRows($payments, $team),
            ],
            'attendance' => $this->attendanceRows($attendance),
            'certificates' => $this->certificateRows($certificates),
            'report' => [
                'available' => $companies->isNotEmpty(),
                'scopeLabel' => $companies->pluck('name')->join(', '),
                'generatedAt' => now()->toIso8601String(),
            ],
            'dataSharingGate' => $this->dataSharingGate(),
        ];
    }

    /**
     * @return array{available: bool, title: string, team: string, scope: string, generatedAt: string, gate: string, sections: array<int, array{key: string, title: string, description: string, columns: array<int, string>, rows: array<int, array<string, string>>}>}
     */
    public function report(User $coordinator, Team $team): array
    {
        $portal = $this->handle($coordinator, $team);

        return [
            'available' => (bool) $portal['report']['available'],
            'title' => 'PowerX Corporate Coordinator Report',
            'team' => $team->name,
            'scope' => (string) $portal['report']['scopeLabel'],
            'generatedAt' => now()->format('Y-m-d H:i'),
            'gate' => (string) $portal['dataSharingGate']['releaseLabel'],
            'sections' => [
                $this->section(
                    key: 'company_visibility',
                    title: 'Company visibility',
                    description: 'Company records matched to the signed-in coordinator only.',
                    columns: ['Company', 'Coordinator', 'Contact email', 'Employees', 'Enrollments', 'Invoices'],
                    rows: collect($portal['companies'])->map(fn (array $company): array => [
                        'Company' => (string) $company['name'],
                        'Coordinator' => (string) $company['contactName'],
                        'Contact email' => (string) $company['email'],
                        'Employees' => (string) $company['employeeCount'],
                        'Enrollments' => (string) $company['enrollmentCount'],
                        'Invoices' => (string) $company['invoiceCount'],
                    ])->all(),
                ),
                $this->sectionFromRows(
                    key: 'quotations',
                    title: 'Quotations',
                    description: 'Quotation documents linked to the visible company scope.',
                    columns: ['Number', 'Company', 'Status', 'Course', 'Total', 'Issued'],
                    rows: $portal['quotations'],
                    map: fn (array $quotation): array => [
                        'Number' => (string) $quotation['number'],
                        'Company' => (string) $quotation['companyName'],
                        'Status' => (string) $quotation['status'],
                        'Course' => (string) $quotation['courseTitle'],
                        'Total' => $this->money((float) $quotation['total'], (string) $quotation['currency']),
                        'Issued' => $this->dateLabel($quotation['issuedAt']),
                    ],
                ),
                $this->sectionFromRows(
                    key: 'enrollments',
                    title: 'Enrollments',
                    description: 'Company employee enrollment status without student contact details, document status, or private notes.',
                    columns: ['Employee', 'Company', 'Course', 'Status', 'Payment', 'Attendance', 'Certificates'],
                    rows: $portal['enrollments'],
                    map: fn (array $enrollment): array => [
                        'Employee' => (string) $enrollment['studentName'],
                        'Company' => (string) $enrollment['companyName'],
                        'Course' => (string) $enrollment['courseTitle'],
                        'Status' => (string) $enrollment['status'],
                        'Payment' => (string) $enrollment['paymentStatus'],
                        'Attendance' => "{$enrollment['attendedSessions']}/{$enrollment['attendanceSessions']}",
                        'Certificates' => (string) $enrollment['issuedCertificates'],
                    ],
                ),
                $this->sectionFromRows(
                    key: 'invoices',
                    title: 'Invoices',
                    description: 'Invoice and payment status visible to the company coordinator.',
                    columns: ['Number', 'Company', 'Type', 'Status', 'Total', 'Due', 'Paid'],
                    rows: $portal['finance']['invoices'],
                    map: fn (array $invoice): array => [
                        'Number' => (string) $invoice['number'],
                        'Company' => (string) $invoice['companyName'],
                        'Type' => (string) $invoice['type'],
                        'Status' => (string) $invoice['status'],
                        'Total' => $this->money((float) $invoice['total'], (string) $invoice['currency']),
                        'Due' => $this->dateLabel($invoice['dueAt']),
                        'Paid' => $this->dateLabel($invoice['paidAt']),
                    ],
                ),
                $this->sectionFromRows(
                    key: 'payments',
                    title: 'Payments',
                    description: 'Payment receipts summarized without internal approval details or proof files.',
                    columns: ['Invoice', 'Company', 'Method', 'Status', 'Amount', 'Paid'],
                    rows: $portal['finance']['payments'],
                    map: fn (array $payment): array => [
                        'Invoice' => (string) $payment['invoiceNumber'],
                        'Company' => (string) $payment['companyName'],
                        'Method' => (string) $payment['method'],
                        'Status' => (string) $payment['status'],
                        'Amount' => $this->money((float) $payment['amount'], (string) $payment['currency']),
                        'Paid' => $this->dateLabel($payment['paidAt']),
                    ],
                ),
                $this->sectionFromRows(
                    key: 'attendance',
                    title: 'Attendance',
                    description: 'Attendance summary and practical completion outcomes for visible company enrollments.',
                    columns: ['Employee', 'Course', 'Status', 'Practical outcome'],
                    rows: $portal['attendance'],
                    map: fn (array $attendance): array => [
                        'Employee' => (string) $attendance['studentName'],
                        'Course' => (string) $attendance['courseTitle'],
                        'Status' => (string) $attendance['status'],
                        'Practical outcome' => (string) $attendance['practicalOutcome'],
                    ],
                ),
                $this->sectionFromRows(
                    key: 'certificates',
                    title: 'Completions and certificates',
                    description: 'Issued certificate records and public verification links within the company scope.',
                    columns: ['Employee', 'Course', 'Certificate', 'Status', 'Result', 'Issued'],
                    rows: $portal['certificates'],
                    map: fn (array $certificate): array => [
                        'Employee' => (string) $certificate['studentName'],
                        'Course' => (string) $certificate['courseTitle'],
                        'Certificate' => (string) $certificate['certificateNumber'],
                        'Status' => (string) $certificate['status'],
                        'Result' => (string) $certificate['result'],
                        'Issued' => $this->dateLabel($certificate['issuedAt']),
                    ],
                ),
            ],
        ];
    }

    /**
     * @return Collection<int, Company>
     */
    private function visibleCompanies(User $coordinator, Team $team): Collection
    {
        return Company::query()
            ->whereBelongsTo($team)
            ->where(function (Builder $query) use ($coordinator): void {
                $query
                    ->where('email', $coordinator->email)
                    ->orWhere('metadata->coordinator_email', $coordinator->email);
            })
            ->select(['id', 'team_id', 'name', 'contact_name', 'email', 'metadata'])
            ->withCount([
                'studentProfiles as employee_count',
                'enrollments as enrollment_count',
                'invoices as invoice_count',
            ])
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  Collection<int, int>  $companyIds
     * @return Collection<int, Enrollment>
     */
    private function enrollments(Team $team, Collection $companyIds): Collection
    {
        if ($companyIds->isEmpty()) {
            return collect();
        }

        return Enrollment::query()
            ->whereBelongsTo($team)
            ->whereIn('company_id', $companyIds)
            ->with([
                'company:id,name',
                'studentProfile:id,full_name',
                'course:id,title,category,delivery_mode,slug',
                'coursePackage:id,name,package_type,validity_days,includes_certificate',
            ])
            ->withCount([
                'attendanceRecords as attendance_sessions_count',
                'attendanceRecords as attended_sessions_count' => fn (Builder $query) => $query->present(),
                'certificates as issued_certificates_count' => fn (Builder $query) => $query->where('status', 'issued'),
            ])
            ->select([
                'id',
                'team_id',
                'company_id',
                'student_profile_id',
                'course_id',
                'course_package_id',
                'status',
                'payment_status',
                'approved_at',
            ])
            ->orderByDesc('approved_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  Collection<int, int>  $companyIds
     * @return Collection<int, Invoice>
     */
    private function invoices(Team $team, Collection $companyIds): Collection
    {
        if ($companyIds->isEmpty()) {
            return collect();
        }

        return Invoice::query()
            ->whereBelongsTo($team)
            ->whereIn('company_id', $companyIds)
            ->with([
                'company:id,name',
                'enrollment:id,course_id,course_package_id',
                'enrollment.course:id,title',
                'enrollment.coursePackage:id,name',
                'paymentTransactions',
            ])
            ->select([
                'id',
                'team_id',
                'enrollment_id',
                'company_id',
                'student_profile_id',
                'number',
                'type',
                'status',
                'currency',
                'total',
                'issued_at',
                'due_at',
                'paid_at',
                'metadata',
            ])
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  Collection<int, int>  $companyIds
     * @return Collection<int, PaymentTransaction>
     */
    private function payments(Team $team, Collection $companyIds): Collection
    {
        if ($companyIds->isEmpty()) {
            return collect();
        }

        return PaymentTransaction::query()
            ->whereBelongsTo($team)
            ->whereIn('company_id', $companyIds)
            ->with(['company:id,name', 'invoice:id,number', 'media'])
            ->select([
                'id',
                'team_id',
                'invoice_id',
                'company_id',
                'method',
                'reference',
                'status',
                'currency',
                'amount',
                'paid_at',
                'metadata',
            ])
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  Collection<int, int>  $companyIds
     * @return Collection<int, AttendanceRecord>
     */
    private function attendance(Team $team, Collection $companyIds): Collection
    {
        if ($companyIds->isEmpty()) {
            return collect();
        }

        return AttendanceRecord::query()
            ->whereBelongsTo($team)
            ->whereHas('enrollment', fn (Builder $query) => $query->whereIn('company_id', $companyIds))
            ->with([
                'enrollment:id,company_id,student_profile_id,course_id',
                'enrollment.company:id,name',
                'enrollment.studentProfile:id,full_name',
                'enrollment.course:id,title',
            ])
            ->select([
                'id',
                'team_id',
                'enrollment_id',
                'status',
                'attended_at',
                'practical_outcome',
            ])
            ->orderByDesc('attended_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  Collection<int, int>  $companyIds
     * @return Collection<int, Certificate>
     */
    private function certificates(Team $team, Collection $companyIds): Collection
    {
        if ($companyIds->isEmpty()) {
            return collect();
        }

        return Certificate::query()
            ->whereBelongsTo($team)
            ->whereHas('enrollment', fn (Builder $query) => $query->whereIn('company_id', $companyIds))
            ->with([
                'enrollment:id,company_id',
                'enrollment.company:id,name',
                'studentProfile:id,full_name',
                'course:id,title',
            ])
            ->select([
                'id',
                'team_id',
                'enrollment_id',
                'student_profile_id',
                'course_id',
                'certificate_number',
                'verification_token',
                'status',
                'result',
                'issued_at',
            ])
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  Collection<int, Company>  $companies
     * @return array<int, array<string, mixed>>
     */
    private function companyRows(Collection $companies): array
    {
        return $companies
            ->map(fn (Company $company): array => [
                'id' => $company->id,
                'name' => $company->name,
                'contactName' => $company->contact_name,
                'email' => $company->email,
                'industry' => data_get($company->metadata, 'industry', 'Not recorded'),
                'employeeCount' => (int) $company->employee_count,
                'enrollmentCount' => (int) $company->enrollment_count,
                'invoiceCount' => (int) $company->invoice_count,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Enrollment>  $enrollments
     * @return array<int, array<string, mixed>>
     */
    private function enrollmentRows(Collection $enrollments): array
    {
        return $enrollments
            ->map(fn (Enrollment $enrollment): array => [
                'id' => $enrollment->id,
                'companyName' => $enrollment->company?->name ?? 'Not linked',
                'studentName' => $enrollment->studentProfile?->full_name ?? 'Not linked',
                'courseTitle' => $enrollment->course?->title ?? 'Not linked',
                'courseCategory' => $enrollment->course?->category,
                'deliveryMode' => $enrollment->course?->delivery_mode,
                'packageName' => $enrollment->coursePackage?->name,
                'packageType' => $enrollment->coursePackage?->package_type,
                'status' => $enrollment->status,
                'paymentStatus' => $enrollment->payment_status,
                'attendanceSessions' => (int) $enrollment->attendance_sessions_count,
                'attendedSessions' => (int) $enrollment->attended_sessions_count,
                'issuedCertificates' => (int) $enrollment->issued_certificates_count,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @return array<int, array<string, mixed>>
     */
    private function invoiceRows(Collection $invoices, Team $team): array
    {
        return $invoices
            ->map(fn (Invoice $invoice): array => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'type' => $invoice->type,
                'status' => $invoice->status,
                'currency' => $invoice->currency,
                'total' => (float) $invoice->total,
                'outstandingAmount' => $this->outstandingAmount($invoice),
                'issuedAt' => $this->isoDate($invoice->issued_at),
                'dueAt' => $this->isoDate($invoice->due_at),
                'paidAt' => $this->isoDate($invoice->paid_at),
                'offlineInstructions' => $this->offlinePaymentInstructions($invoice),
                'offlinePaymentProofUrl' => route('corporate.payments.offline-proof.store', [
                    'current_team' => $team,
                    'invoice' => $invoice,
                ]),
                'invoicePdfUrl' => route('corporate.payments.invoices.pdf', [
                    'current_team' => $team,
                    'invoice' => $invoice,
                ]),
                'companyName' => $invoice->company?->name ?? 'Not linked',
                'courseTitle' => $invoice->enrollment?->course?->title ?? data_get($invoice->metadata, 'course_title', 'Not linked'),
                'packageName' => $invoice->enrollment?->coursePackage?->name,
                'seatCount' => data_get($invoice->metadata, 'employee_count', data_get($invoice->metadata, 'seats')),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, PaymentTransaction>  $payments
     * @return array<int, array<string, mixed>>
     */
    private function paymentRows(Collection $payments, Team $team): array
    {
        return $payments
            ->map(fn (PaymentTransaction $payment): array => [
                'id' => $payment->id,
                'companyName' => $payment->company?->name ?? 'Not linked',
                'invoiceNumber' => $payment->invoice?->number ?? 'Not linked',
                'method' => $payment->method,
                'reference' => $payment->reference,
                'status' => $payment->status,
                'currency' => $payment->currency,
                'amount' => (float) $payment->amount,
                'paidAt' => $this->isoDate($payment->paid_at),
                'reviewStatus' => data_get($payment->metadata, 'finance_review.status'),
                'proofStatus' => $payment->hasMedia('payment-proofs') ? 'proof_uploaded' : 'proof_missing',
                'receiptUrl' => $payment->status === PaymentTransaction::STATUS_APPROVED
                    ? route('corporate.payments.receipts.pdf', [
                        'current_team' => $team,
                        'paymentTransaction' => $payment,
                    ])
                    : null,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, AttendanceRecord>  $attendance
     * @return array<int, array<string, mixed>>
     */
    private function attendanceRows(Collection $attendance): array
    {
        return $attendance
            ->map(fn (AttendanceRecord $attendanceRecord): array => [
                'id' => $attendanceRecord->id,
                'companyName' => $attendanceRecord->enrollment?->company?->name ?? 'Not linked',
                'studentName' => $attendanceRecord->enrollment?->studentProfile?->full_name ?? 'Not linked',
                'courseTitle' => $attendanceRecord->enrollment?->course?->title ?? 'Not linked',
                'status' => $attendanceRecord->status,
                'practicalOutcome' => $attendanceRecord->practical_outcome ?? 'Not assessed',
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Certificate>  $certificates
     * @return array<int, array<string, mixed>>
     */
    private function certificateRows(Collection $certificates): array
    {
        return $certificates
            ->map(fn (Certificate $certificate): array => [
                'id' => $certificate->id,
                'companyName' => $certificate->enrollment?->company?->name ?? 'Not linked',
                'studentName' => $certificate->studentProfile?->full_name ?? 'Not linked',
                'courseTitle' => $certificate->course?->title ?? 'Not linked',
                'certificateNumber' => $certificate->certificate_number,
                'status' => $certificate->status,
                'result' => $certificate->result,
                'issuedAt' => $this->isoDate($certificate->issued_at),
                'verifyUrl' => route('certificates.verify', ['token' => $certificate->verification_token]),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Company>  $companies
     * @param  Collection<int, Enrollment>  $enrollments
     * @param  Collection<int, Invoice>  $invoices
     * @param  Collection<int, PaymentTransaction>  $payments
     * @param  Collection<int, AttendanceRecord>  $attendance
     * @param  Collection<int, Certificate>  $certificates
     * @return array<string, mixed>
     */
    private function summary(Collection $companies, Collection $enrollments, Collection $invoices, Collection $payments, Collection $attendance, Collection $certificates): array
    {
        $attendanceTotal = $attendance->count();
        $attendancePresent = $attendance->whereIn('status', ['present', 'late'])->count();
        $approvedPayments = $payments->where('status', 'approved');
        $invoiceExposure = $invoices->sum(fn (Invoice $invoice): float => $this->outstandingAmount($invoice));

        return [
            'companyCount' => $companies->count(),
            'employeeCount' => $companies->sum(fn (Company $company): int => (int) $company->employee_count),
            'enrollmentCount' => $enrollments->count(),
            'activeEnrollments' => $enrollments->where('status', 'active')->count(),
            'completedEnrollments' => $enrollments->where('status', 'completed')->count(),
            'pendingEnrollments' => $enrollments->where('status', 'pending')->count(),
            'quotationCount' => $invoices->where('type', 'quotation')->count(),
            'invoiceCount' => $invoices->where('type', '!=', 'quotation')->count(),
            'outstandingAmount' => (float) $invoiceExposure,
            'paidAmount' => (float) $approvedPayments->sum(fn (PaymentTransaction $payment): float => (float) $payment->amount),
            'currency' => $invoices->first()?->currency ?? $payments->first()?->currency ?? 'QAR',
            'attendanceTotal' => $attendanceTotal,
            'attendancePresent' => $attendancePresent,
            'attendanceRate' => $attendanceTotal === 0 ? 0 : (int) round(($attendancePresent / $attendanceTotal) * 100),
            'certificateCount' => $certificates->where('status', 'issued')->count(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function dataSharingGate(): array
    {
        return [
            'status' => 'level_2_operational',
            'releaseLabel' => 'Level 2 operational sharing',
            'summary' => 'Corporate coordinators can review company-scoped operational records approved for the MVP: profile, quotation, invoice, payment status, employee enrollment, attendance summary, practical outcome, issued certificate verification links, and offline payment proof submission.',
            'scopeRule' => 'This portal only shows company records directly matched to the signed-in coordinator; internal approvals, student contact details, private finance notes, proof storage paths, audit metadata, exam answers, and other-company records are not exposed.',
        ];
    }

    /**
     * @param  array<int, string>  $columns
     * @param  array<int, array<string, string>>  $rows
     * @return array{key: string, title: string, description: string, columns: array<int, string>, rows: array<int, array<string, string>>}
     */
    private function section(string $key, string $title, string $description, array $columns, array $rows): array
    {
        return compact('key', 'title', 'description', 'columns', 'rows');
    }

    /**
     * @param  array<int, string>  $columns
     * @param  array<int, array<string, mixed>>  $rows
     * @param  callable(array<string, mixed>): array<string, string>  $map
     * @return array{key: string, title: string, description: string, columns: array<int, string>, rows: array<int, array<string, string>>}
     */
    private function sectionFromRows(string $key, string $title, string $description, array $columns, array $rows, callable $map): array
    {
        return $this->section(
            key: $key,
            title: $title,
            description: $description,
            columns: $columns,
            rows: collect($rows)->map($map)->all(),
        );
    }

    private function isoDate(?CarbonInterface $date): ?string
    {
        return $date?->toIso8601String();
    }

    private function outstandingAmount(Invoice $invoice): float
    {
        $approvedTotal = $invoice->paymentTransactions
            ->where('status', PaymentTransaction::STATUS_APPROVED)
            ->sum(fn (PaymentTransaction $payment): float => (float) $payment->amount);

        return max(0, (float) $invoice->total - (float) $approvedTotal);
    }

    private function offlinePaymentInstructions(Invoice $invoice): string
    {
        $methods = collect(PaymentTransaction::manualMethodOptions())->values()->join(', ', ' or ');
        $currency = config('powerx_payments.manual.bank_transfer.currency', $invoice->currency);

        return __('Submit :methods proof in :currency with invoice :invoice as the reference. Finance approval is required before paid access opens.', [
            'methods' => $methods,
            'currency' => $currency,
            'invoice' => $invoice->number,
        ]);
    }

    private function dateLabel(mixed $date): string
    {
        return is_string($date) && $date !== '' ? $date : 'Not set';
    }

    private function money(float $amount, string $currency): string
    {
        return $currency.' '.number_format($amount, 2);
    }
}
