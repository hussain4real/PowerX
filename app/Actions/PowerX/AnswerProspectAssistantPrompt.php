<?php

namespace App\Actions\PowerX;

use App\Models\Company;
use App\Models\Course;
use App\Models\Lead;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AnswerProspectAssistantPrompt
{
    public function __construct(private BuildAssistantKnowledgeSource $buildAssistantKnowledgeSource) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function handle(array $data): array
    {
        $course = $this->requestedCourse($data);
        $knowledge = $this->buildAssistantKnowledgeSource->handle($course);
        $prompt = trim((string) $data['message']);
        $guardrailTriggered = $this->containsBlockedTopic($prompt, $knowledge);
        $suggestedCourse = $this->suggestCourse($data, $knowledge);
        $matchedFaq = $this->matchedFaq($prompt, $knowledge);
        $intent = $this->intent($prompt, $data);
        $answer = $guardrailTriggered
            ? (string) $knowledge['fallback']
            : $this->composeApprovedAnswer($knowledge, $suggestedCourse, $matchedFaq, $intent);
        $lead = $this->persistHandoffLead($data, $prompt, $answer, $knowledge, $suggestedCourse, $guardrailTriggered, $intent);

        return [
            'answer' => $answer,
            'disclaimer' => (string) $knowledge['disclaimer'],
            'guardrailTriggered' => $guardrailTriggered,
            'handoffCreated' => $lead !== null,
            'leadId' => $lead?->id,
            'suggestedCourse' => $suggestedCourse === null ? null : Arr::only($suggestedCourse, ['id', 'title', 'category', 'deliveryMode']),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function requestedCourse(array $data): ?Course
    {
        if (blank($data['course_id'] ?? null)) {
            return null;
        }

        return Course::query()->published()->find($data['course_id']);
    }

    /**
     * @param  array<string, mixed>  $knowledge
     */
    private function containsBlockedTopic(string $prompt, array $knowledge): bool
    {
        $prompt = str($prompt)->lower()->value();

        foreach ($knowledge['blockedTopics'] as $topic) {
            if (filled($topic) && str_contains($prompt, str((string) $topic)->lower()->value())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $knowledge
     * @return array<string, mixed>|null
     */
    private function suggestCourse(array $data, array $knowledge): ?array
    {
        $courses = collect($knowledge['courses']);

        if ($courses->isEmpty()) {
            return null;
        }

        $courseId = $data['course_id'] ?? $knowledge['focusCourseId'] ?? null;
        $selected = $courseId ? $courses->firstWhere('id', (int) $courseId) : null;

        if ($selected !== null) {
            return $selected;
        }

        $haystack = str(collect([$data['message'] ?? null, $data['course_interest'] ?? null])->filter()->implode(' '))->lower()->value();

        return $courses
            ->sortByDesc(fn (array $course): int => $this->courseScore($course, $haystack))
            ->first();
    }

    /**
     * @param  array<string, mixed>  $course
     */
    private function courseScore(array $course, string $haystack): int
    {
        $score = 0;

        foreach (['title', 'category', 'summary'] as $key) {
            $value = str((string) ($course[$key] ?? ''))->lower()->value();

            if (filled($value) && str_contains($haystack, $value)) {
                $score += $key === 'title' ? 4 : 2;
            }
        }

        foreach (preg_split('/\s+/', str((string) $course['title'])->lower()->value()) ?: [] as $word) {
            if (mb_strlen($word) > 3 && str_contains($haystack, $word)) {
                $score++;
            }
        }

        return $score;
    }

    /**
     * @param  array<string, mixed>  $knowledge
     * @return array<string, mixed>|null
     */
    private function matchedFaq(string $prompt, array $knowledge): ?array
    {
        $prompt = str($prompt)->lower()->value();

        foreach ($knowledge['faq'] as $faq) {
            $question = str((string) ($faq['question'] ?? ''))->lower()->value();
            $tags = collect($faq['tags'] ?? [])->map(fn (mixed $tag): string => str((string) $tag)->lower()->value());

            if (str_contains($prompt, $question) || $tags->contains(fn (string $tag): bool => filled($tag) && str_contains($prompt, $tag))) {
                return $faq;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{registration: bool, contact: bool, label: string}
     */
    private function intent(string $prompt, array $data): array
    {
        $prompt = str($prompt)->lower()->value();
        $registration = collect(['register', 'registration', 'enroll', 'quotation', 'quote', 'schedule', 'batch', 'call', 'whatsapp', 'payment', 'course fee'])
            ->contains(fn (string $keyword): bool => str_contains($prompt, $keyword));
        $contact = filled($data['email'] ?? null) || filled($data['phone'] ?? null);

        return [
            'registration' => $registration,
            'contact' => $contact,
            'label' => $registration ? 'registration_help' : 'course_guidance',
        ];
    }

    /**
     * @param  array<string, mixed>  $knowledge
     * @param  array<string, mixed>|null  $course
     * @param  array<string, mixed>|null  $faq
     * @param  array{registration: bool, contact: bool, label: string}  $intent
     */
    private function composeApprovedAnswer(array $knowledge, ?array $course, ?array $faq, array $intent): string
    {
        $parts = [];

        if ($faq !== null) {
            $parts[] = (string) $faq['answer'];
        }

        if ($course !== null) {
            $packageSummary = collect($course['packages'])
                ->take(2)
                ->map(fn (array $package): string => "{$package['name']} ({$package['currency']} ".number_format((float) $package['price'], 2).')')
                ->implode(', ');
            $price = "{$course['currency']} ".number_format((float) $course['basePrice'], 2);
            $parts[] = "{$course['title']} is a {$course['deliveryMode']} {$course['category']} course. {$course['summary']} Base pricing starts from {$price}.".($packageSummary === '' ? '' : " Active packages include {$packageSummary}.");

            $nextBatch = collect($course['scheduleAvailability'])->first();

            if ($nextBatch !== null) {
                $parts[] = "The next scheduled batch is {$nextBatch['name']} starting {$nextBatch['startsAt']}.";
            }
        }

        if ($parts === []) {
            $parts[] = 'PowerX can share approved course information once the course catalog is configured.';
        }

        if ($intent['registration']) {
            $parts[] = 'For registration help, PowerX staff can follow up when you share a name plus email or phone.';
        }

        return implode(' ', $parts);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $knowledge
     * @param  array<string, mixed>|null  $suggestedCourse
     * @param  array{registration: bool, contact: bool, label: string}  $intent
     */
    private function persistHandoffLead(
        array $data,
        string $prompt,
        string $answer,
        array $knowledge,
        ?array $suggestedCourse,
        bool $guardrailTriggered,
        array $intent,
    ): ?Lead {
        if (! $intent['contact']) {
            return null;
        }

        $course = $suggestedCourse === null ? null : Course::query()->find($suggestedCourse['id']);
        $attribution = $this->attributionMetadata($data);
        $referral = $this->referralMetadata($data);
        $source = $data['source'] ?? $attribution['utm_source'] ?? ($referral === [] ? 'ai_chat' : 'referral');
        $campaign = $data['campaign'] ?? $attribution['utm_campaign'] ?? null;
        $followUpAt = now()->addHours((int) config('powerx_growth.ai_assistant.handoff_follow_up_hours', 4));
        $summary = $this->handoffSummary($data, $prompt, $suggestedCourse, $guardrailTriggered, $intent);

        return DB::transaction(function () use ($data, $prompt, $answer, $knowledge, $suggestedCourse, $guardrailTriggered, $intent, $course, $attribution, $referral, $source, $campaign, $followUpAt, $summary): Lead {
            $company = $this->companyFromAssistant($data, $course?->team_id ?? $suggestedCourse['teamId'] ?? null);
            $lead = $this->matchingLead($data, $course);
            $metadata = [
                'company_name' => $data['company_name'] ?? null,
                'channel' => 'ai_assistant',
                'channel_group' => $this->channelGroup($source, $attribution, $referral),
                'attribution' => $attribution,
                'referral' => $referral,
                'ai_assistant' => [
                    'scope' => $knowledge['scope'],
                    'last_prompt' => $prompt,
                    'last_answer' => $answer,
                    'handoff_summary' => $summary,
                    'transcript_summary' => $summary,
                    'preferred_course_id' => $suggestedCourse['id'] ?? null,
                    'preferred_course_title' => $suggestedCourse['title'] ?? null,
                    'guardrail_triggered' => $guardrailTriggered,
                    'intent' => $intent['label'],
                    'next_action' => 'Sales/support follow-up',
                ],
            ];

            if ($lead === null) {
                return Lead::query()->create([
                    'team_id' => $course?->team_id ?? $suggestedCourse['teamId'] ?? null,
                    'company_id' => $company?->id,
                    'course_id' => $course?->id,
                    'name' => $data['name'] ?? 'AI assistant prospect',
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'source' => $source,
                    'campaign' => $campaign,
                    'status' => $intent['registration'] || $guardrailTriggered ? Lead::STATUS_QUALIFIED : Lead::STATUS_NEW,
                    'course_interest' => $suggestedCourse['title'] ?? ($data['course_interest'] ?? null),
                    'notes' => $prompt,
                    'follow_up_at' => $followUpAt,
                    'metadata' => $metadata,
                ]);
            }

            $lead->forceFill([
                'company_id' => $lead->company_id ?? $company?->id,
                'course_id' => $lead->course_id ?? $course?->id,
                'source' => $lead->source ?? $source,
                'campaign' => $lead->campaign ?? $campaign,
                'status' => $intent['registration'] || $guardrailTriggered ? Lead::STATUS_QUALIFIED : $lead->status,
                'course_interest' => $lead->course_interest ?? ($suggestedCourse['title'] ?? ($data['course_interest'] ?? null)),
                'notes' => trim(collect([$lead->notes, $prompt])->filter()->implode("\n\n")),
                'follow_up_at' => $followUpAt,
                'metadata' => array_replace_recursive($lead->metadata ?? [], $metadata),
            ])->save();

            return $lead;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function matchingLead(array $data, ?Course $course): ?Lead
    {
        return Lead::query()
            ->when($course !== null, fn ($query) => $query->whereBelongsTo($course))
            ->where(function ($query) use ($data): void {
                $query
                    ->when(filled($data['email'] ?? null), fn ($query) => $query->orWhere('email', $data['email']))
                    ->when(filled($data['phone'] ?? null), fn ($query) => $query->orWhere('phone', $data['phone']));
            })
            ->latest()
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function companyFromAssistant(array $data, ?int $teamId): ?Company
    {
        if (blank($data['company_name'] ?? null)) {
            return null;
        }

        return Company::query()->firstOrCreate(
            [
                'team_id' => $teamId,
                'name' => $data['company_name'],
            ],
            [
                'contact_name' => $data['name'] ?? 'AI assistant prospect',
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>|null  $suggestedCourse
     * @param  array{registration: bool, contact: bool, label: string}  $intent
     */
    private function handoffSummary(array $data, string $prompt, ?array $suggestedCourse, bool $guardrailTriggered, array $intent): string
    {
        $course = $suggestedCourse['title'] ?? ($data['course_interest'] ?? 'not selected');
        $contact = collect([$data['email'] ?? null, $data['phone'] ?? null])->filter()->implode(' / ');
        $guardrail = $guardrailTriggered ? ' Guardrail fallback was used.' : '';

        return "Prospect asked: {$prompt} Preferred course: {$course}. Intent: {$intent['label']}. Contact: {$contact}.{$guardrail}";
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributionMetadata(array $data): array
    {
        return array_filter(Arr::only($data, [
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_content',
            'utm_term',
        ]), fn (mixed $value): bool => filled($value));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function referralMetadata(array $data): array
    {
        return array_filter([
            'name' => $data['referral_name'] ?? null,
            'phone' => $data['referral_phone'] ?? null,
            'email' => $data['referral_email'] ?? null,
            'relationship' => $data['referral_relationship'] ?? null,
        ], fn (mixed $value): bool => filled($value));
    }

    /**
     * @param  array<string, mixed>  $attribution
     * @param  array<string, mixed>  $referral
     */
    private function channelGroup(?string $source, array $attribution, array $referral): string
    {
        $source = str((string) ($source ?? data_get($attribution, 'utm_source')))->lower()->value();
        $medium = str((string) data_get($attribution, 'utm_medium'))->lower()->value();

        if ($referral !== [] || $source === 'referral') {
            return 'Referral';
        }

        if ($source === 'ai_chat') {
            return 'AI assistant';
        }

        if (in_array($source, ['instagram', 'linkedin', 'youtube', 'google', 'meta', 'facebook', 'paid_ads'], true) || in_array($medium, ['cpc', 'paid', 'paid-social'], true)) {
            return 'Paid / social';
        }

        if (in_array($source, ['whatsapp', 'phone', 'walk-in'], true)) {
            return 'Direct';
        }

        return 'Website';
    }
}
