<?php

namespace App\Http\Controllers;

use App\Actions\PowerX\BuildOperationsDashboard;
use App\Enums\PowerXPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, BuildOperationsDashboard $buildOperationsDashboard): Response|RedirectResponse
    {
        $user = $request->user();
        $team = $user->currentTeam;

        abort_unless($team, 403);

        if (! $user->canViewOperationsDashboard()) {
            if ($user->canViewInstructorPortal()) {
                return to_route('instructor.portal', ['current_team' => $team]);
            }

            if ($user->canViewStudentPortal($team)) {
                return to_route('student.portal', ['current_team' => $team]);
            }

            if ($user->canViewCorporatePortal()) {
                return to_route('corporate.portal', ['current_team' => $team]);
            }

            if ($user->can(PowerXPermission::AdminAccess->value)) {
                return redirect('/admin');
            }

            abort(403);
        }

        return Inertia::render('Dashboard', [
            'metrics' => $buildOperationsDashboard->handle($team),
        ]);
    }
}
