<?php

namespace App\Actions\PowerX;

use App\Models\Certificate;
use App\Models\Communication;
use App\Models\Team;
use Illuminate\Support\Collection;

class CreateRenewalCampaignCommunications
{
    public function __construct(private CreateRenewalReminderCommunication $createRenewalReminderCommunication) {}

    /**
     * @return Collection<int, Communication>
     */
    public function handle(Team $team): Collection
    {
        return Certificate::query()
            ->whereBelongsTo($team)
            ->where('status', 'issued')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(BuildRenewalGrowthOpportunities::RENEWAL_WINDOW_DAYS))
            ->with(['course', 'studentProfile.company', 'team'])
            ->orderBy('expires_at')
            ->get()
            ->map(fn (Certificate $certificate): Communication => $this->existingCommunication($certificate)
                ?? $this->createRenewalReminderCommunication->handle($certificate))
            ->values();
    }

    private function existingCommunication(Certificate $certificate): ?Communication
    {
        return Communication::query()
            ->whereBelongsTo($certificate->team)
            ->where('template_key', 'renewal_reminder')
            ->get()
            ->first(fn (Communication $communication): bool => (int) data_get($communication->metadata ?? [], 'certificate_id') === $certificate->id);
    }
}
