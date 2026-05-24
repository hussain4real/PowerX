<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

test('two factor login attempts are rate limited by login id', function () {
    $request = Request::create('/two-factor-challenge', 'POST');
    $request->setLaravelSession(app('session.store'));
    $request->session()->put('login.id', 123);

    $limit = RateLimiter::limiter('two-factor')($request);

    expect($limit->maxAttempts)->toBe(5)
        ->and($limit->decaySeconds)->toBe(60)
        ->and($limit->key)->toBe(123);
});

test('passkey login attempts are rate limited by credential id when present', function () {
    $request = Request::create('/passkeys/login', 'POST', [
        'credential' => ['id' => 'credential-id'],
    ], server: [
        'REMOTE_ADDR' => '10.0.0.1',
    ]);
    $request->setLaravelSession(app('session.store'));

    $limit = RateLimiter::limiter('passkeys')($request);

    expect($limit->maxAttempts)->toBe(10)
        ->and($limit->decaySeconds)->toBe(60)
        ->and($limit->key)->toBe('credential-id|10.0.0.1');
});

test('passkey login attempts fall back to the session id without a credential id', function () {
    $request = Request::create('/passkeys/login', 'POST', server: [
        'REMOTE_ADDR' => '10.0.0.2',
    ]);
    $request->setLaravelSession(app('session.store'));

    $limit = RateLimiter::limiter('passkeys')($request);

    expect($limit->maxAttempts)->toBe(10)
        ->and($limit->decaySeconds)->toBe(60)
        ->and($limit->key)->toBe($request->session()->getId().'|10.0.0.2');
});
