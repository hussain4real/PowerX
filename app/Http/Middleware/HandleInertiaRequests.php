<?php

namespace App\Http\Middleware;

use App\Enums\PowerXPermission;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $currentTeam = $user?->currentTeam;

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'currentTeam' => fn () => $currentTeam ? $user?->toUserTeam($currentTeam) : null,
            'teams' => fn () => $user?->canManagePowerXTeams() ? $user->toUserTeams(includeCurrent: true) : [],
            'can' => [
                'viewAdminPanel' => $user?->can(PowerXPermission::AdminAccess->value) ?? false,
                'viewOperationsDashboard' => $user?->canViewOperationsDashboard() ?? false,
                'viewStudentPortal' => $user?->canViewStudentPortal($currentTeam) ?? false,
                'viewInstructorPortal' => $user?->canViewInstructorPortal() ?? false,
                'viewCorporatePortal' => $user?->canViewCorporatePortal() ?? false,
                'viewReports' => $user?->can(PowerXPermission::ViewReports->value) ?? false,
                'manageTeams' => $user ? Gate::forUser($user)->allows('viewAny', Team::class) : false,
                'createTeams' => $user ? Gate::forUser($user)->allows('create', Team::class) : false,
            ],
        ];
    }
}
