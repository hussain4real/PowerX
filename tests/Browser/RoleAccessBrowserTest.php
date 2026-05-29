<?php

use Database\Seeders\PowerXDemoSeeder;

beforeEach(function (): void {
    $this->seed(PowerXDemoSeeder::class);
});

test('browser personas land on role safe workspaces', function (string $email, string $path, array $visible, array $hiddenPaths): void {
    $page = $this
        ->visit('/login')
        ->fill('Email address', $email)
        ->fill('Password', 'password')
        ->pressAndWaitFor('Log in', 2)
        ->assertPathIs($path)
        ->assertNoJavaScriptErrors();

    foreach ($visible as $text) {
        $page->assertSee($text);
    }

    foreach ($hiddenPaths as $hiddenPath) {
        expect($page->content())->not->toContain($hiddenPath);
    }
})->with([
    'management' => [
        'test@example.com',
        '/powerx-training-center/dashboard',
        ['Dashboard', 'Admin panel', 'Team settings', 'PowerX operations'],
        ['/powerx-training-center/student-portal', '/powerx-training-center/instructor-portal', '/powerx-training-center/corporate-portal'],
    ],
    'student' => [
        'student@powerx.test',
        '/powerx-training-center/student-portal',
        ['Student learning portal', "Fatima Ali's training dashboard"],
        ['/admin', '/settings/teams', '/powerx-training-center/corporate-portal'],
    ],
    'instructor' => [
        'instructor@powerx.test',
        '/powerx-training-center/instructor-portal',
        ['Instructor workspace', 'Assigned classes and practical records', 'Admin panel'],
        ['/powerx-training-center/dashboard', '/powerx-training-center/student-portal', '/settings/teams', '/powerx-training-center/corporate-portal'],
    ],
    'corporate' => [
        'corporate@powerx.test',
        '/powerx-training-center/corporate-portal',
        ['Corporate workspace', 'Read-only company coordination'],
        ['/powerx-training-center/student-portal', '/powerx-training-center/instructor-portal', '/admin', '/settings/teams'],
    ],
]);

test('staff browser personas without operations dashboard access go to admin', function (string $email, string $name): void {
    $page = $this
        ->visit('/login')
        ->fill('Email address', $email)
        ->fill('Password', 'password')
        ->pressAndWaitFor('Log in', 4)
        ->wait(1)
        ->assertNoJavaScriptErrors();

    expect($page->content())
        ->toContain('Dashboard')
        ->toContain($name)
        ->toContain('PowerX Operations')
        ->not->toContain('/powerx-training-center/dashboard')
        ->not->toContain('/settings/teams');
})->with([
    'sales' => ['sales@powerx.test', 'Noura Sales'],
    'finance' => ['finance@powerx.test', 'Hassan Finance'],
    'support' => ['support@powerx.test', 'Sara Support'],
]);

test('student menu anchors update the active navigation state', function (): void {
    $studentPortal = '/powerx-training-center/student-portal';
    $paymentsSection = "{$studentPortal}#student-payments";

    $page = $this
        ->visit('/login')
        ->fill('Email address', 'student@powerx.test')
        ->fill('Password', 'password')
        ->pressAndWaitFor('Log in', 2)
        ->assertPathIs($studentPortal)
        ->assertDataAttribute("[data-sidebar=\"menu-button\"][data-size=\"default\"][href=\"{$studentPortal}\"]", 'active', 'true')
        ->assertDataAttribute("[data-sidebar=\"menu-button\"][data-size=\"default\"][href=\"{$paymentsSection}\"]", 'active', 'false');

    $page
        ->click('Payments')
        ->wait(1)
        ->assertFragmentIs('student-payments')
        ->assertDataAttribute("[data-sidebar=\"menu-button\"][data-size=\"default\"][href=\"{$studentPortal}\"]", 'active', 'false')
        ->assertDataAttribute("[data-sidebar=\"menu-button\"][data-size=\"default\"][href=\"{$paymentsSection}\"]", 'active', 'true')
        ->assertNoJavaScriptErrors();
});

test('admin panel menu leaves the Inertia shell for Filament', function (): void {
    $page = $this
        ->visit('/login')
        ->fill('Email address', 'test@example.com')
        ->fill('Password', 'password')
        ->pressAndWaitFor('Log in', 2)
        ->assertPathIs('/powerx-training-center/dashboard')
        ->click('Admin panel')
        ->wait(2)
        ->assertPathIs('/admin')
        ->assertNoJavaScriptErrors();

    expect($page->content())
        ->toContain('PowerX Operations')
        ->not->toContain('All Inertia requests must receive a valid Inertia response');
});
