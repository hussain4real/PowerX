<?php

namespace App\Http\Controllers;

use App\Actions\PowerX\BuildStudentPortal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentPortalController extends Controller
{
    public function __invoke(Request $request, BuildStudentPortal $buildStudentPortal): Response
    {
        return Inertia::render('Student/Portal', $buildStudentPortal->handle(
            user: $request->user(),
            team: $request->user()->currentTeam,
        ));
    }
}
