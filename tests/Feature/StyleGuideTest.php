<?php

use Inertia\Testing\AssertableInertia as Assert;

test('style guide page is publicly visible', function () {
    $this->withoutVite();

    $this->get(route('style-guide'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('StyleGuide'));
});
