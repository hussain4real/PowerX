<?php

namespace App\Http\Controllers;

use App\Actions\PowerX\CreateLeadInquiry;
use App\Http\Requests\StoreLeadInquiryRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class LeadInquiryController extends Controller
{
    public function store(StoreLeadInquiryRequest $request, CreateLeadInquiry $createLeadInquiry): RedirectResponse
    {
        $lead = $createLeadInquiry->handle($request->validated());

        $request->session()->put('powerx.lead_id', $lead->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Thanks. PowerX will contact you shortly.')]);

        return back();
    }
}
