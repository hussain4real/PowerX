<?php

namespace App\Actions\PowerX;

use App\Models\AttendanceRecord;
use App\Models\Certificate;
use App\Models\Company;
use App\Models\Course;
use App\Models\ExamAttempt;
use App\Models\Lead;
use App\Models\PaymentTransaction;
use App\Models\Team;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BuildOperationalReport
{
    public function __construct(
        private BuildExamAnalytics $buildExamAnalytics,
        private BuildCampaignAttributionMetrics $buildCampaignAttributionMetrics,
    ) {}

    /**
     * @return array{title: string, team: string, generatedAt: string, sections: array<int, array{key: string, title: string, description: string, columns: array<int, string>, rows: array<int, array<string, string>>}>}
     */
    public function handle(Team $team): array
    {
        return [
            'title' => 'PowerX Operational Report',
            'team' => $team->name,
            'generatedAt' => now()->format('Y-m-d H:i'),
            'sections' => [
                $this->leadSourceReport($team),
                $this->salesPipelineReport($team),
                $this->weeklyRevenueReport($team),
                $this->courseEnrollmentReport($team),
                $this->attendancePracticalReport($team),
                $this->examPerformanceReport($team),
                $this->examAnalyticsReport($team),
                $this->certificateReport($team),
                $this->corporateAccountReport($team),
            ],
        ];
    }

    /**
     * @return array{key: string, title: string, description: string, columns: array<int, string>, rows: array<int, array<string, string>>}
     */
    private function leadSourceReport(Team $team): array
    {
        $rows = $this->buildCampaignAttributionMetrics
            ->handle($team)
            ->sortBy(fn (array $campaign): string => "{$campaign['source']}|{$campaign['campaign']}")
            ->map(fn (array $campaign): array => [
                'Source' => $campaign['source'],
                'Campaign' => $campaign['campaign'],
                'Lead count' => (string) $campaign['leadCount'],
                'Qualified count' => (string) $campaign['qualifiedCount'],
                'Converted count' => (string) $campaign['convertedCount'],
                'Conversion rate' => $campaign['conversionRate'],
                'Cost' => $campaign['costLabel'],
                'Revenue' => $campaign['revenueLabel'],
                'ROI' => $campaign['roiLabel'],
                'Attribution' => $campaign['attributionStatus'],
            ]);

        return $this->section(
            'lead_source',
            'Lead source report',
            'Measures internal CRM campaign conversion, approved finance revenue, spend, and ROI with external tracking blocked.',
            ['Source', 'Campaign', 'Lead count', 'Qualified count', 'Converted count', 'Conversion rate', 'Cost', 'Revenue', 'ROI', 'Attribution'],
            $rows,
        );
    }

    /**
     * @return array{key: string, title: string, description: string, columns: array<int, string>, rows: array<int, array<string, string>>}
     */
    private function salesPipelineReport(Team $team): array
    {
        $rows = Lead::query()
            ->whereBelongsTo($team)
            ->with(['course:id,title', 'owner:id,name'])
            ->select(['id', 'owner_id', 'course_id', 'name', 'status', 'course_interest', 'follow_up_at', 'metadata'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (Lead $lead): array => [
                'Lead' => $lead->name,
                'Status' => $lead->status,
                'Owner' => $lead->owner?->name ?? 'Unassigned',
                'Course' => $lead->course?->title ?? $lead->course_interest ?? 'Not selected',
                'Follow-up date' => $this->dateLabel($lead->follow_up_at),
                'Quotation' => data_get($lead->metadata ?? [], 'quotation_number', 'Not linked'),
                'Payment status' => data_get($lead->metadata ?? [], 'payment_status', 'Not linked'),
            ]);

        return $this->section(
            'sales_pipeline',
            'Sales pipeline report',
            'Tracks inquiry movement, owners, follow-up work, quotations, and payment status.',
            ['Lead', 'Status', 'Owner', 'Course', 'Follow-up date', 'Quotation', 'Payment status'],
            $rows,
        );
    }

    /**
     * @return array{key: string, title: string, description: string, columns: array<int, string>, rows: array<int, array<string, string>>}
     */
    private function weeklyRevenueReport(Team $team): array
    {
        $rows = PaymentTransaction::query()
            ->whereBelongsTo($team)
            ->approved()
            ->with(['enrollment:id,course_id,course_package_id', 'enrollment.course:id,title', 'enrollment.coursePackage:id,name', 'invoice:id,total'])
            ->select(['id', 'enrollment_id', 'invoice_id', 'method', 'currency', 'amount', 'paid_at', 'created_at'])
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (PaymentTransaction $payment): array {
                $invoiceTotal = (float) ($payment->invoice?->total ?? $payment->amount);
                $pendingAmount = max($invoiceTotal - (float) $payment->amount, 0);

                return [
                    'Week' => ($payment->paid_at ?? $payment->created_at)->startOfWeek()->format('Y-m-d'),
                    'Paid date' => $this->dateLabel($payment->paid_at ?? $payment->created_at),
                    'Course' => $payment->enrollment?->course?->title ?? 'Not linked',
                    'Package' => $payment->enrollment?->coursePackage?->name ?? 'Not linked',
                    'Payment method' => $payment->method,
                    'Paid amount' => $this->money($payment->currency, (float) $payment->amount),
                    'Pending amount' => $this->money($payment->currency, $pendingAmount),
                ];
            });

        return $this->section(
            'weekly_revenue',
            'Weekly revenue report',
            'Lists approved payment collections and remaining invoice exposure.',
            ['Week', 'Paid date', 'Course', 'Package', 'Payment method', 'Paid amount', 'Pending amount'],
            $rows,
        );
    }

    /**
     * @return array{key: string, title: string, description: string, columns: array<int, string>, rows: array<int, array<string, string>>}
     */
    private function courseEnrollmentReport(Team $team): array
    {
        $rows = Course::query()
            ->whereBelongsTo($team)
            ->select(['id', 'title'])
            ->with(['trainingBatches:id,course_id,capacity'])
            ->withCount([
                'enrollments',
                'enrollments as active_enrollments_count' => fn (Builder $query) => $query->where('status', 'active'),
                'enrollments as cancelled_enrollments_count' => fn (Builder $query) => $query->where('status', 'cancelled'),
            ])
            ->orderBy('title')
            ->get()
            ->map(function (Course $course): array {
                $capacity = (int) $course->trainingBatches->sum('capacity');
                $activeEnrollments = (int) $course->active_enrollments_count;

                return [
                    'Course' => $course->title,
                    'Batch capacity' => (string) $capacity,
                    'Enrolled students' => (string) (int) $course->enrollments_count,
                    'Active students' => (string) $activeEnrollments,
                    'Seats available' => (string) max($capacity - $activeEnrollments, 0),
                    'Cancellations' => (string) (int) $course->cancelled_enrollments_count,
                ];
            });

        return $this->section(
            'course_enrollment',
            'Course enrollment report',
            'Shows course demand, batch capacity, available seats, and cancellations.',
            ['Course', 'Batch capacity', 'Enrolled students', 'Active students', 'Seats available', 'Cancellations'],
            $rows,
        );
    }

    /**
     * @return array{key: string, title: string, description: string, columns: array<int, string>, rows: array<int, array<string, string>>}
     */
    private function attendancePracticalReport(Team $team): array
    {
        $rows = AttendanceRecord::query()
            ->whereBelongsTo($team)
            ->with(['enrollment:id,student_profile_id,course_id', 'enrollment.course:id,title', 'enrollment.studentProfile:id,full_name', 'trainingSession:id,training_batch_id,title', 'trainingSession.trainingBatch:id,name'])
            ->select(['id', 'training_session_id', 'enrollment_id', 'status', 'practical_outcome', 'practical_score'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (AttendanceRecord $attendance): array => [
                'Student' => $attendance->enrollment?->studentProfile?->full_name ?? 'Not linked',
                'Course' => $attendance->enrollment?->course?->title ?? 'Not linked',
                'Batch' => $attendance->trainingSession?->trainingBatch?->name ?? 'Not linked',
                'Session' => $attendance->trainingSession?->title ?? 'Not linked',
                'Attendance' => $attendance->status,
                'Practical result' => $attendance->practical_outcome ?? 'Not assessed',
                'Practical score' => (string) ($attendance->practical_score ?? 'Not scored'),
            ]);

        return $this->section(
            'attendance_practical',
            'Attendance and practical report',
            'Supports delivery verification and certificate eligibility review.',
            ['Student', 'Course', 'Batch', 'Session', 'Attendance', 'Practical result', 'Practical score'],
            $rows,
        );
    }

    /**
     * @return array{key: string, title: string, description: string, columns: array<int, string>, rows: array<int, array<string, string>>}
     */
    private function examPerformanceReport(Team $team): array
    {
        $rows = ExamAttempt::query()
            ->whereBelongsTo($team)
            ->with([
                'exam:id,course_id,title',
                'exam.course:id,title',
                'studentProfile:id,full_name',
                'enrollment:id,student_profile_id,course_id',
                'enrollment.attendanceRecords:id,enrollment_id,training_session_id',
                'enrollment.attendanceRecords.trainingSession:id,training_batch_id',
                'enrollment.attendanceRecords.trainingSession.trainingBatch:id,course_id,instructor_id,name',
                'enrollment.attendanceRecords.trainingSession.trainingBatch.instructor:id,name',
            ])
            ->select(['id', 'exam_id', 'enrollment_id', 'student_profile_id', 'attempt_number', 'result', 'score', 'submitted_at', 'metadata'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (ExamAttempt $attempt): array {
                $delivery = $this->deliveryLabels($attempt);

                return [
                    'Course' => $attempt->exam?->course?->title ?? 'Not linked',
                    'Batch' => $delivery['batch'],
                    'Instructor' => $delivery['instructor'],
                    'Exam' => $attempt->exam?->title ?? 'Not linked',
                    'Student' => $attempt->studentProfile?->full_name ?? 'Not linked',
                    'Attempt' => (string) $attempt->attempt_number,
                    'Score' => (string) $attempt->score,
                    'Result' => $attempt->result,
                    'Weak topic' => data_get($attempt->metadata ?? [], 'weak_topic', 'Not recorded'),
                ];
            });

        return $this->section(
            'exam_performance',
            'Exam performance report',
            'Monitors pass rates, attempts, scores, and weak topics.',
            ['Course', 'Batch', 'Instructor', 'Exam', 'Student', 'Attempt', 'Score', 'Result', 'Weak topic'],
            $rows,
        );
    }

    /**
     * @return array{key: string, title: string, description: string, columns: array<int, string>, rows: array<int, array<string, string>>}
     */
    private function examAnalyticsReport(Team $team): array
    {
        $analytics = $this->buildExamAnalytics->handle($team);
        $rows = collect($analytics['courses'])
            ->map(fn (array $course): array => [
                'Dimension' => 'Course',
                'Name' => $course['courseTitle'],
                'Attempts' => (string) $course['attempts'],
                'Pass rate' => $course['passRate'].'%',
                'Average score' => (string) $course['averageScore'],
                'Weak topic' => $analytics['summary']['top_weak_topic'] ?? 'Not recorded',
                'Details' => "{$course['passed']} passed / {$course['failed']} failed",
            ])
            ->merge(collect($analytics['batches'])->map(fn (array $batch): array => [
                'Dimension' => 'Batch',
                'Name' => $batch['batchName'],
                'Attempts' => (string) $batch['attempts'],
                'Pass rate' => $batch['passRate'].'%',
                'Average score' => (string) $batch['averageScore'],
                'Weak topic' => $analytics['summary']['top_weak_topic'] ?? 'Not recorded',
                'Details' => $batch['courseTitle'],
            ]))
            ->merge(collect($analytics['instructors'])->map(fn (array $instructor): array => [
                'Dimension' => 'Instructor',
                'Name' => $instructor['instructorName'],
                'Attempts' => (string) $instructor['attempts'],
                'Pass rate' => $instructor['passRate'].'%',
                'Average score' => (string) $instructor['averageScore'],
                'Weak topic' => $analytics['summary']['top_weak_topic'] ?? 'Not recorded',
                'Details' => $instructor['courseTitle'],
            ]))
            ->merge(collect($analytics['questions'])->map(fn (array $question): array => [
                'Dimension' => 'Question',
                'Name' => $question['topic'],
                'Attempts' => (string) $question['attempts'],
                'Pass rate' => $question['accuracyRate'].'%',
                'Average score' => 'Question accuracy',
                'Weak topic' => (string) $question['incorrectAnswers'],
                'Details' => $question['difficulty'] ?? 'Not set',
            ]))
            ->merge(collect($analytics['weakTopics'])->map(fn (array $topic): array => [
                'Dimension' => 'Weak topic',
                'Name' => $topic['topic'],
                'Attempts' => (string) $topic['occurrences'],
                'Pass rate' => 'Needs review',
                'Average score' => (string) $topic['averageScore'],
                'Weak topic' => $topic['topic'],
                'Details' => "{$topic['questionCount']} linked questions",
            ]))
            ->values();

        return $this->section(
            'exam_analytics',
            'Exam analytics report',
            'Breaks exam outcomes down by course, batch, instructor, question, and weak topic.',
            ['Dimension', 'Name', 'Attempts', 'Pass rate', 'Average score', 'Weak topic', 'Details'],
            $rows,
        );
    }

    /**
     * @return array{key: string, title: string, description: string, columns: array<int, string>, rows: array<int, array<string, string>>}
     */
    private function certificateReport(Team $team): array
    {
        $rows = Certificate::query()
            ->whereBelongsTo($team)
            ->with(['course:id,title', 'studentProfile:id,full_name'])
            ->select(['id', 'student_profile_id', 'course_id', 'certificate_number', 'status', 'issued_at', 'expires_at'])
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Certificate $certificate): array => [
                'Certificate number' => $certificate->certificate_number,
                'Student' => $certificate->studentProfile?->full_name ?? 'Not linked',
                'Course' => $certificate->course?->title ?? 'Not linked',
                'Issue date' => $this->dateLabel($certificate->issued_at),
                'Expiry date' => $this->dateLabel($certificate->expires_at),
                'Verification status' => $certificate->status,
            ]);

        return $this->section(
            'certificate',
            'Certificate report',
            'Maintains issued certificate records and renewal opportunities.',
            ['Certificate number', 'Student', 'Course', 'Issue date', 'Expiry date', 'Verification status'],
            $rows,
        );
    }

    /**
     * @return array{key: string, title: string, description: string, columns: array<int, string>, rows: array<int, array<string, string>>}
     */
    private function corporateAccountReport(Team $team): array
    {
        $rows = Company::query()
            ->whereBelongsTo($team)
            ->select(['id', 'name', 'contact_name'])
            ->withCount([
                'studentProfiles as employee_count',
                'enrollments as completed_enrollments_count' => fn (Builder $query) => $query->where('status', 'completed'),
                'invoices as paid_invoices_count' => fn (Builder $query) => $query->where('status', 'paid'),
                'invoices as pending_invoices_count' => fn (Builder $query) => $query->whereIn('status', ['draft', 'issued', 'partial', 'overdue']),
            ])
            ->withSum(['invoices as quotation_value' => fn (Builder $query) => $query->where('type', 'quotation')], 'total')
            ->orderBy('name')
            ->get()
            ->map(fn (Company $company): array => [
                'Company' => $company->name,
                'Contact' => $company->contact_name ?? 'Not set',
                'Employees trained' => (string) (int) $company->completed_enrollments_count,
                'Employee profiles' => (string) (int) $company->employee_count,
                'Quotation value' => $this->money('QAR', (float) ($company->quotation_value ?? 0)),
                'Payment status' => "{$company->paid_invoices_count} paid / {$company->pending_invoices_count} pending",
                'Completion status' => "{$company->completed_enrollments_count} completed enrollments",
            ]);

        return $this->section(
            'corporate_account',
            'Corporate account report',
            'Summarizes company clients, quotations, payments, and completion status.',
            ['Company', 'Contact', 'Employees trained', 'Employee profiles', 'Quotation value', 'Payment status', 'Completion status'],
            $rows,
        );
    }

    /**
     * @return array{batch: string, instructor: string}
     */
    private function deliveryLabels(ExamAttempt $attempt): array
    {
        $courseId = $attempt->exam?->course_id;
        $attendanceRecords = $attempt->enrollment?->attendanceRecords ?? collect();
        $batches = $attendanceRecords
            ->filter(fn (AttendanceRecord $attendanceRecord): bool => $attendanceRecord->trainingSession?->trainingBatch?->course_id === $courseId)
            ->map(fn (AttendanceRecord $attendanceRecord) => $attendanceRecord->trainingSession?->trainingBatch)
            ->filter()
            ->unique('id')
            ->values();

        return [
            'batch' => $batches->pluck('name')->implode(' / ') ?: 'Unassigned batch',
            'instructor' => $batches->pluck('instructor.name')->filter()->unique()->implode(' / ') ?: 'Unassigned instructor',
        ];
    }

    /**
     * @param  Collection<int, array<string, string>>  $rows
     * @return array{key: string, title: string, description: string, columns: array<int, string>, rows: array<int, array<string, string>>}
     */
    private function section(string $key, string $title, string $description, array $columns, Collection $rows): array
    {
        return [
            'key' => $key,
            'title' => $title,
            'description' => $description,
            'columns' => $columns,
            'rows' => $rows->values()->all(),
        ];
    }

    private function dateLabel(?CarbonInterface $date): string
    {
        return $date?->format('Y-m-d H:i') ?? 'Not set';
    }

    private function money(string $currency, float $amount): string
    {
        return $currency.' '.number_format($amount, 2);
    }
}
