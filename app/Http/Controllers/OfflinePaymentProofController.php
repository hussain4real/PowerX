<?php

namespace App\Http\Controllers;

use App\Actions\PowerX\SubmitOfflinePaymentProof;
use App\Http\Requests\StoreOfflinePaymentProofRequest;
use App\Models\Invoice;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;

class OfflinePaymentProofController extends Controller
{
    public function store(
        StoreOfflinePaymentProofRequest $request,
        Team $currentTeam,
        Invoice $invoice,
        SubmitOfflinePaymentProof $submitOfflinePaymentProof,
    ): RedirectResponse {
        abort_unless((int) $invoice->team_id === (int) $currentTeam->id, 404);

        $proof = $request->file('proof');

        $submitOfflinePaymentProof->handle(
            invoice: $invoice,
            submitter: $request->user(),
            data: $request->validated(),
            proof: $proof instanceof UploadedFile ? $proof : null,
        );

        return back()->with('success', __('Payment proof submitted for finance review.'));
    }
}
