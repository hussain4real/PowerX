<?php

namespace App\Http\Controllers;

use App\Actions\PowerX\BuildInstructorPortal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InstructorPortalController extends Controller
{
    public function __invoke(Request $request, BuildInstructorPortal $buildInstructorPortal): Response
    {
        return Inertia::render('Instructor/Portal', $buildInstructorPortal->handle(
            instructor: $request->user(),
            team: $request->user()->currentTeam,
        ));
    }
}
