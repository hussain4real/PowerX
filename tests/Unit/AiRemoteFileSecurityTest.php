<?php

use Illuminate\Support\Facades\Http;
use Laravel\Ai\Files\UntrustedUrl;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function (): void {
    UntrustedUrl::resolveUsing(null);
});

test('AI remote files reject internal addresses before requesting them', function (string $url): void {
    Http::fake();
    UntrustedUrl::resolveUsing(fn (string $host): array => ['127.0.0.1']);

    expect(fn () => UntrustedUrl::fetch($url))->toThrow(InvalidArgumentException::class);
    Http::assertNothingSent();
})->with([
    'http://127.0.0.1/private',
    'http://[::1]/private',
    'http://169.254.169.254/latest/meta-data',
    'https://public-name.example/private',
]);

test('AI remote files validate redirect targets while allowing public files', function (): void {
    UntrustedUrl::resolveUsing(fn (string $host): array => ['93.184.216.34']);
    Http::fake([
        'https://public.example/image.png' => Http::response('image', 200),
        'https://public.example/redirect' => Http::response('', 302, ['Location' => 'http://127.0.0.1/private']),
    ]);

    expect(UntrustedUrl::fetch('https://public.example/image.png')->body())->toBe('image');
    expect(fn () => UntrustedUrl::fetch('https://public.example/redirect'))->toThrow(InvalidArgumentException::class);
    Http::assertSentCount(2);
});
