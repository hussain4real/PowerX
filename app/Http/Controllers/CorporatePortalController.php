<?php

namespace App\Http\Controllers;

use App\Actions\PowerX\BuildCorporatePortal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CorporatePortalController extends Controller
{
    public function __invoke(Request $request, BuildCorporatePortal $buildCorporatePortal): Response
    {
        abort_unless($request->user()->canViewCorporatePortal(), 403);

        return Inertia::render('Corporate/Portal', $buildCorporatePortal->handle(
            coordinator: $request->user(),
            team: $request->user()->currentTeam,
        ));
    }
}
