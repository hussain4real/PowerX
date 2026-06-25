<?php

namespace App\Actions\PowerX;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\StudentProfile;
use App\Models\Team;
use App\Models\User;

class ResolvePortalFinanceAccess
{
    public function canUseInvoice(User $user, Team $team, Invoice $invoice): bool
    {
        $invoice->loadMissing(['company', 'enrollment.studentProfile', 'studentProfile']);

        if ((int) $invoice->team_id !== (int) $team->id) {
            return false;
        }

        return $this->matchesStudentProfile($user, $team, $invoice)
            || $this->matchesCorporateCompany($user, $team, $invoice->company);
    }

    public function canUsePayment(User $user, Team $team, PaymentTransaction $paymentTransaction): bool
    {
        $paymentTransaction->loadMissing(['company', 'invoice.company', 'invoice.enrollment.studentProfile', 'invoice.studentProfile']);

        if ((int) $paymentTransaction->team_id !== (int) $team->id) {
            return false;
        }

        if ($paymentTransaction->invoice) {
            return $this->canUseInvoice($user, $team, $paymentTransaction->invoice);
        }

        return $this->matchesCorporateCompany($user, $team, $paymentTransaction->company);
    }

    private function matchesStudentProfile(User $user, Team $team, Invoice $invoice): bool
    {
        $profile = StudentProfile::query()
            ->whereBelongsTo($user)
            ->whereBelongsTo($team)
            ->first();

        if (! $profile) {
            return false;
        }

        $invoiceStudentProfileId = $invoice->student_profile_id
            ?? $invoice->enrollment?->student_profile_id;

        return (int) $invoiceStudentProfileId === (int) $profile->id;
    }

    private function matchesCorporateCompany(User $user, Team $team, ?Company $company): bool
    {
        if (! $company || ! $user->canViewCorporatePortal()) {
            return false;
        }

        return Company::query()
            ->whereBelongsTo($team)
            ->whereKey($company->id)
            ->where(function ($query) use ($user): void {
                $query
                    ->where('email', $user->email)
                    ->orWhere('metadata->coordinator_email', $user->email);
            })
            ->exists();
    }
}
