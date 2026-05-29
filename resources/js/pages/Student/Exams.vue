<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    BookOpenCheck,
    CheckCircle2,
    Clock3,
    GraduationCap,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { portal as studentPortal } from '@/routes/student';
import { index as studentCoursesIndex } from '@/routes/student/courses';
import { index as studentExamsIndex } from '@/routes/student/exams';
import type {
    StudentEnrollment,
    StudentExam,
    StudentPortalProps,
    Team,
} from '@/types';

type ExamRow = StudentExam & {
    enrollment: StudentEnrollment;
};

const props = defineProps<StudentPortalProps>();
const page = usePage();

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
                title: 'Exams',
                href: props.currentTeam
                    ? studentExamsIndex(props.currentTeam.slug).url
                    : '/',
            },
        ],
    }),
});

const examRows = computed<ExamRow[]>(() =>
    props.enrollments.flatMap((enrollment) =>
        enrollment.exams.map((exam) => ({
            ...exam,
            enrollment,
        })),
    ),
);

const examsReadyToStart = computed(
    () => examRows.value.filter((exam) => exam.canStart).length,
);

const studentCoursesUrl = computed(() =>
    page.props.currentTeam
        ? studentCoursesIndex(page.props.currentTeam.slug).url
        : '/',
);

const label = (value: string | null | undefined): string =>
    value ? value.replaceAll('_', ' ') : 'Not set';
</script>

<template>
    <Head title="Student exams" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
        <section
            class="rounded-2xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border"
        >
            <p
                class="text-sm font-black tracking-[0.22em] text-powerx-yellow uppercase"
            >
                Exams
            </p>
            <div class="mt-3 flex flex-col justify-between gap-4 lg:flex-row">
                <div>
                    <h1 class="text-3xl font-black">
                        {{
                            profile
                                ? `${profile.fullName}'s exam readiness`
                                : 'Exam readiness'
                        }}
                    </h1>
                    <p
                        class="mt-2 max-w-3xl text-sm leading-6 text-muted-foreground"
                    >
                        Review assigned mock exams, attempts, scores, and start
                        eligibility from one dedicated page.
                    </p>
                </div>
                <div class="grid min-w-64 gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl bg-muted/50 p-4">
                        <p
                            class="text-xs font-black tracking-[0.18em] text-muted-foreground uppercase"
                        >
                            Ready
                        </p>
                        <p class="mt-2 text-2xl font-black">
                            {{ examsReadyToStart }}
                        </p>
                    </div>
                    <div class="rounded-2xl bg-muted/50 p-4">
                        <p
                            class="text-xs font-black tracking-[0.18em] text-muted-foreground uppercase"
                        >
                            Assigned
                        </p>
                        <p class="mt-2 text-2xl font-black">
                            {{ examRows.length }}
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
                Exam assignments will appear here after admissions links your
                student profile.
            </p>
        </section>

        <section
            v-else-if="examRows.length === 0"
            class="rounded-2xl border border-sidebar-border/70 bg-card p-8 text-center shadow-sm dark:border-sidebar-border"
        >
            <GraduationCap class="mx-auto size-12 text-powerx-yellow" />
            <h2 class="mt-4 text-2xl font-black">No exams assigned yet</h2>
            <p
                class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-muted-foreground"
            >
                Exams will appear after your course team publishes active mock
                exams for your enrollments.
            </p>
            <Link
                :href="studentCoursesUrl"
                class="mt-6 inline-flex items-center justify-center rounded-full bg-powerx-yellow px-5 py-3 text-sm font-black text-powerx-navy transition hover:bg-powerx-yellow/90"
            >
                View my courses
            </Link>
        </section>

        <section v-else class="grid gap-4 lg:grid-cols-2">
            <article
                v-for="exam in examRows"
                :key="`${exam.enrollment.id}-${exam.id}`"
                class="rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm dark:border-sidebar-border"
            >
                <div class="flex flex-col justify-between gap-4 sm:flex-row">
                    <div>
                        <p
                            class="text-xs font-black tracking-[0.18em] text-powerx-yellow uppercase"
                        >
                            {{ label(exam.examType) }}
                        </p>
                        <h2 class="mt-2 text-xl font-black">
                            {{ exam.title }}
                        </h2>
                        <p class="mt-2 text-sm text-muted-foreground">
                            {{ exam.enrollment.course.title }}
                        </p>
                    </div>
                    <span
                        class="inline-flex h-fit rounded-full border px-3 py-1 text-xs font-black tracking-[0.18em] uppercase"
                        :class="
                            exam.canStart
                                ? 'border-powerx-success/30 bg-powerx-success/10 text-powerx-success'
                                : 'border-border text-muted-foreground'
                        "
                    >
                        {{ exam.canStart ? 'Ready' : 'Locked' }}
                    </span>
                </div>

                <div
                    class="mt-5 grid gap-3 text-sm text-muted-foreground sm:grid-cols-2"
                >
                    <p class="flex items-center gap-2">
                        <Clock3 class="size-4 text-powerx-yellow" />
                        {{ exam.durationMinutes }} minutes
                    </p>
                    <p class="flex items-center gap-2">
                        <CheckCircle2 class="size-4 text-powerx-yellow" />
                        Pass mark {{ exam.passMark }}%
                    </p>
                    <p>
                        Attempts: {{ exam.attemptsUsed }} /
                        {{ exam.maxAttempts }}
                    </p>
                    <p>
                        Best score:
                        {{
                            exam.bestScore === null
                                ? 'Not attempted'
                                : `${exam.bestScore}%`
                        }}
                    </p>
                </div>

                <div class="mt-4 rounded-2xl bg-muted/50 p-4 text-sm">
                    <p class="font-black">Last result</p>
                    <p class="mt-1 text-muted-foreground">
                        {{ label(exam.lastResult) }}
                    </p>
                </div>
            </article>
        </section>
    </div>
</template>
