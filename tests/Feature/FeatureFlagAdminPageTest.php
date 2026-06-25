<?php

use App\Enums\PowerXRole;
use App\Filament\Pages\FeatureFlags;
use App\Models\User;
use App\Support\PowerXFeatureFlags;
use Database\Seeders\PowerXAccessSeeder;
use Laravel\Pennant\Feature;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function (): void {
    $this->seed(PowerXAccessSeeder::class);
});

test('management and admin users can open the feature flag page', function (PowerXRole $role): void {
    $user = grantPowerXRole(User::factory()->create(), $role);

    $this->actingAs($user);

    expect(FeatureFlags::canAccess())->toBeTrue();

    $this
        ->get(FeatureFlags::getUrl())
        ->assertOk()
        ->assertSee('Feature flags')
        ->assertSee('AI prospect assistant')
        ->assertSee(PowerXFeatureFlags::aiAssistant());
})->with([
    'management' => PowerXRole::Management,
    'admin' => PowerXRole::Admin,
]);

test('admin panel users without settings permission cannot open feature flags', function (): void {
    $salesUser = grantPowerXRole(User::factory()->create(), PowerXRole::Sales);

    $this->actingAs($salesUser);

    expect(FeatureFlags::canAccess())->toBeFalse();

    $this
        ->get(FeatureFlags::getUrl())
        ->assertForbidden();
});

test('settings managers can control the assistant flag through the filament page', function (): void {
    config(['powerx_growth.ai_assistant.enabled' => false]);

    $manager = grantPowerXRole(User::factory()->create(), PowerXRole::Management);
    $this->actingAs($manager);

    PowerXFeatureFlags::resetAiAssistant();

    expect(PowerXFeatureFlags::aiAssistant())->toBe('powerx-ai-assistant')
        ->and(PowerXFeatureFlags::aiAssistantIsActive())->toBeFalse();

    Livewire::test(FeatureFlags::class)
        ->assertOk()
        ->assertSee('AI prospect assistant')
        ->assertSee(PowerXFeatureFlags::aiAssistant())
        ->assertSee(Feature::serializeScope(null))
        ->callAction('enableAiAssistant')
        ->assertNotified('AI assistant enabled.');

    Feature::flushCache();

    expect(PowerXFeatureFlags::aiAssistantIsActive())->toBeTrue();

    assertDatabaseHas('features', [
        'name' => PowerXFeatureFlags::aiAssistant(),
        'scope' => Feature::serializeScope(null),
        'value' => 'true',
    ]);

    Livewire::test(FeatureFlags::class)
        ->callAction('disableAiAssistant')
        ->assertNotified('AI assistant disabled.');

    Feature::flushCache();

    expect(PowerXFeatureFlags::aiAssistantIsActive())->toBeFalse();

    assertDatabaseHas('features', [
        'name' => PowerXFeatureFlags::aiAssistant(),
        'scope' => Feature::serializeScope(null),
        'value' => 'false',
    ]);

    config(['powerx_growth.ai_assistant.enabled' => true]);

    Livewire::test(FeatureFlags::class)
        ->callAction('resetAiAssistant')
        ->assertNotified('AI assistant is using the configured default.');

    Feature::flushCache();

    expect(PowerXFeatureFlags::aiAssistantIsActive())->toBeTrue();

    assertDatabaseHas('features', [
        'name' => PowerXFeatureFlags::aiAssistant(),
        'scope' => Feature::serializeScope(null),
        'value' => 'true',
    ]);
});
