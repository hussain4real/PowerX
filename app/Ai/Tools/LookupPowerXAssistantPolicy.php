<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class LookupPowerXAssistantPolicy implements Tool
{
    /**
     * Get the tool name exposed to AI providers.
     */
    public function name(): string
    {
        return 'lookup_powerx_assistant_policy';
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Look up approved PowerX AI assistant policy, blocked topics, fallback copy, disclaimers, and FAQ answers.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $topic = str($request->string('topic')->toString())->lower()->value();
        $blockedTopics = collect(config('powerx_growth.ai_assistant.blocked_topics', []))
            ->map(fn (mixed $blockedTopic): string => (string) $blockedTopic)
            ->filter()
            ->values();
        $faq = collect(config('powerx_growth.ai_assistant.faq', []))
            ->map(fn (array $item): array => [
                'question' => (string) ($item['question'] ?? ''),
                'answer' => (string) ($item['answer'] ?? ''),
                'tags' => array_values($item['tags'] ?? []),
            ]);

        return json_encode([
            'source' => 'powerx_approved_configuration',
            'scope' => config('powerx_growth.ai_assistant.scope'),
            'fallback' => config('powerx_growth.ai_assistant.approved_fallback'),
            'disclaimer' => config('powerx_growth.ai_assistant.disclaimer'),
            'blockedTopics' => $blockedTopics->all(),
            'matchingBlockedTopics' => $blockedTopics
                ->filter(fn (string $blockedTopic): bool => $topic !== '' && str_contains($topic, str($blockedTopic)->lower()->value()))
                ->values()
                ->all(),
            'faqMatches' => $faq
                ->filter(fn (array $item): bool => $topic !== '' && (
                    str_contains($topic, str((string) $item['question'])->lower()->value())
                    || collect($item['tags'])->contains(fn (mixed $tag): bool => str_contains($topic, str((string) $tag)->lower()->value()))
                ))
                ->values()
                ->all(),
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'topic' => $schema->string()->description('Optional prospect question or topic to match against approved policies and FAQ tags.'),
        ];
    }
}
