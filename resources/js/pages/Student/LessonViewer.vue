<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    CheckCircle2,
    Download,
    FileText,
    PlayCircle,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import VideoJsLessonPlayer from '@/components/powerx/VideoJsLessonPlayer.vue';
import { portal as studentPortal } from '@/routes/student';
import { index as studentCoursesIndex } from '@/routes/student/courses';
import { update as updateLessonProgressRoute } from '@/routes/student/lesson-progress';
import type {
    Lesson,
    LessonMedia,
    StudentLessonViewerProps,
    Team,
} from '@/types';

const props = defineProps<StudentLessonViewerProps>();
const page = usePage();
const progressRequests = ref<Record<string, boolean>>({});
const sentMediaEvents = ref<Record<string, boolean>>({});
const lastVideoProgressSent = ref<Record<number, number>>({});

type VideoPlaybackProgress = {
    currentTime: number;
    duration: number;
};

defineOptions({
    layout: (props: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Student portal',
                href: props.currentTeam
                    ? studentPortal(props.currentTeam.slug).url
                    : '/',
            },
            {
                title: 'My courses',
                href: props.currentTeam
                    ? studentCoursesIndex(props.currentTeam.slug).url
                    : '/',
            },
            {
                title: 'Lesson viewer',
                href: '#',
            },
        ],
    }),
});

const currentTeamSlug = computed(() => page.props.currentTeam?.slug);
const videoMedia = computed(() =>
    props.lesson.media.filter((media) => media.mediaType === 'video'),
);
const pdfMedia = computed(() =>
    props.lesson.media.filter((media) => media.mediaType === 'pdf'),
);
const downloadMedia = computed(() =>
    props.lesson.media.filter((media) => media.mediaType === 'download'),
);

const progressKey = (event: string, media?: LessonMedia): string =>
    `${props.enrollment.id}-${props.lesson.id}-${event}-${media?.id ?? 'lesson'}`;

const recordProgress = (
    progressPercentage: number,
    event: string,
    media?: LessonMedia,
    lastPositionSeconds = props.lesson.lastPositionSeconds,
): void => {
    if (!props.lesson.canUpdateProgress || !currentTeamSlug.value) {
        return;
    }

    const key = progressKey(event, media);
    progressRequests.value = {
        ...progressRequests.value,
        [key]: true,
    };

    router.patch(
        updateLessonProgressRoute.url({
            current_team: currentTeamSlug.value,
            enrollment: props.enrollment.id,
            lesson: props.lesson.id,
        }),
        {
            progress_percentage: progressPercentage,
            last_position_seconds: lastPositionSeconds,
            event,
            media_id: media?.id,
        },
        {
            only: [
                'summary',
                'enrollment',
                'lesson',
                'previousLesson',
                'nextLesson',
            ],
            preserveScroll: true,
            onFinish: () => {
                progressRequests.value = {
                    ...progressRequests.value,
                    [key]: false,
                };
            },
        },
    );
};

const recordOnce = (
    event: string,
    progressPercentage: number,
    media?: LessonMedia,
): void => {
    const key = progressKey(event, media);

    if (sentMediaEvents.value[key]) {
        return;
    }

    sentMediaEvents.value = {
        ...sentMediaEvents.value,
        [key]: true,
    };

    recordProgress(progressPercentage, event, media);
};

const recordVideoStarted = (media: LessonMedia): void => {
    recordOnce(
        'video_started',
        Math.max(props.lesson.progressPercentage, 1),
        media,
    );
};

const recordVideoProgress = (
    media: LessonMedia,
    playback: VideoPlaybackProgress,
): void => {
    if (
        !Number.isFinite(playback.currentTime) ||
        !Number.isFinite(playback.duration) ||
        playback.duration <= 0
    ) {
        return;
    }

    const currentSecond = Math.floor(playback.currentTime);
    const lastSent = lastVideoProgressSent.value[media.id] ?? 0;

    if (
        currentSecond - lastSent < 15 &&
        currentSecond < playback.duration - 2
    ) {
        return;
    }

    lastVideoProgressSent.value = {
        ...lastVideoProgressSent.value,
        [media.id]: currentSecond,
    };

    recordProgress(
        Math.min(
            99,
            Math.max(
                props.lesson.progressPercentage,
                Math.round((playback.currentTime / playback.duration) * 100),
            ),
        ),
        'video_progress',
        media,
        currentSecond,
    );
};

const recordVideoCompleted = (
    media: LessonMedia,
    playback: VideoPlaybackProgress,
): void => {
    recordProgress(
        100,
        'video_completed',
        media,
        Math.floor(
            Number.isFinite(playback.currentTime) ? playback.currentTime : 0,
        ),
    );
};

const recordPdfOpened = (media: LessonMedia): void => {
    recordOnce(
        'pdf_opened',
        Math.max(props.lesson.progressPercentage, 25),
        media,
    );
};

const recordDownload = (media: LessonMedia): void => {
    recordProgress(
        Math.max(
            props.lesson.progressPercentage,
            media.mediaType === 'pdf' ? 25 : 1,
        ),
        media.mediaType === 'pdf' ? 'pdf_downloaded' : 'media_downloaded',
        media,
    );
};

const markCompleted = (): void => {
    recordProgress(100, 'lesson_completed');
};

const lessonStatusLabel = (lesson: Lesson): string => {
    if (lesson.isCompleted) {
        return 'Completed';
    }

    return `${lesson.progressPercentage}% complete`;
};
</script>

<template>
    <Head :title="lesson.title" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
        <section
            class="rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm dark:border-sidebar-border"
        >
            <div class="flex flex-col justify-between gap-4 lg:flex-row">
                <div>
                    <Link
                        :href="studentCoursesIndex(currentTeamSlug ?? '').url"
                        class="inline-flex items-center gap-2 text-sm font-black text-powerx-yellow"
                    >
                        <ArrowLeft class="size-4" />
                        My courses
                    </Link>
                    <p
                        class="mt-4 text-xs font-black tracking-[0.2em] text-powerx-yellow uppercase"
                    >
                        {{ enrollment.course.title }}
                    </p>
                    <h1 class="mt-2 text-3xl font-black">
                        {{ lesson.title }}
                    </h1>
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{ lessonStatusLabel(lesson) }}
                    </p>
                </div>
                <div class="flex flex-wrap items-start gap-2">
                    <Link
                        v-if="previousLesson?.viewerUrl"
                        :href="previousLesson.viewerUrl"
                        class="inline-flex items-center gap-2 rounded-full border border-border px-4 py-2 text-sm font-black transition hover:border-powerx-yellow hover:text-powerx-yellow"
                    >
                        <ArrowLeft class="size-4" />
                        Previous
                    </Link>
                    <Link
                        v-if="nextLesson?.viewerUrl"
                        :href="nextLesson.viewerUrl"
                        class="inline-flex items-center gap-2 rounded-full bg-powerx-yellow px-4 py-2 text-sm font-black text-powerx-navy transition hover:bg-powerx-yellow/90"
                    >
                        Next
                        <ArrowRight class="size-4" />
                    </Link>
                </div>
            </div>
        </section>

        <section
            class="grid gap-6 rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm xl:grid-cols-[1fr_20rem] dark:border-sidebar-border"
        >
            <div class="space-y-5">
                <div v-if="videoMedia.length > 0" class="grid gap-4">
                    <VideoJsLessonPlayer
                        v-for="media in videoMedia"
                        :key="media.id"
                        :media="media"
                        @started="recordVideoStarted"
                        @progress="recordVideoProgress"
                        @completed="recordVideoCompleted"
                    />
                </div>

                <div v-if="pdfMedia.length > 0" class="grid gap-4">
                    <div
                        v-for="media in pdfMedia"
                        :key="media.id"
                        class="overflow-hidden rounded-2xl border border-border"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-3 border-b border-border bg-muted/40 p-3"
                        >
                            <div
                                class="flex items-center gap-2 text-sm font-black"
                            >
                                <FileText class="size-4 text-powerx-yellow" />
                                {{ media.fileName }}
                            </div>
                            <a
                                :href="media.downloadUrl"
                                class="inline-flex items-center gap-2 rounded-full border border-border bg-background px-3 py-1.5 text-xs font-black transition hover:border-powerx-yellow hover:text-powerx-yellow"
                                @click="recordDownload(media)"
                            >
                                <Download class="size-3.5" />
                                Download
                            </a>
                        </div>
                        <iframe
                            class="h-[70vh] min-h-96 w-full bg-background"
                            :src="media.inlineUrl"
                            :title="media.fileName"
                            @load="recordPdfOpened(media)"
                        />
                    </div>
                </div>

                <div
                    v-if="videoMedia.length === 0 && pdfMedia.length === 0"
                    class="rounded-2xl border border-border bg-muted/40 p-5"
                >
                    <p class="text-sm font-black">Lesson notes</p>
                    <p
                        v-if="lesson.content"
                        class="mt-3 text-sm leading-6 whitespace-pre-line text-muted-foreground"
                    >
                        {{ lesson.content }}
                    </p>
                    <p v-else class="mt-3 text-sm text-muted-foreground">
                        No embedded media has been uploaded for this lesson yet.
                    </p>
                </div>

                <div
                    v-if="downloadMedia.length > 0"
                    class="flex flex-wrap gap-2"
                >
                    <a
                        v-for="media in downloadMedia"
                        :key="media.id"
                        :href="media.downloadUrl"
                        class="inline-flex items-center gap-2 rounded-full border border-border bg-background px-3 py-1.5 text-xs font-black transition hover:border-powerx-yellow hover:text-powerx-yellow"
                        @click="recordDownload(media)"
                    >
                        <Download class="size-3.5" />
                        {{ media.fileName }} · {{ media.humanReadableSize }}
                    </a>
                </div>
            </div>

            <aside class="space-y-4">
                <div class="rounded-2xl border border-border p-4">
                    <p
                        class="text-xs font-black tracking-[0.18em] text-powerx-yellow uppercase"
                    >
                        Lesson progress
                    </p>
                    <div class="mt-4 h-3 overflow-hidden rounded-full bg-muted">
                        <div
                            class="h-full rounded-full bg-powerx-yellow"
                            :style="{ width: `${lesson.progressPercentage}%` }"
                        />
                    </div>
                    <button
                        v-if="lesson.canUpdateProgress"
                        type="button"
                        class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-full bg-powerx-yellow px-4 py-2 text-sm font-black text-powerx-navy transition hover:bg-powerx-yellow/90 disabled:pointer-events-none disabled:opacity-60"
                        :disabled="lesson.isCompleted"
                        @click="markCompleted"
                    >
                        <CheckCircle2 class="size-4" />
                        {{ lesson.isCompleted ? 'Completed' : 'Mark complete' }}
                    </button>
                </div>

                <div class="rounded-2xl border border-border p-4">
                    <p
                        class="text-xs font-black tracking-[0.18em] text-powerx-yellow uppercase"
                    >
                        Lesson media
                    </p>
                    <div class="mt-4 grid gap-2 text-sm">
                        <div
                            v-for="media in lesson.media"
                            :key="media.id"
                            class="flex items-center gap-2 rounded-xl bg-muted/50 p-3"
                        >
                            <PlayCircle
                                v-if="media.mediaType === 'video'"
                                class="size-4 text-powerx-yellow"
                            />
                            <FileText
                                v-else
                                class="size-4 text-powerx-yellow"
                            />
                            <div>
                                <p class="font-bold">{{ media.fileName }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ media.collectionLabel }} ·
                                    {{ media.humanReadableSize }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </section>
    </div>
</template>
