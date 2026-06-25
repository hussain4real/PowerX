<?php

namespace App\Actions\PowerX;

use App\Models\Lead;
use App\Models\PaymentTransaction;
use App\Models\Team;
use Illuminate\Support\Collection;

class BuildCampaignAttributionMetrics
{
    public const TRACKING_STATUS_LABEL = 'Internal CRM attribution with UTM, referral, campaign cost, and approved finance revenue matching.';

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function handle(Team $team): Collection
    {
        $campaigns = $this->campaignsFromLeads($team);

        if ($campaigns->isEmpty()) {
            return collect();
        }

        $convertedLeads = $this->convertedLeads($team);

        foreach ($this->approvedPayments($team) as $payment) {
            $campaignKey = $this->campaignKeyFromPayment($payment, $campaigns)
                ?? $this->campaignKeyFromLead($this->matchingLeadForPayment($payment, $convertedLeads));

            if ($campaignKey === null || ! $campaigns->has($campaignKey)) {
                continue;
            }

            $campaign = $campaigns->get($campaignKey);
            $currency = $this->currency($payment->currency);
            $campaign['revenueByCurrency'][$currency] = ($campaign['revenueByCurrency'][$currency] ?? 0) + (float) $payment->amount;

            $campaigns->put($campaignKey, $campaign);
        }

        return $campaigns
            ->map(fn (array $campaign): array => $this->formatCampaign($campaign))
            ->sortBy([
                ['convertedCount', 'desc'],
                ['qualifiedCount', 'desc'],
                ['leadCount', 'desc'],
                ['campaign', 'asc'],
            ])
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $campaigns
     * @return array{campaign_revenue_label: string, campaign_cost_label: string, campaign_roi_label: string, campaign_roi_leader: string|null, referral_conversion_label: string, tracking_status: string}
     */
    public function summarize(Collection $campaigns): array
    {
        $campaignsWithRoi = $campaigns
            ->filter(fn (array $campaign): bool => $campaign['roi'] !== null)
            ->sortByDesc('roi')
            ->values();
        $topCampaign = $campaignsWithRoi->first();

        return [
            'campaign_revenue_label' => $this->moneySummary($this->sumCurrencyBuckets($campaigns, 'revenueByCurrency')),
            'campaign_cost_label' => $this->moneySummary($this->sumCurrencyBuckets($campaigns, 'costByCurrency')),
            'campaign_roi_label' => $topCampaign['roiLabel'] ?? 'Not set',
            'campaign_roi_leader' => $topCampaign ? "{$topCampaign['source']} / {$topCampaign['campaign']}" : null,
            'referral_conversion_label' => $this->referralConversionLabel($campaigns),
            'tracking_status' => $this->trackingStatusLabel(),
        ];
    }

    /**
     * @return Collection<string, array<string, mixed>>
     */
    private function campaignsFromLeads(Team $team): Collection
    {
        return Lead::query()
            ->whereBelongsTo($team)
            ->select(['id', 'company_id', 'course_id', 'source', 'campaign', 'email', 'phone', 'status', 'converted_at', 'metadata'])
            ->get()
            ->groupBy(fn (Lead $lead): string => $this->campaignKey($lead->source, $lead->campaign))
            ->map(function (Collection $leads): array {
                /** @var Lead $firstLead */
                $firstLead = $leads->first();

                $convertedCount = $leads
                    ->whereIn('status', [Lead::STATUS_CONVERTED_LEGACY, Lead::STATUS_ENROLLED, Lead::STATUS_WON])
                    ->count();

                return [
                    'source' => $this->sourceLabel($firstLead->source),
                    'campaign' => $this->campaignLabel($firstLead->campaign),
                    'channelGroup' => $this->channelGroup($firstLead),
                    'leadCount' => $leads->count(),
                    'qualifiedCount' => $leads->where('status', 'qualified')->count(),
                    'convertedCount' => $convertedCount,
                    'enrollmentCount' => $convertedCount,
                    'referralCount' => $leads->filter(fn (Lead $lead): bool => $this->isReferralLead($lead))->count(),
                    'referralConvertedCount' => $leads
                        ->filter(fn (Lead $lead): bool => $this->isReferralLead($lead))
                        ->whereIn('status', [Lead::STATUS_CONVERTED_LEGACY, Lead::STATUS_ENROLLED, Lead::STATUS_WON])
                        ->count(),
                    'costByCurrency' => $this->leadCostByCurrency($leads),
                    'revenueByCurrency' => [],
                ];
            });
    }

    /**
     * @return Collection<int, Lead>
     */
    private function convertedLeads(Team $team): Collection
    {
        return Lead::query()
            ->whereBelongsTo($team)
            ->whereIn('status', [Lead::STATUS_CONVERTED_LEGACY, Lead::STATUS_ENROLLED, Lead::STATUS_WON])
            ->select(['id', 'company_id', 'course_id', 'source', 'campaign', 'email', 'phone', 'converted_at'])
            ->orderByDesc('converted_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return Collection<int, PaymentTransaction>
     */
    private function approvedPayments(Team $team): Collection
    {
        return PaymentTransaction::query()
            ->whereBelongsTo($team)
            ->approved()
            ->with([
                'studentProfile:id,email,mobile',
                'enrollment:id,student_profile_id,company_id,course_id',
                'enrollment.studentProfile:id,email,mobile',
            ])
            ->select(['id', 'team_id', 'enrollment_id', 'student_profile_id', 'company_id', 'currency', 'amount', 'metadata'])
            ->get();
    }

    /**
     * @param  Collection<int, Lead>  $leads
     * @return array<string, float>
     */
    private function leadCostByCurrency(Collection $leads): array
    {
        return $leads->reduce(function (array $costByCurrency, Lead $lead): array {
            [$currency, $amount] = $this->leadCampaignCost($lead);

            if ($amount > 0) {
                $costByCurrency[$currency] = ($costByCurrency[$currency] ?? 0) + $amount;
            }

            return $costByCurrency;
        }, []);
    }

    /**
     * @return array{0: string, 1: float}
     */
    private function leadCampaignCost(Lead $lead): array
    {
        $metadata = $lead->metadata ?? [];
        $amount = data_get($metadata, 'campaign_cost')
            ?? data_get($metadata, 'campaign.cost')
            ?? data_get($metadata, 'marketing_cost')
            ?? data_get($metadata, 'marketing.cost');

        if (! is_numeric($amount)) {
            return [$this->currency(null), 0.0];
        }

        return [
            $this->currency(data_get($metadata, 'campaign_cost_currency') ?? data_get($metadata, 'campaign.currency') ?? data_get($metadata, 'marketing.currency')),
            max((float) $amount, 0.0),
        ];
    }

    /**
     * @param  Collection<string, array<string, mixed>>  $campaigns
     */
    private function campaignKeyFromPayment(PaymentTransaction $payment, Collection $campaigns): ?string
    {
        $metadata = $payment->metadata ?? [];
        $source = data_get($metadata, 'attribution.source') ?? data_get($metadata, 'campaign_source');
        $campaign = data_get($metadata, 'attribution.campaign') ?? data_get($metadata, 'campaign');

        if (blank($source) && blank($campaign)) {
            return null;
        }

        $campaignKey = $this->campaignKey($source, $campaign);

        return $campaigns->has($campaignKey) ? $campaignKey : null;
    }

    /**
     * @param  Collection<int, Lead>  $convertedLeads
     */
    private function matchingLeadForPayment(PaymentTransaction $payment, Collection $convertedLeads): ?Lead
    {
        $studentProfile = $payment->studentProfile ?? $payment->enrollment?->studentProfile;
        $email = $this->normalizeEmail($studentProfile?->email);
        $phone = $this->normalizePhone($studentProfile?->mobile);
        $companyId = $payment->company_id ?? $payment->enrollment?->company_id;
        $courseId = $payment->enrollment?->course_id;

        return $convertedLeads->first(fn (Lead $lead): bool => $email !== null && $this->normalizeEmail($lead->email) === $email)
            ?? $convertedLeads->first(fn (Lead $lead): bool => $phone !== null && $this->normalizePhone($lead->phone) === $phone)
            ?? $convertedLeads->first(fn (Lead $lead): bool => $companyId !== null && $courseId !== null && $lead->company_id === $companyId && $lead->course_id === $courseId);
    }

    private function campaignKeyFromLead(?Lead $lead): ?string
    {
        return $lead ? $this->campaignKey($lead->source, $lead->campaign) : null;
    }

    /**
     * @param  array<string, mixed>  $campaign
     * @return array<string, mixed>
     */
    private function formatCampaign(array $campaign): array
    {
        $costByCurrency = $this->sortCurrencyBucket($campaign['costByCurrency']);
        $revenueByCurrency = $this->sortCurrencyBucket($campaign['revenueByCurrency']);
        $roiCurrency = $this->roiCurrency($costByCurrency, $revenueByCurrency);
        $cost = $roiCurrency ? ($costByCurrency[$roiCurrency] ?? 0.0) : 0.0;
        $revenue = $roiCurrency ? ($revenueByCurrency[$roiCurrency] ?? 0.0) : 0.0;
        $roi = $cost > 0 ? round((($revenue - $cost) / $cost) * 100, 1) : null;

        return [
            ...$campaign,
            'conversionRate' => $this->percentage($campaign['convertedCount'], $campaign['leadCount']),
            'referralConversionRate' => $this->percentage($campaign['referralConvertedCount'], max((int) $campaign['referralCount'], 0)),
            'costByCurrency' => $costByCurrency,
            'costLabel' => $this->moneySummary($costByCurrency),
            'revenueByCurrency' => $revenueByCurrency,
            'revenueLabel' => $this->moneySummary($revenueByCurrency),
            'roi' => $roi,
            'roiLabel' => $roi === null ? 'Not set' : number_format($roi, 1).'%',
            'attributionStatus' => $this->trackingStatusLabel(),
        ];
    }

    /**
     * @param  array<string, float>  $costByCurrency
     * @param  array<string, float>  $revenueByCurrency
     */
    private function roiCurrency(array $costByCurrency, array $revenueByCurrency): ?string
    {
        if (array_key_exists('QAR', $costByCurrency)) {
            return 'QAR';
        }

        return array_key_first($costByCurrency) ?? array_key_first($revenueByCurrency);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $campaigns
     * @return array<string, float>
     */
    private function sumCurrencyBuckets(Collection $campaigns, string $key): array
    {
        return $campaigns->reduce(function (array $totals, array $campaign) use ($key): array {
            foreach ($campaign[$key] as $currency => $amount) {
                $totals[$currency] = ($totals[$currency] ?? 0) + (float) $amount;
            }

            return $totals;
        }, []);
    }

    /**
     * @param  array<string, float>  $amounts
     * @return array<string, float>
     */
    private function sortCurrencyBucket(array $amounts): array
    {
        ksort($amounts);

        return $amounts;
    }

    /**
     * @param  array<string, float>  $amounts
     */
    private function moneySummary(array $amounts): string
    {
        $amounts = $this->sortCurrencyBucket(array_filter($amounts, fn (float $amount): bool => $amount > 0));

        if ($amounts === []) {
            return 'QAR 0.00';
        }

        return collect($amounts)
            ->map(fn (float $amount, string $currency): string => $currency.' '.number_format($amount, 2))
            ->implode(' / ');
    }

    private function sourceLabel(?string $source): string
    {
        return filled($source) ? $source : 'Unattributed';
    }

    private function campaignLabel(?string $campaign): string
    {
        return filled($campaign) ? $campaign : 'Not set';
    }

    private function campaignKey(?string $source, ?string $campaign): string
    {
        return str($this->sourceLabel($source))->lower()->value().'|'.str($this->campaignLabel($campaign))->lower()->value();
    }

    private function currency(mixed $currency): string
    {
        return str(filled($currency) ? (string) $currency : config('powerx_growth.campaigns.default_currency', 'QAR'))->upper()->value();
    }

    private function trackingStatusLabel(): string
    {
        return (string) config('powerx_growth.campaigns.attribution_status', self::TRACKING_STATUS_LABEL);
    }

    private function channelGroup(Lead $lead): string
    {
        $metadata = $lead->metadata ?? [];
        $source = str((string) ($lead->source ?? data_get($metadata, 'attribution.utm_source')))->lower()->value();
        $medium = str((string) data_get($metadata, 'attribution.utm_medium'))->lower()->value();
        $stored = data_get($metadata, 'channel_group');

        if (filled($stored)) {
            return (string) $stored;
        }

        if ($this->isReferralLead($lead)) {
            return 'Referral';
        }

        if ($source === 'ai_chat' || data_get($metadata, 'channel') === 'ai_assistant') {
            return 'AI assistant';
        }

        if (in_array($source, ['instagram', 'linkedin', 'youtube', 'google', 'meta', 'facebook', 'paid_ads'], true) || in_array($medium, ['cpc', 'paid', 'paid-social'], true)) {
            return 'Paid / social';
        }

        if (in_array($source, ['email', 'newsletter'], true)) {
            return 'Email';
        }

        if (in_array($source, ['whatsapp', 'phone', 'walk-in'], true)) {
            return 'Direct';
        }

        return 'Website';
    }

    private function isReferralLead(Lead $lead): bool
    {
        return $lead->source === 'referral' || filled(data_get($lead->metadata ?? [], 'referral.name'));
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $campaigns
     */
    private function referralConversionLabel(Collection $campaigns): string
    {
        $referrals = (int) $campaigns->sum('referralCount');

        if ($referrals === 0) {
            return '0 / 0';
        }

        $converted = (int) $campaigns->filter(fn (array $campaign): bool => $campaign['referralCount'] > 0)->sum('referralConvertedCount');

        return $converted.' / '.$referrals.' ('.$this->percentage($converted, $referrals).')';
    }

    private function normalizeEmail(?string $email): ?string
    {
        return filled($email) ? str($email)->lower()->value() : null;
    }

    private function normalizePhone(?string $phone): ?string
    {
        $digits = filled($phone) ? preg_replace('/\D+/', '', $phone) : null;

        return filled($digits) ? $digits : null;
    }

    private function percentage(int $value, int $total): string
    {
        return $total > 0 ? number_format(($value / $total) * 100, 1).'%' : '0.0%';
    }
}
