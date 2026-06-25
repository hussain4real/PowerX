<?php

namespace App\Filament\Pages;

use App\Enums\PowerXPermission;
use App\Support\PowerXFeatureFlags;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Laravel\Pennant\Feature;
use UnitEnum;

class FeatureFlags extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Feature flags';

    protected string $view = 'filament.pages.feature-flags';

    public static function canAccess(): bool
    {
        return Auth::user()?->can(PowerXPermission::ManageSettings->value) ?? false;
    }

    /**
     * @return array<int, array{
     *     title: string,
     *     description: string,
     *     key: string,
     *     status: string,
     *     statusColor: string,
     *     configDefault: string,
     *     store: string,
     *     scope: string,
     *     endpoint: string
     * }>
     */
    public function featureRows(): array
    {
        $enabled = PowerXFeatureFlags::aiAssistantIsActive();

        return [[
            'title' => 'AI prospect assistant',
            'description' => 'Controls the public assistant shown on the website and course catalog.',
            'key' => PowerXFeatureFlags::aiAssistant(),
            'status' => $enabled ? 'Enabled' : 'Disabled',
            'statusColor' => $enabled ? 'success' : 'danger',
            'configDefault' => ((bool) config('powerx_growth.ai_assistant.enabled', false)) ? 'Enabled' : 'Disabled',
            'store' => (string) config('pennant.default', 'database'),
            'scope' => Feature::serializeScope(null),
            'endpoint' => route('powerx-assistant.store'),
        ]];
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('enableAiAssistant')
                ->label('Enable AI assistant')
                ->icon(Heroicon::OutlinedBolt)
                ->color('success')
                ->authorize(fn (): bool => static::canAccess())
                ->requiresConfirmation()
                ->modalHeading('Enable AI assistant')
                ->action(function (): void {
                    $this->enableAiAssistant();
                }),
            Action::make('disableAiAssistant')
                ->label('Disable AI assistant')
                ->icon(Heroicon::OutlinedNoSymbol)
                ->color('danger')
                ->authorize(fn (): bool => static::canAccess())
                ->requiresConfirmation()
                ->modalHeading('Disable AI assistant')
                ->action(function (): void {
                    $this->disableAiAssistant();
                }),
            Action::make('resetAiAssistant')
                ->label('Use config default')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->authorize(fn (): bool => static::canAccess())
                ->requiresConfirmation()
                ->modalHeading('Use configured default')
                ->action(function (): void {
                    $this->resetAiAssistant();
                }),
        ];
    }

    public function enableAiAssistant(): void
    {
        PowerXFeatureFlags::activateAiAssistant();
        $this->sendStatusNotification('AI assistant enabled.');
    }

    public function disableAiAssistant(): void
    {
        PowerXFeatureFlags::deactivateAiAssistant();
        $this->sendStatusNotification('AI assistant disabled.');
    }

    public function resetAiAssistant(): void
    {
        PowerXFeatureFlags::resetAiAssistant();
        $this->sendStatusNotification('AI assistant is using the configured default.');
    }

    private function sendStatusNotification(string $title): void
    {
        Notification::make()
            ->title($title)
            ->success()
            ->send();
    }
}
