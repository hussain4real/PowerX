<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    BookOpenCheck,
    CalendarClock,
    CheckCircle2,
    ClipboardCheck,
    GraduationCap,
    MapPin,
    UsersRound,
} from 'lucide-vue-next';
import type { Team } from '@/types';

interface InstructorSummary {
    assignedBatches: number;
    scheduledSessions: number;
    students: number;
    pendingAttendance: number;
    pendingPractical: number;
    nextSessionLabel: string;
}

interface InstructorStudent {
    attendanceId: number;
    enrollmentId: number;
    studentProfileId: number;
    studentName: string;
    studentEmail: string;
    studentMobile: string | null;
    attendanceStatus: string;
    attendedAt: string | null;
    practicalOutcome: string | null;
    practicalScore: number | null;
    practicalComments: string | null;
}

interface InstructorSession {
    id: number;
    title: string;
    sessionType: string;
    venue: string | null;
    status: string;
    startsAt: string | null;
    endsAt: string | null;
    students: InstructorStudent[];
}

interface InstructorResource {
    id: number;
    title: string;
    lessons: {
        id: number;
        title: string;
        lessonType: string;
        durationMinutes: number | null;
        isPreview: boolean;
    }[];
}

interface InstructorBatch {
    id: number;
    name: string;
    status: string;
    deliveryMode: string;
    venue: string | null;
    capacity: number;
    startsAt: string | null;
    endsAt: string | null;
    attendanceRate: number;
    course: {
        id: number;
        title: string;
        category: string | null;
        deliveryMode: string;
        url: string;
    };
    sessions: InstructorSession[];
    resources: InstructorResource[];
}

defineProps<{
    summary: InstructorSummary;
    batches: InstructorBatch[];
}>();

defineOptions({
    layout: (props: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Instructor portal',
                href: props.currentTeam
                    ? `/${props.currentTeam.slug}/instructor-portal`
                    : '/',
            },
        ],
    }),
});

const label = (value: string | null | undefined): string =>
    value ? value.replaceAll('_', ' ') : 'Not set';

const dateLabel = (value: string | null): string =>
    value
        ? new Intl.DateTimeFormat('en-QA', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(value))
        : 'To be confirmed';

const attendanceClasses: Record<string, string> = {
    present: 'bg-powerx-success/10 text-powerx-success',
    late: 'bg-powerx-yellow/10 text-powerx-yellow',
    pending: 'bg-muted text-muted-foreground',
    absent: 'bg-red-500/10 text-red-300',
    excused: 'bg-powerx-blue/10 text-powerx-cyan',
};
</script>

<template>
    <Head title="Instructor portal" />

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
                        Instructor workspace
                    </p>
                    <h1 class="mt-3 text-3xl font-black md:text-4xl">
                        Assigned classes and practical records
                    </h1>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-white/65">
                        Review your PowerX batches, session rosters, attendance
                        status, practical outcomes, comments, and course
                        resources before updating operational records.
                    </p>
                </div>
                <div
                    class="rounded-2xl border border-powerx-yellow/30 bg-powerx-yellow/10 p-4 text-powerx-yellow"
                >
                    <p class="text-xs font-black tracking-[0.2em] uppercase">
                        Next session
                    </p>
                    <p class="mt-2 text-lg font-black">
                        {{ summary.nextSessionLabel }}
                    </p>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <article class="rounded-2xl border bg-card p-5 shadow-sm">
                <ClipboardCheck class="size-7 text-powerx-yellow" />
                <p class="mt-4 text-3xl font-black">
                    {{ summary.assignedBatches }}
                </p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Assigned batches
                </p>
            </article>
            <article class="rounded-2xl border bg-card p-5 shadow-sm">
                <CalendarClock class="size-7 text-powerx-cyan" />
                <p class="mt-4 text-3xl font-black">
                    {{ summary.scheduledSessions }}
                </p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Scheduled sessions
                </p>
            </article>
            <article class="rounded-2xl border bg-card p-5 shadow-sm">
                <UsersRound class="size-7 text-powerx-success" />
                <p class="mt-4 text-3xl font-black">
                    {{ summary.students }}
                </p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Assigned students
                </p>
            </article>
            <article class="rounded-2xl border bg-card p-5 shadow-sm">
                <CheckCircle2 class="size-7 text-powerx-yellow" />
                <p class="mt-4 text-3xl font-black">
                    {{ summary.pendingAttendance }}
                </p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Attendance pending
                </p>
            </article>
            <article class="rounded-2xl border bg-card p-5 shadow-sm">
                <GraduationCap class="size-7 text-powerx-cyan" />
                <p class="mt-4 text-3xl font-black">
                    {{ summary.pendingPractical }}
                </p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Practical pending
                </p>
            </article>
        </section>

        <section
            v-if="batches.length === 0"
            class="rounded-2xl border border-sidebar-border/70 bg-card p-8 text-center shadow-sm dark:border-sidebar-border"
        >
            <UsersRound class="mx-auto size-12 text-powerx-yellow" />
            <h2 class="mt-4 text-2xl font-black">
                No batches are assigned yet
            </h2>
            <p
                class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-muted-foreground"
            >
                Once training operations assigns you to a batch, session
                rosters, attendance records, practical comments, and course
                resources will appear here.
            </p>
        </section>

        <section v-else class="grid gap-6">
            <article
                v-for="batch in batches"
                :key="batch.id"
                class="overflow-hidden rounded-2xl border border-sidebar-border/70 bg-card shadow-sm dark:border-sidebar-border"
            >
                <div
                    class="grid gap-6 border-b border-border bg-gradient-to-r from-powerx-ink to-powerx-panel p-6 text-white xl:grid-cols-[1fr_auto]"
                >
                    <div>
                        <div class="flex flex-wrap gap-3">
                            <span
                                class="rounded-full border border-powerx-yellow/30 bg-powerx-yellow/10 px-3 py-1 text-xs font-black tracking-[0.18em] text-powerx-yellow uppercase"
                            >
                                {{ label(batch.status) }}
                            </span>
                            <span
                                class="rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-black tracking-[0.18em] text-white/75 uppercase"
                            >
                                {{ label(batch.deliveryMode) }}
                            </span>
                        </div>
                        <h2 class="mt-4 text-2xl font-black">
                            {{ batch.name }} · {{ batch.course.title }}
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-white/65">
                            {{ label(batch.course.category) }} · capacity
                            {{ batch.capacity }} ·
                            {{ dateLabel(batch.startsAt) }}
                        </p>
                    </div>
                    <div class="min-w-56 rounded-2xl bg-white/10 p-4">
                        <p class="text-sm font-bold text-white/70">
                            Attendance rate
                        </p>
                        <p class="mt-3 text-4xl font-black">
                            {{ batch.attendanceRate }}%
                        </p>
                    </div>
                </div>

                <div class="grid gap-6 p-6 xl:grid-cols-[1.2fr_0.8fr]">
                    <section>
                        <div class="flex items-center justify-between gap-4">
                            <h3 class="text-xl font-black">Session rosters</h3>
                            <Link
                                :href="batch.course.url"
                                class="text-sm font-black text-powerx-yellow hover:underline"
                            >
                                Course page
                            </Link>
                        </div>
                        <div class="mt-4 grid gap-4">
                            <div
                                v-for="session in batch.sessions"
                                :key="session.id"
                                class="rounded-2xl border border-border p-4"
                            >
                                <div
                                    class="flex flex-col justify-between gap-3 md:flex-row md:items-center"
                                >
                                    <div>
                                        <h4 class="font-black">
                                            {{ session.title }}
                                        </h4>
                                        <p
                                            class="mt-1 text-sm text-muted-foreground"
                                        >
                                            {{ dateLabel(session.startsAt) }} ·
                                            {{ label(session.sessionType) }}
                                        </p>
                                    </div>
                                    <div
                                        class="inline-flex items-center gap-2 text-sm text-muted-foreground"
                                    >
                                        <MapPin class="size-4" />
                                        {{
                                            session.venue ??
                                            batch.venue ??
                                            'TBC'
                                        }}
                                    </div>
                                </div>

                                <div
                                    class="mt-4 overflow-hidden rounded-xl border"
                                >
                                    <div
                                        v-for="student in session.students"
                                        :key="student.attendanceId"
                                        class="grid gap-3 border-b p-4 last:border-b-0 md:grid-cols-[1fr_auto] md:items-center"
                                    >
                                        <div>
                                            <p class="font-bold">
                                                {{ student.studentName }}
                                            </p>
                                            <p
                                                class="mt-1 text-sm text-muted-foreground"
                                            >
                                                {{ student.studentEmail }} ·
                                                {{
                                                    student.studentMobile ??
                                                    'No mobile'
                                                }}
                                            </p>
                                            <p
                                                v-if="student.practicalComments"
                                                class="mt-2 text-sm text-muted-foreground"
                                            >
                                                {{ student.practicalComments }}
                                            </p>
                                        </div>
                                        <div
                                            class="flex flex-wrap justify-start gap-2 md:justify-end"
                                        >
                                            <span
                                                class="rounded-full px-3 py-1 text-xs font-black uppercase"
                                                :class="
                                                    attendanceClasses[
                                                        student.attendanceStatus
                                                    ] ??
                                                    attendanceClasses.pending
                                                "
                                            >
                                                {{
                                                    label(
                                                        student.attendanceStatus,
                                                    )
                                                }}
                                            </span>
                                            <span
                                                class="rounded-full bg-muted px-3 py-1 text-xs font-black text-muted-foreground uppercase"
                                            >
                                                Practical:
                                                {{
                                                    label(
                                                        student.practicalOutcome,
                                                    )
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                    <p
                                        v-if="session.students.length === 0"
                                        class="p-4 text-sm text-muted-foreground"
                                    >
                                        No enrolled students have attendance
                                        records for this session yet.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <aside class="grid content-start gap-6">
                        <section class="rounded-2xl border border-border p-5">
                            <div class="flex items-center gap-3">
                                <BookOpenCheck
                                    class="size-6 text-powerx-yellow"
                                />
                                <h3 class="text-lg font-black">
                                    Course resources
                                </h3>
                            </div>
                            <div class="mt-4 grid gap-3">
                                <div
                                    v-for="resource in batch.resources"
                                    :key="resource.id"
                                    class="rounded-xl bg-muted/50 p-3"
                                >
                                    <p class="font-bold">
                                        {{ resource.title }}
                                    </p>
                                    <p
                                        class="mt-1 text-sm text-muted-foreground"
                                    >
                                        {{ resource.lessons.length }} lessons
                                        available for this batch.
                                    </p>
                                </div>
                            </div>
                        </section>
                    </aside>
                </div>
            </article>
        </section>
    </div>
</template>
