<?php

namespace App\Http\Controllers;

use App\Actions\PowerX\RecordFreePreviewEvent;
use App\Actions\PowerX\ResolveStudentLessonAccess;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentLessonMediaController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const DOWNLOADABLE_COLLECTIONS = ['video', 'learning-materials'];

    public function __invoke(
        Request $request,
        string $currentTeam,
        Enrollment $enrollment,
        Lesson $lesson,
        Media $media,
        ResolveStudentLessonAccess $resolveStudentLessonAccess,
        RecordFreePreviewEvent $recordFreePreviewEvent,
    ): StreamedResponse|BinaryFileResponse {
        $user = $request->user();
        $team = $user?->currentTeam;

        abort_unless($user instanceof User && $team instanceof Team && $user->canViewStudentPortal($team), 403);
        abort_unless($this->mediaBelongsToLesson($lesson, $media), 404);
        abort_unless($resolveStudentLessonAccess->canViewLesson($user, $team, $enrollment, $lesson), 403);

        $hasPaidAccess = $resolveStudentLessonAccess->hasPaidAccess($enrollment);
        $disposition = $request->string('disposition')->toString() === 'download' ? 'attachment' : 'inline';

        if (! $hasPaidAccess && $resolveStudentLessonAccess->hasPreviewAccess($enrollment, $lesson)) {
            $recordFreePreviewEvent->handle($enrollment->course, [
                'event_type' => 'started',
                'lesson_id' => $lesson->id,
                'source' => $request->string('source')->toString() ?: 'student_preview_media',
                'campaign' => $enrollment->metadata['campaign'] ?? $enrollment->metadata['lead_campaign'] ?? null,
            ], $user, $request);
        }

        return Storage::disk($media->disk)->response(
            $media->getPathRelativeToRoot(),
            $media->file_name,
            ['Content-Type' => $media->mime_type],
            $disposition,
        );
    }

    private function mediaBelongsToLesson(Lesson $lesson, Media $media): bool
    {
        return $media->model_type === $lesson->getMorphClass()
            && (int) $media->model_id === $lesson->getKey()
            && in_array($media->collection_name, self::DOWNLOADABLE_COLLECTIONS, true);
    }
}
