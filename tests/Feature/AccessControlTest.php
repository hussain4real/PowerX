<?php

use App\Enums\PowerXPermission;
use App\Enums\PowerXRole;
use App\Models\User;
use Database\Seeders\PowerXAccessSeeder;
use Filament\Panel;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PowerXAccessSeeder::class);
});

test('powerx roles and permissions are seeded', function () {
    expect(Role::query()->count())->toBe(count(PowerXRole::cases()))
        ->and(Permission::query()->count())->toBe(count(PowerXPermission::cases()))
        ->and(Role::findByName(PowerXRole::Management->value)->hasPermissionTo(PowerXPermission::ManageSettings->value))->toBeTrue()
        ->and(Role::findByName(PowerXRole::Student->value)->permissions)->toHaveCount(0);
});

test('only authorized staff can access the filament admin panel', function () {
    $manager = User::factory()->create();
    $student = User::factory()->create();

    $manager->assignRole(PowerXRole::Management->value);
    $student->assignRole(PowerXRole::Student->value);

    $adminPanel = Panel::make()->id('admin');

    expect($manager->canAccessPanel($adminPanel))->toBeTrue()
        ->and($student->canAccessPanel($adminPanel))->toBeFalse()
        ->and($manager->canAccessPanel(Panel::make()->id('student')))->toBeFalse();
});

test('guests are redirected to the filament login page', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});
