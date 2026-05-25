<?php

namespace App\Http\Controllers;

use App\Actions\PowerX\CreateCorporateQuotation;
use App\Http\Requests\StoreCorporateQuotationRequest;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CorporateQuotationController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Corporate/QuotationRequest', [
            'courseOptions' => Course::query()
                ->published()
                ->with(['packages' => fn ($query) => $query->active()->orderBy('price')])
                ->orderBy('title')
                ->get()
                ->map(fn (Course $course): array => [
                    'id' => $course->id,
                    'title' => $course->title,
                    'category' => $course->category,
                    'currency' => $course->currency,
                    'basePrice' => (float) $course->base_price,
                    'packages' => $course->packages
                        ->map(fn ($package): array => [
                            'id' => $package->id,
                            'name' => $package->name,
                            'currency' => $package->currency,
                            'price' => (float) ($package->discount_price ?? $package->price),
                        ])
                        ->values()
                        ->all(),
                ])
                ->values(),
        ]);
    }

    public function store(StoreCorporateQuotationRequest $request, CreateCorporateQuotation $createCorporateQuotation): RedirectResponse
    {
        $quotation = $createCorporateQuotation->handle($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Corporate quotation :number created. PowerX will follow up with payment and batch assignment.', ['number' => $quotation->number]),
        ]);

        return to_route('corporate.quotations.create');
    }
}
