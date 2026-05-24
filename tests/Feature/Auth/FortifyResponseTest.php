<?php

use App\Http\Responses\LoginResponse;
use App\Http\Responses\RegisterResponse;
use App\Http\Responses\TwoFactorLoginResponse;
use App\Http\Responses\VerifyEmailResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;

function requestForFortifyResponse(User $user, bool $wantsJson = false): Request
{
    $request = Request::create('/login', 'POST', server: $wantsJson ? [
        'HTTP_ACCEPT' => 'application/json',
    ] : []);

    $request->setLaravelSession(app('session.store'));
    $request->setUserResolver(fn () => $user);

    return $request;
}

test('fortify authentication responses can return json payloads', function () {
    $user = User::factory()->create();

    $loginResponse = (new LoginResponse)->toResponse(requestForFortifyResponse($user, wantsJson: true));
    $registerResponse = (new RegisterResponse)->toResponse(requestForFortifyResponse($user, wantsJson: true));
    $twoFactorResponse = (new TwoFactorLoginResponse)->toResponse(requestForFortifyResponse($user, wantsJson: true));
    $verifyEmailResponse = (new VerifyEmailResponse)->toResponse(requestForFortifyResponse($user, wantsJson: true));

    expect($loginResponse->getStatusCode())->toBe(200)
        ->and($loginResponse->getData(true))->toBe(['two_factor' => false])
        ->and($registerResponse->getStatusCode())->toBe(201)
        ->and($registerResponse->getData(true))->toBe(['two_factor' => false])
        ->and($twoFactorResponse->getStatusCode())->toBe(200)
        ->and($twoFactorResponse->getData(true))->toBe(['two_factor' => false])
        ->and($verifyEmailResponse->getStatusCode())->toBe(204);
});

test('two factor login response redirects to the current team dashboard', function () {
    $user = User::factory()->create();
    $team = $user->personalTeam();

    $response = (new TwoFactorLoginResponse)->toResponse(requestForFortifyResponse($user));

    expect($response->getTargetUrl())->toBe(route('dashboard', ['current_team' => $team->slug]));
});

test('fortify team redirects are forbidden when the user has no teams', function () {
    $user = User::create([
        'name' => 'No Team User',
        'email' => 'no-team@example.com',
        'password' => Hash::make('password'),
    ]);

    expect(fn () => (new LoginResponse)->toResponse(requestForFortifyResponse($user)))
        ->toThrow(HttpException::class);
});
