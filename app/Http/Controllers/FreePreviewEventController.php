<?php

namespace App\Http\Controllers;

use App\Actions\PowerX\RecordFreePreviewEvent;
use App\Http\Requests\StoreFreePreviewEventRequest;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class FreePreviewEventController extends Controller
{
    public function store(Course $course, StoreFreePreviewEventRequest $request, RecordFreePreviewEvent $recordFreePreviewEvent): RedirectResponse
    {
        abort_unless($course->isPublished(), 404);

        $recordFreePreviewEvent->handle($course, $request->validated(), $request->user(), $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Preview activity saved.')]);

        return back();
    }
}
