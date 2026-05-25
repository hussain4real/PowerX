<?php

namespace App\Http\Controllers;

use App\Actions\PowerX\BuildOperationsDashboard;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, BuildOperationsDashboard $buildOperationsDashboard): Response
    {
        return Inertia::render('Dashboard', [
            'metrics' => $buildOperationsDashboard->handle($request->user()->currentTeam),
        ]);
    }
}
