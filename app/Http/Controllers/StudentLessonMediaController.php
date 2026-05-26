<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentLessonMediaController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const DOWNLOADABLE_COLLECTIONS = ['video', 'learning-materials'];

    public function __invoke(Request $request, string $currentTeam, Lesson $lesson, Media $media): StreamedResponse
    {
        $user = $request->user();
        $team = $user?->currentTeam;

        abort_unless($user instanceof User && $team instanceof Team && $user->canViewStudentPortal($team), 403);
        abort_unless($this->mediaBelongsToLesson($lesson, $media), 404);
        abort_unless($this->studentHasLessonAccess($user, $team, $lesson), 403);

        return Storage::disk($media->disk)->download(
            $media->getPathRelativeToRoot(),
            $media->file_name,
            ['Content-Type' => $media->mime_type],
        );
    }

    private function mediaBelongsToLesson(Lesson $lesson, Media $media): bool
    {
        return $media->model_type === $lesson->getMorphClass()
            && (int) $media->model_id === $lesson->getKey()
            && in_array($media->collection_name, self::DOWNLOADABLE_COLLECTIONS, true);
    }

    private function studentHasLessonAccess(User $user, Team $team, Lesson $lesson): bool
    {
        return Enrollment::query()
            ->whereBelongsTo($team)
            ->where('payment_status', 'paid')
            ->whereIn('status', ['active', 'completed'])
            ->where(fn (Builder $query) => $query
                ->whereNull('access_starts_at')
                ->orWhere('access_starts_at', '<=', now()))
            ->where(fn (Builder $query) => $query
                ->whereNull('access_expires_at')
                ->orWhere('access_expires_at', '>=', now()))
            ->whereHas('studentProfile', fn (Builder $query) => $query
                ->whereBelongsTo($user)
                ->whereBelongsTo($team))
            ->whereHas('course.modules.lessons', fn (Builder $query) => $query->whereKey($lesson->getKey()))
            ->exists();
    }
}
