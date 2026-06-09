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
});
