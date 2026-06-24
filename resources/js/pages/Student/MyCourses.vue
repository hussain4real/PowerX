<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    Banknote,
    BookOpenCheck,
    CheckCircle2,
    Clock3,
    Download,
    FileText,
    GraduationCap,
    LockKeyhole,
    PlayCircle,
    ShieldCheck,
} from 'lucide-vue-next';
import type { Component } from 'vue';
import { computed, ref } from 'vue';
import { portal as studentPortal } from '@/routes/student';
import { index as studentCatalogIndex } from '@/routes/student/catalog';
import { index as studentCoursesIndex } from '@/routes/student/courses';
import { update as updateLessonProgressRoute } from '@/routes/student/lesson-progress';
import { index as studentPaymentsIndex } from '@/routes/student/payments';
import type {
    Lesson,
    StudentEnrollment,
    StudentPortalProps,
    Team,
} from '@/types';

const props = defineProps<StudentPortalProps>();

const page = usePage();
const progressRequests = ref<Record<string, boolean>>({});

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
        ],
    }),
});

const accessClasses: Record<string, string> = {
    open: 'border-powerx-success/30 bg-powerx-success/10 text-powerx-success',
    payment_pending:
        'border-powerx-yellow/30 bg-powerx-yellow/10 text-powerx-yellow',
    admission_pending:
        'border-powerx-blue/30 bg-powerx-blue/10 text-powerx-cyan',
    information_requested:
        'border-powerx-yellow/30 bg-powerx-yellow/10 text-powerx-yellow',
    admission_rejected: 'border-red-500/30 bg-red-500/10 text-red-300',
    enrollment_pending:
        'border-powerx-blue/30 bg-powerx-blue/10 text-powerx-cyan',
    expired: 'border-red-500/30 bg-red-500/10 text-red-300',
};

const lessonIcons: Record<string, Component> = {
    video: PlayCircle,
    document: FileText,
    quiz: GraduationCap,
    practical: ShieldCheck,
};

const label = (value: string | null | undefined): string =>
    value ? value.replaceAll('_', ' ') : 'Not set';

const lessonIcon = (lessonType: string): Component =>
    lessonIcons[lessonType] ?? Clock3;

const currentTeamSlug = computed(() => page.props.currentTeam?.slug);

const studentCatalogUrl = computed(() =>
    currentTeamSlug.value
        ? studentCatalogIndex(currentTeamSlug.value).url
        : '/',
);

const studentPaymentsUrl = computed(() =>
    currentTeamSlug.value
        ? studentPaymentsIndex(currentTeamSlug.value).url
        : '/',
);

const readyEnrollments = computed(() =>
    props.enrollments.filter((enrollment) => enrollment.hasPaidAccess),
);

const firstReadyEnrollment = computed(() => readyEnrollments.value[0]);

const learningActionLabel = (enrollment: StudentEnrollment): string =>
    enrollment.progress.percentage > 0 ? 'Continue learning' : 'Start learning';

const courseLearningHref = (enrollment: StudentEnrollment): string =>
    `#course-${enrollment.id}-learning`;

const studentPaymentHref = (enrollment: StudentEnrollment): string =>
    `${studentPaymentsUrl.value}#enrollment-${enrollment.id}-finance`;

const lessonProgressKey = (
    enrollment: StudentEnrollment,
    lesson: Lesson,
): string => `${enrollment.id}-${lesson.id}`;

const isUpdatingLessonProgress = (
    enrollment: StudentEnrollment,
    lesson: Lesson,
): boolean =>
    progressRequests.value[lessonProgressKey(enrollment, lesson)] === true;

const updateLessonProgress = (
    enrollment: StudentEnrollment,
    lesson: Lesson,
    progressPercentage: number,
    event: string,
): void => {
    if (!lesson.canUpdateProgress || !currentTeamSlug.value) {
        return;
    }

    const key = lessonProgressKey(enrollment, lesson);

    progressRequests.value = {
        ...progressRequests.value,
        [key]: true,
    };

    router.patch(
        updateLessonProgressRoute.url({
            current_team: currentTeamSlug.value,
            enrollment: enrollment.id,
            lesson: lesson.id,
        }),
        {
            progress_percentage: progressPercentage,
            last_position_seconds: lesson.lastPositionSeconds,
            event,
        },
        {
            only: ['summary', 'enrollments'],
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

const markLessonStarted = (
    enrollment: StudentEnrollment,
    lesson: Lesson,
): void => {
    updateLessonProgress(
        enrollment,
        lesson,
        Math.max(lesson.progressPercentage, 1),
        'lesson_started',
    );
};

const markLessonCompleted = (
    enrollment: StudentEnrollment,
    lesson: Lesson,
): void => {
    updateLessonProgress(enrollment, lesson, 100, 'lesson_completed');
};
</script>

<template>
    <Head title="My courses" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
        <section
            class="relative isolate overflow-hidden rounded-2xl border border-sidebar-border/70 bg-gradient-to-br from-powerx-ink via-powerx-navy to-powerx-panel p-6 text-white shadow-sm dark:border-sidebar-border"
        >
            <div
                class="absolute inset-0 -z-10 [background-image:linear-gradient(rgba(255,255,255,0.08)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.08)_1px,transparent_1px)] [background-size:64px_64px] opacity-20"
            />
            <div class="flex flex-col justify-between gap-6 xl:flex-row">
                <div>
                    <p
                        class="text-sm font-black tracking-[0.28em] text-powerx-yellow uppercase"
                    >
                        My courses
                    </p>
                    <h1 class="mt-3 text-3xl font-black md:text-4xl">
                        {{
                            profile
                                ? `${profile.fullName}'s enrolled courses`
                                : 'Your enrolled courses'
                        }}
                    </h1>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-white/65">
                        Open paid courses first, jump into lessons, download
                        resources, and keep your LMS progress updated.
                    </p>
                    <div v-if="firstReadyEnrollment" class="mt-5">
                        <a
                            :href="courseLearningHref(firstReadyEnrollment)"
                            class="inline-flex items-center justify-center gap-2 rounded-full bg-powerx-yellow px-5 py-3 text-sm font-black text-powerx-navy transition hover:bg-powerx-yellow/90"
                        >
                            {{ learningActionLabel(firstReadyEnrollment) }}
                            <ArrowRight class="size-4" />
                        </a>
                    </div>
                </div>
                <div class="grid min-w-64 gap-3 sm:grid-cols-2 xl:grid-cols-1">
                    <div
                        class="rounded-2xl border border-powerx-yellow/30 bg-powerx-yellow/10 p-4"
                    >
                        <p
                            class="text-xs font-black tracking-[0.2em] text-powerx-yellow uppercase"
                        >
                            Enrolled
                        </p>
                        <p class="mt-2 text-2xl font-black">
                            {{ summary.enrolledCourses }}
                        </p>
                    </div>
                    <div
                        class="rounded-2xl border border-white/15 bg-white/10 p-4"
                    >
                        <p
                            class="text-xs font-black tracking-[0.2em] text-white/60 uppercase"
                        >
                            Progress
                        </p>
                        <p class="mt-2 text-2xl font-black">
                            {{ summary.averageProgress }}%
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section
            v-if="!profile"
            class="rounded-2xl border border-sidebar-border/70 bg-card p-8 text-center shadow-sm dark:border-sidebar-border"
        >
            <BookOpenCheck class="mx-auto size-12 text-powerx-yellow" />
            <h2 class="mt-4 text-2xl font-black">
                No student profile is linked yet
            </h2>
            <p
                class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-muted-foreground"
            >
                Once admissions links your account to a student profile, your
                enrolled courses and LMS lessons will appear here.
            </p>
            <Link
                :href="studentCatalogUrl"
                class="mt-6 inline-flex items-center justify-center rounded-full bg-powerx-yellow px-5 py-3 text-sm font-black text-powerx-navy transition hover:bg-powerx-yellow/90"
            >
                Browse courses
            </Link>
        </section>

        <template v-else>
            <section
                v-if="readyEnrollments.length > 0"
                class="rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm dark:border-sidebar-border"
            >
                <div class="flex flex-col justify-between gap-4 lg:flex-row">
                    <div>
                        <p
                            class="text-sm font-black tracking-[0.22em] text-powerx-yellow uppercase"
                        >
                            Ready to learn
                        </p>
                        <h2 class="mt-2 text-2xl font-black">
                            Continue active paid courses
                        </h2>
                        <p class="mt-2 max-w-3xl text-sm text-muted-foreground">
                            These courses are unlocked for LMS lessons and
                            resources.
                        </p>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <article
                        v-for="enrollment in readyEnrollments"
                        :key="`ready-${enrollment.id}`"
                        class="rounded-2xl border border-powerx-yellow/40 bg-powerx-yellow/10 p-5"
                    >
                        <div
                            class="flex flex-col justify-between gap-4 sm:flex-row"
                        >
                            <div>
                                <p
                                    class="text-xs font-black tracking-[0.18em] text-powerx-yellow uppercase"
                                >
                                    {{ label(enrollment.course.category) }}
                                </p>
                                <h3 class="mt-2 text-xl font-black">
                                    {{ enrollment.course.title }}
                                </h3>
                                <p class="mt-2 text-sm text-muted-foreground">
                                    {{ enrollment.progress.completedLessons }}
                                    of
                                    {{ enrollment.progress.totalLessons }}
                                    lessons complete
                                </p>
                            </div>
                            <a
                                :href="courseLearningHref(enrollment)"
                                class="inline-flex items-center justify-center gap-2 self-start rounded-full bg-powerx-yellow px-4 py-2 text-sm font-black text-powerx-navy transition hover:bg-powerx-yellow/90"
                            >
                                {{ learningActionLabel(enrollment) }}
                                <ArrowRight class="size-4" />
                            </a>
                        </div>
                        <div
                            class="mt-4 h-3 overflow-hidden rounded-full bg-background"
                        >
                            <div
                                class="h-full rounded-full bg-powerx-yellow"
                                :style="{
                                    width: `${enrollment.progress.percentage}%`,
                                }"
                            />
                        </div>
                    </article>
                </div>
            </section>

            <section
                class="rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm dark:border-sidebar-border"
            >
                <p
                    class="text-sm font-black tracking-[0.22em] text-powerx-yellow uppercase"
                >
                    All enrollments
                </p>
                <h2 class="mt-2 text-2xl font-black">Current course order</h2>
                <p class="mt-2 max-w-3xl text-sm text-muted-foreground">
                    Courses stay in the same order as the student dashboard, but
                    unlocked courses include direct learning actions.
                </p>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <article
                        v-for="enrollment in enrollments"
                        :key="enrollment.id"
                        class="rounded-2xl border border-border p-5"
                    >
                        <div class="flex flex-wrap gap-3">
                            <span
                                class="rounded-full border px-3 py-1 text-xs font-black tracking-[0.18em] uppercase"
                                :class="
                                    accessClasses[enrollment.accessStatus] ??
                                    accessClasses.expired
                                "
                            >
                                {{ label(enrollment.accessStatus) }}
                            </span>
                            <span
                                class="rounded-full border border-border px-3 py-1 text-xs font-black tracking-[0.18em] text-muted-foreground uppercase"
                            >
                                {{ label(enrollment.paymentStatus) }}
                            </span>
                        </div>
                        <h3 class="mt-4 text-xl font-black">
                            {{ enrollment.course.title }}
                        </h3>
                        <p class="mt-2 text-sm text-muted-foreground">
                            {{ label(enrollment.course.deliveryMode) }}
                            delivery ·
                            {{ enrollment.package.name ?? 'Standard plan' }}
                        </p>
                        <div
                            class="mt-4 h-3 overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                class="h-full rounded-full bg-powerx-yellow"
                                :style="{
                                    width: `${enrollment.progress.percentage}%`,
                                }"
                            />
                        </div>
                        <p class="mt-2 text-xs text-muted-foreground">
                            {{ enrollment.progress.percentage }}% complete
                        </p>
                        <div class="mt-5 flex flex-wrap gap-2">
                            <a
                                v-if="enrollment.hasPaidAccess"
                                :href="courseLearningHref(enrollment)"
                                class="inline-flex items-center gap-2 rounded-full bg-powerx-yellow px-4 py-2 text-sm font-black text-powerx-navy transition hover:bg-powerx-yellow/90"
                            >
                                {{ learningActionLabel(enrollment) }}
                                <ArrowRight class="size-4" />
                            </a>
                            <Link
                                v-else-if="enrollment.paymentStatus !== 'paid'"
                                :href="studentPaymentHref(enrollment)"
                                class="inline-flex items-center gap-2 rounded-full border border-powerx-yellow/50 bg-powerx-yellow/10 px-4 py-2 text-sm font-black text-powerx-yellow transition hover:border-powerx-yellow"
                            >
                                Payment details
                                <ArrowRight class="size-4" />
                            </Link>
                            <Link
                                v-else
                                :href="studentPaymentHref(enrollment)"
                                class="inline-flex items-center gap-2 rounded-full border border-border px-4 py-2 text-sm font-black transition hover:border-powerx-yellow hover:text-powerx-yellow"
                            >
                                Review access status
                                <ArrowRight class="size-4" />
                            </Link>
                            <Link
                                :href="enrollment.course.url"
                                class="inline-flex items-center rounded-full border border-border px-4 py-2 text-sm font-black transition hover:border-powerx-yellow hover:text-powerx-yellow"
                            >
                                Course info
                            </Link>
                        </div>
                    </article>
                </div>
            </section>

            <section
                v-for="enrollment in readyEnrollments"
                :id="`course-${enrollment.id}-learning`"
                :key="`learning-${enrollment.id}`"
                class="scroll-mt-6 rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm dark:border-sidebar-border"
            >
                <div class="flex flex-col justify-between gap-4 lg:flex-row">
                    <div>
                        <p
                            class="text-sm font-black tracking-[0.22em] text-powerx-yellow uppercase"
                        >
                            Learning workspace
                        </p>
                        <h2 class="mt-2 text-2xl font-black">
                            {{ enrollment.course.title }}
                        </h2>
                        <p class="mt-2 max-w-3xl text-sm text-muted-foreground">
                            Work through lessons in order, download assigned
                            resources, and mark each lesson complete.
                        </p>
                    </div>
                    <div class="min-w-56 rounded-2xl bg-muted/50 p-4">
                        <div class="flex items-center justify-between gap-4">
                            <p class="text-sm font-bold text-muted-foreground">
                                Progress
                            </p>
                            <p class="text-2xl font-black">
                                {{ enrollment.progress.percentage }}%
                            </p>
                        </div>
                        <div
                            class="mt-4 h-3 overflow-hidden rounded-full bg-background"
                        >
                            <div
                                class="h-full rounded-full bg-powerx-yellow"
                                :style="{
                                    width: `${enrollment.progress.percentage}%`,
                                }"
                            />
                        </div>
                    </div>
                </div>

                <div class="mt-5 grid gap-4">
                    <article
                        v-for="module in enrollment.modules"
                        :key="module.id"
                        class="rounded-2xl border border-border p-4"
                    >
                        <h3 class="text-lg font-black">
                            {{ module.title }}
                        </h3>
                        <p
                            v-if="module.summary"
                            class="mt-1 text-sm text-muted-foreground"
                        >
                            {{ module.summary }}
                        </p>

                        <div class="mt-4 grid gap-3">
                            <div
                                v-for="lesson in module.lessons"
                                :key="lesson.id"
                                class="rounded-xl bg-muted/50 p-3"
                            >
                                <div
                                    class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center"
                                >
                                    <div class="flex items-center gap-3">
                                        <component
                                            :is="lessonIcon(lesson.lessonType)"
                                            class="size-5 text-powerx-yellow"
                                        />
                                        <div>
                                            <p class="text-sm font-bold">
                                                {{ lesson.title }}
                                            </p>
                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{ label(lesson.lessonType) }}
                                                ·
                                                {{
                                                    lesson.durationMinutes ?? 0
                                                }}
                                                mins
                                            </p>
                                        </div>
                                    </div>
                                    <div
                                        class="flex items-center gap-2 text-xs font-black"
                                    >
                                        <CheckCircle2
                                            v-if="lesson.isCompleted"
                                            class="size-4 text-powerx-success"
                                        />
                                        <LockKeyhole
                                            v-else-if="lesson.isLocked"
                                            class="size-4 text-muted-foreground"
                                        />
                                        <span>
                                            {{
                                                lesson.isCompleted
                                                    ? 'Done'
                                                    : lesson.isLocked
                                                      ? 'Locked'
                                                      : `${lesson.progressPercentage}%`
                                            }}
                                        </span>
                                    </div>
                                </div>

                                <div
                                    v-if="!lesson.isLocked"
                                    class="mt-3 grid gap-3 ps-8"
                                >
                                    <p
                                        v-if="lesson.content"
                                        class="text-sm leading-6 whitespace-pre-line text-muted-foreground"
                                    >
                                        {{ lesson.content }}
                                    </p>
                                    <div
                                        class="h-2 overflow-hidden rounded-full bg-background"
                                    >
                                        <div
                                            class="h-full rounded-full bg-powerx-yellow"
                                            :style="{
                                                width: `${lesson.progressPercentage}%`,
                                            }"
                                        />
                                    </div>
                                    <div
                                        v-if="lesson.canUpdateProgress"
                                        class="flex flex-wrap gap-2"
                                    >
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center rounded-full border border-border bg-background px-3 py-1.5 text-xs font-black transition hover:border-powerx-yellow hover:text-powerx-yellow disabled:pointer-events-none disabled:opacity-60"
                                            :disabled="
                                                isUpdatingLessonProgress(
                                                    enrollment,
                                                    lesson,
                                                )
                                            "
                                            @click="
                                                markLessonStarted(
                                                    enrollment,
                                                    lesson,
                                                )
                                            "
                                        >
                                            Save progress
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center rounded-full bg-powerx-yellow px-3 py-1.5 text-xs font-black text-powerx-navy transition hover:bg-powerx-yellow/90 disabled:pointer-events-none disabled:opacity-60"
                                            :disabled="
                                                lesson.isCompleted ||
                                                isUpdatingLessonProgress(
                                                    enrollment,
                                                    lesson,
                                                )
                                            "
                                            @click="
                                                markLessonCompleted(
                                                    enrollment,
                                                    lesson,
                                                )
                                            "
                                        >
                                            {{
                                                lesson.isCompleted
                                                    ? 'Completed'
                                                    : 'Mark complete'
                                            }}
                                        </button>
                                    </div>
                                    <div
                                        v-if="lesson.media.length > 0"
                                        class="flex flex-wrap gap-2"
                                    >
                                        <a
                                            v-for="media in lesson.media"
                                            :key="media.id"
                                            :href="media.url"
                                            class="inline-flex items-center gap-2 rounded-full border border-border bg-background px-3 py-1.5 text-xs font-black text-foreground transition hover:border-powerx-yellow hover:text-powerx-yellow"
                                        >
                                            <Download class="size-3.5" />
                                            {{ media.collectionLabel }}
                                            · {{ media.fileName }} ·
                                            {{ media.humanReadableSize }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <section
                v-if="readyEnrollments.length === 0"
                class="rounded-2xl border border-sidebar-border/70 bg-card p-8 text-center shadow-sm dark:border-sidebar-border"
            >
                <Banknote class="mx-auto size-12 text-powerx-yellow" />
                <h2 class="mt-4 text-2xl font-black">
                    No courses are open for learning yet
                </h2>
                <p
                    class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-muted-foreground"
                >
                    Complete pending payments or wait for admissions approval to
                    unlock LMS lessons and resources.
                </p>
                <Link
                    :href="studentPaymentsUrl"
                    class="mt-6 inline-flex items-center justify-center rounded-full bg-powerx-yellow px-5 py-3 text-sm font-black text-powerx-navy transition hover:bg-powerx-yellow/90"
                >
                    Review payments
                </Link>
            </section>
        </template>
    </div>
</template>
