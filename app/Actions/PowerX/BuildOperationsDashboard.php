<?php

namespace App\Actions\PowerX;

use App\Models\AttendanceRecord;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\ExamAttempt;
use App\Models\Lead;
use App\Models\PaymentTransaction;
use App\Models\Team;
use Illuminate\Support\Collection;

class BuildOperationsDashboard
{
    public function __construct(
        private BuildRenewalGrowthOpportunities $buildRenewalGrowthOpportunities,
        private BuildExamAnalytics $buildExamAnalytics,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(Team $team): array
    {
        $leads = $this->leadMetrics($team);
        $enrollments = $this->enrollmentMetrics($team);
        $finance = $this->financeMetrics($team);
        $examAnalytics = $this->buildExamAnalytics->handle($team);
        $learning = $this->learningMetrics($team, $examAnalytics);
        $growth = $this->growthMetrics($team);

        return [
            'summaryCards' => [
                [
                    'label' => 'New leads',
                    'value' => (string) $leads['new'],
                    'detail' => "{$leads['converted']} converted from {$leads['total']} total; {$leads['overdue_follow_ups']} overdue",
                    'tone' => 'warning',
                ],
                [
                    'label' => 'Approved revenue',
                    'value' => $finance['approved_revenue_label'],
                    'detail' => "{$finance['pending_payments']} payments pending finance review",
                    'tone' => 'success',
                ],
                [
                    'label' => 'Active enrollments',
                    'value' => (string) $enrollments['active'],
                    'detail' => "{$enrollments['approval_queue']} pending approval; {$enrollments['request_information_queue']} need info",
                    'tone' => 'info',
                ],
                [
                    'label' => 'Certificates issued',
                    'value' => (string) $learning['certificates_issued'],
                    'detail' => "{$learning['exam_pass_rate']}% exam pass rate",
                    'tone' => 'neutral',
                ],
            ],
            'leads' => $leads,
            'enrollments' => $enrollments,
            'finance' => $finance,
            'learning' => $learning,
            'growth' => $growth,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function leadMetrics(Team $team): array
    {
        $metrics = Lead::query()
            ->whereBelongsTo($team)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count")
            ->selectRaw("SUM(CASE WHEN status = 'qualified' THEN 1 ELSE 0 END) as qualified_count")
            ->selectRaw("SUM(CASE WHEN status IN ('converted', 'enrolled', 'won') THEN 1 ELSE 0 END) as converted_count")
            ->selectRaw("SUM(CASE WHEN follow_up_at BETWEEN ? AND ? AND status NOT IN ('converted', 'won', 'lost', 'enrolled', 'not_responsive') THEN 1 ELSE 0 END) as due_today_count", [
                now()->startOfDay(),
                now()->endOfDay(),
            ])
            ->selectRaw("SUM(CASE WHEN follow_up_at < ? AND status NOT IN ('converted', 'won', 'lost', 'enrolled', 'not_responsive') THEN 1 ELSE 0 END) as overdue_count", [
                now()->startOfDay(),
            ])
            ->first();

        return [
            'total' => (int) ($metrics->total ?? 0),
            'new' => (int) ($metrics->new_count ?? 0),
            'qualified' => (int) ($metrics->qualified_count ?? 0),
            'converted' => (int) ($metrics->converted_count ?? 0),
            'follow_ups_due_today' => (int) ($metrics->due_today_count ?? 0),
            'overdue_follow_ups' => (int) ($metrics->overdue_count ?? 0),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function enrollmentMetrics(Team $team): array
    {
        $metrics = Enrollment::query()
            ->whereBelongsTo($team)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count")
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as approval_queue_count")
            ->selectRaw("SUM(CASE WHEN status = 'request_more_information' THEN 1 ELSE 0 END) as request_information_count")
            ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count")
            ->selectRaw("SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_count")
            ->first();

        return [
            'total' => (int) ($metrics->total ?? 0),
            'pending' => (int) ($metrics->pending_count ?? 0),
            'approval_queue' => (int) ($metrics->approval_queue_count ?? 0),
            'request_information_queue' => (int) ($metrics->request_information_count ?? 0),
            'active' => (int) ($metrics->active_count ?? 0),
            'completed' => (int) ($metrics->completed_count ?? 0),
            'paid' => (int) ($metrics->paid_count ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function financeMetrics(Team $team): array
    {
        $pendingPayments = PaymentTransaction::query()
            ->whereBelongsTo($team)
            ->pendingApproval()
            ->count();

        $approvedRevenue = PaymentTransaction::query()
            ->whereBelongsTo($team)
            ->approved()
            ->select('currency')
            ->selectRaw('SUM(amount) as total')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('currency')
            ->orderBy('currency')
            ->get()
            ->map(fn (PaymentTransaction $payment) => [
                'currency' => $payment->currency,
                'total' => (float) $payment->total,
                'count' => (int) $payment->count,
            ]);

        return [
            'pending_payments' => $pendingPayments,
            'approved_revenue' => $approvedRevenue->values(),
            'approved_revenue_label' => $this->revenueLabel($approvedRevenue),
        ];
    }

    /**
     * @param  array<string, mixed>  $examAnalytics
     * @return array<string, int|float|string|null>
     */
    private function learningMetrics(Team $team, array $examAnalytics): array
    {
        $attendanceTotal = AttendanceRecord::query()
            ->whereBelongsTo($team)
            ->count();
        $attendancePresent = AttendanceRecord::query()
            ->whereBelongsTo($team)
            ->present()
            ->count();
        $examAttempts = ExamAttempt::query()
            ->whereBelongsTo($team)
            ->whereNotNull('submitted_at')
            ->count();
        $passedAttempts = ExamAttempt::query()
            ->whereBelongsTo($team)
            ->where('result', 'passed')
            ->count();

        return [
            'attendance_total' => $attendanceTotal,
            'attendance_present' => $attendancePresent,
            'attendance_rate' => $attendanceTotal > 0 ? round(($attendancePresent / $attendanceTotal) * 100, 1) : 0.0,
            'exam_attempts' => $examAttempts,
            'passed_attempts' => $passedAttempts,
            'exam_pass_rate' => $examAttempts > 0 ? round(($passedAttempts / $examAttempts) * 100, 1) : 0.0,
            'average_exam_score' => $examAnalytics['summary']['average_score'],
            'top_weak_topic' => $examAnalytics['summary']['top_weak_topic'] ?? null,
            'top_weak_topic_count' => $examAnalytics['summary']['top_weak_topic_occurrences'],
            'certificates_issued' => Certificate::query()
                ->whereBelongsTo($team)
                ->where('status', 'issued')
                ->count(),
        ];
    }

    /**
     * @return array<string, int|string|null>
     */
    private function growthMetrics(Team $team): array
    {
        $opportunities = $this->buildRenewalGrowthOpportunities->handle($team);
        $summary = $opportunities['summary'];

        return [
            'renewal_opportunities' => $summary['renewal_count'],
            'overdue_renewals' => $summary['overdue_count'],
            'campaigns_tracked' => $summary['campaign_count'],
            'campaign_revenue_label' => $summary['campaign_revenue_label'],
            'campaign_cost_label' => $summary['campaign_cost_label'],
            'campaign_roi_label' => $summary['campaign_roi_label'],
            'campaign_roi_leader' => $summary['campaign_roi_leader'],
            'tracking_status' => $summary['tracking_status'],
        ];
    }

    /**
     * @param  Collection<int, array{currency: string, total: float, count: int}>  $approvedRevenue
     */
    private function revenueLabel(Collection $approvedRevenue): string
    {
        if ($approvedRevenue->isEmpty()) {
            return 'QAR 0';
        }

        return $approvedRevenue
            ->map(fn (array $row) => $row['currency'].' '.number_format($row['total'], 0))
            ->implode(' / ');
    }
}
