<?php

namespace App\Http\Controllers;

use App\Actions\PowerX\BuildStudentPortal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentCertificatesController extends Controller
{
    public function __invoke(Request $request, BuildStudentPortal $buildStudentPortal): Response
    {
        abort_unless($request->user()->canViewStudentPortal($request->user()->currentTeam), 403);

        return Inertia::render('Student/Certificates', $buildStudentPortal->handle(
            user: $request->user(),
            team: $request->user()->currentTeam,
        ));
    }
}
