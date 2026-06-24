<?php

namespace App\Actions\PowerX;

use App\Models\Course;
use App\Models\FreePreviewEvent;
use App\Models\Lead;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordFreePreviewEvent
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Course $course, array $data, ?User $user = null, ?Request $request = null): FreePreviewEvent
    {
        $request ??= request();
        $lesson = $this->previewLesson($course, $data['lesson_id'] ?? null);
        $lead = $this->matchedLead($course, $data, $request, $user);
        $attribution = $this->attributionMetadata($data);

        return DB::transaction(function () use ($course, $data, $user, $request, $lesson, $lead, $attribution): FreePreviewEvent {
            return FreePreviewEvent::query()->create([
                'team_id' => $course->team_id,
                'lead_id' => $lead?->id,
                'user_id' => $user?->id,
                'course_id' => $course->id,
                'lesson_id' => $lesson?->id,
                'event_type' => $data['event_type'],
                'source' => $data['source'] ?? $attribution['utm_source'] ?? 'course_detail',
                'campaign' => $data['campaign'] ?? $attribution['utm_campaign'] ?? null,
                'session_id' => $request->session()->getId(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'occurred_at' => now(),
                'metadata' => [
                    'attribution' => $attribution,
                    'contact' => Arr::only($data, ['name', 'email', 'phone']),
                    'matched_by' => $lead ? 'session_or_contact' : null,
                ],
            ]);
        });
    }

    private function previewLesson(Course $course, mixed $lessonId): ?Lesson
    {
        if (blank($lessonId)) {
            return null;
        }

        $lesson = Lesson::query()
            ->whereKey($lessonId)
            ->where('is_preview', true)
            ->whereHas('courseModule', fn ($query) => $query->whereBelongsTo($course))
            ->first();

        if ($lesson === null) {
            throw ValidationException::withMessages([
                'lesson_id' => __('Choose a valid preview lesson for this course.'),
            ]);
        }

        return $lesson;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function matchedLead(Course $course, array $data, Request $request, ?User $user): ?Lead
    {
        $sessionLeadId = $request->session()->get('powerx.lead_id');

        if ($sessionLeadId) {
            $lead = Lead::query()
                ->whereBelongsTo($course)
                ->whereKey($sessionLeadId)
                ->first();

            if ($lead) {
                return $lead;
            }
        }

        $email = $data['email'] ?? $user?->email;
        $phone = $data['phone'] ?? null;

        if (blank($email) && blank($phone)) {
            return null;
        }

        return Lead::query()
            ->whereBelongsTo($course)
            ->where(function ($query) use ($email, $phone): void {
                $query
                    ->when($email, fn ($query, string $email) => $query->orWhere('email', $email))
                    ->when($phone, fn ($query, string $phone) => $query->orWhere('phone', $phone));
            })
            ->latest()
            ->first();
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
}
