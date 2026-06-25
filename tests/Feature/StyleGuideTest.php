<?php

use Inertia\Testing\AssertableInertia as Assert;

test('style guide page is publicly visible', function () {
    $this->withoutVite();

    $this->get(route('style-guide'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('StyleGuide'));

    $source = file_get_contents(resource_path('js/pages/StyleGuide.vue'));

    expect($source)
        ->toContain('Theme switcher ready')
        ->toContain('Light variant')
        ->toContain('Dark variant')
        ->toContain('PowerX design system for light and dark public surfaces.');

    $rootView = file_get_contents(resource_path('views/app.blade.php'));
    $logoComponent = file_get_contents(resource_path('js/components/AppLogoIcon.vue'));

    expect($rootView)
        ->toContain('/favicon.ico')
        ->toContain('/favicon.png')
        ->toContain('/apple-touch-icon.png')
        ->not->toContain('/favicon.svg')
        ->and($logoComponent)
        ->toContain('/images/brand/powerx-logo.png')
        ->not->toContain('<svg');

    $brandStatusBadge = file_get_contents(resource_path('js/components/powerx/BrandStatusBadge.vue'));

    expect($brandStatusBadge)
        ->toContain('text-green-700')
        ->toContain('dark:text-green-300')
        ->toContain('bg-powerx-yellow/20')
        ->toContain('dark:bg-powerx-yellow')
        ->toContain('text-powerx-blue')
        ->toContain('dark:text-powerx-cyan')
        ->toContain('bg-powerx-navy/10')
        ->toContain('text-powerx-navy')
        ->toContain('dark:text-white');

    $sidebarHeader = file_get_contents(resource_path('js/components/AppSidebarHeader.vue'));
    $header = file_get_contents(resource_path('js/components/AppHeader.vue'));
    $authSimpleLayout = file_get_contents(resource_path('js/layouts/auth/AuthSimpleLayout.vue'));
    $authCardLayout = file_get_contents(resource_path('js/layouts/auth/AuthCardLayout.vue'));
    $authSplitLayout = file_get_contents(resource_path('js/layouts/auth/AuthSplitLayout.vue'));

    expect($sidebarHeader)
        ->toContain('PublicThemeSwitcher')
        ->toContain('justify-between')
        ->and($header)
        ->toContain('PublicThemeSwitcher')
        ->and($authSimpleLayout)
        ->toContain('PublicThemeSwitcher')
        ->and($authCardLayout)
        ->toContain('PublicThemeSwitcher')
        ->and($authSplitLayout)
        ->toContain('PublicThemeSwitcher');
});
