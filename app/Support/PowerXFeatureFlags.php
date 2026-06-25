<?php

namespace App\Support;

use Laravel\Pennant\Feature;

final class PowerXFeatureFlags
{
    public static function aiAssistant(): string
    {
        return (string) config('powerx_growth.ai_assistant.feature', 'powerx-ai-assistant');
    }

    public static function aiAssistantIsActive(): bool
    {
        return Feature::for(null)->active(self::aiAssistant());
    }

    public static function activateAiAssistant(): void
    {
        Feature::for(null)->activate(self::aiAssistant());
        Feature::activateForEveryone(self::aiAssistant());
        Feature::flushCache();
    }

    public static function deactivateAiAssistant(): void
    {
        Feature::for(null)->deactivate(self::aiAssistant());
        Feature::deactivateForEveryone(self::aiAssistant());
        Feature::flushCache();
    }

    public static function resetAiAssistant(): void
    {
        Feature::purge(self::aiAssistant());
        Feature::flushCache();
    }
}
