<?php

namespace App\Actions\PowerX;

use Illuminate\Support\Str;
use InvalidArgumentException;

class RenderCommunicationTemplate
{
    /**
     * @param  array<string, mixed>  $context
     * @return array{key: string, subject: string, message: string, whatsappText: string, whatsappUrl: string|null}
     */
    public function handle(string $templateKey, array $context = []): array
    {
        $template = config("powerx_notifications.templates.{$templateKey}");

        if (! is_array($template)) {
            throw new InvalidArgumentException("Unknown communication template [{$templateKey}].");
        }

        $whatsappText = $this->renderString($template['whatsapp'] ?? $template['message'], $context);
        $phone = $this->normalizePhone($context['recipient_phone'] ?? null);

        return [
            'key' => $templateKey,
            'subject' => $this->renderString($template['subject'] ?? '', $context),
            'message' => $this->renderString($template['message'], $context),
            'whatsappText' => $whatsappText,
            'whatsappUrl' => $phone ? 'https://wa.me/'.$phone.'?text='.rawurlencode($whatsappText) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function renderString(string $template, array $context): string
    {
        return Str::replaceMatches('/{{\s*([\w.]+)\s*}}/', function (array $matches) use ($context): string {
            $value = data_get($context, $matches[1]);

            if ($value === null) {
                throw new InvalidArgumentException("Missing communication template value [{$matches[1]}].");
            }

            return (string) $value;
        }, $template);
    }

    private function normalizePhone(mixed $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $digits = Str::of((string) $phone)->replaceMatches('/\D+/', '')->toString();

        return $digits !== '' ? $digits : null;
    }
}
