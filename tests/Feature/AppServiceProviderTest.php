<?php

use App\Providers\AppServiceProvider;
use Illuminate\Validation\Rules\Password;

test('production password defaults require strong uncompromised passwords', function () {
    $originalEnvironment = app()->environment();

    try {
        $this->app['env'] = 'production';

        (new AppServiceProvider($this->app))->boot();

        $password = Password::defaults();
        $configuration = (fn () => [
            'min' => $this->min,
            'mixedCase' => $this->mixedCase,
            'letters' => $this->letters,
            'numbers' => $this->numbers,
            'symbols' => $this->symbols,
            'uncompromised' => $this->uncompromised,
        ])->call($password);

        expect($configuration)->toBe([
            'min' => 12,
            'mixedCase' => true,
            'letters' => true,
            'numbers' => true,
            'symbols' => true,
            'uncompromised' => true,
        ]);
    } finally {
        $this->app['env'] = $originalEnvironment;

        (new AppServiceProvider($this->app))->boot();
    }
});
