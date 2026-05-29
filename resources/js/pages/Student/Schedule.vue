<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    BookOpenCheck,
    CalendarClock,
    MapPin,
    UserRound,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { portal as studentPortal } from '@/routes/student';
import { index as studentCatalogIndex } from '@/routes/student/catalog';
import { index as studentScheduleIndex } from '@/routes/student/schedule';
import type {
    ScheduleSession,
    StudentEnrollment,
    StudentPortalProps,
    Team,
} from '@/types';

type ScheduleRow = ScheduleSession & {
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
                title: 'Schedule',
                href: props.currentTeam
                    ? studentScheduleIndex(props.currentTeam.slug).url
                    : '/',
            },
        ],
    }),
});

const scheduleRows = computed<ScheduleRow[]>(() =>
    props.enrollments
        .flatMap((enrollment) =>
            enrollment.schedule.map((session) => ({
                ...session,
                enrollment,
            })),
        )
        .sort((first, second) => {
            if (!first.startsAt) {
                return 1;
            }

            if (!second.startsAt) {
                return -1;
            }

            return (
                new Date(first.startsAt).getTime() -
                new Date(second.startsAt).getTime()
            );
        }),
);

const upcomingRows = computed(() =>
    scheduleRows.value.filter(
        (session) =>
            session.startsAt !== null &&
            new Date(session.startsAt).getTime() >= Date.now(),
    ),
);

const studentCatalogUrl = computed(() =>
    page.props.currentTeam
        ? studentCatalogIndex(page.props.currentTeam.slug).url
        : '/',
);

const label = (value: string | null | undefined): string =>
    value ? value.replaceAll('_', ' ') : 'Not set';

const dateLabel = (value: string | null): string =>
    value
        ? new Intl.DateTimeFormat('en-QA', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(value))
        : 'To be confirmed';
</script>

<template>
    <Head title="Student schedule" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
        <section
            class="rounded-2xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border"
        >
            <p
                class="text-sm font-black tracking-[0.22em] text-powerx-yellow uppercase"
            >
                Schedule
            </p>
            <div class="mt-3 flex flex-col justify-between gap-4 lg:flex-row">
                <div>
                    <h1 class="text-3xl font-black">
                        {{
                            profile
                                ? `${profile.fullName}'s assigned sessions`
                                : 'Assigned sessions'
                        }}
                    </h1>
                    <p
                        class="mt-2 max-w-3xl text-sm leading-6 text-muted-foreground"
                    >
                        View classroom, online, and practical sessions across
                        your current enrollments.
                    </p>
                </div>
                <div class="grid min-w-64 gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl bg-muted/50 p-4">
                        <p
                            class="text-xs font-black tracking-[0.18em] text-muted-foreground uppercase"
                        >
                            Upcoming
                        </p>
                        <p class="mt-2 text-2xl font-black">
                            {{ upcomingRows.length }}
                        </p>
                    </div>
                    <div class="rounded-2xl bg-muted/50 p-4">
                        <p
                            class="text-xs font-black tracking-[0.18em] text-muted-foreground uppercase"
                        >
                            Total
                        </p>
                        <p class="mt-2 text-2xl font-black">
                            {{ scheduleRows.length }}
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
                Your assigned training sessions will appear here after
                admissions links your student profile.
            </p>
        </section>

        <section
            v-else-if="scheduleRows.length === 0"
            class="rounded-2xl border border-sidebar-border/70 bg-card p-8 text-center shadow-sm dark:border-sidebar-border"
        >
            <CalendarClock class="mx-auto size-12 text-powerx-yellow" />
            <h2 class="mt-4 text-2xl font-black">No sessions assigned yet</h2>
            <p
                class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-muted-foreground"
            >
                Admissions will add classroom, online, or practical sessions
                once your course batch is confirmed.
            </p>
            <Link
                :href="studentCatalogUrl"
                class="mt-6 inline-flex items-center justify-center rounded-full bg-powerx-yellow px-5 py-3 text-sm font-black text-powerx-navy transition hover:bg-powerx-yellow/90"
            >
                Browse courses
            </Link>
        </section>

        <section v-else class="grid gap-4">
            <article
                v-for="session in scheduleRows"
                :key="`${session.enrollment.id}-${session.id}`"
                class="rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm dark:border-sidebar-border"
            >
                <div class="flex flex-col justify-between gap-4 lg:flex-row">
                    <div>
                        <p
                            class="text-xs font-black tracking-[0.18em] text-powerx-yellow uppercase"
                        >
                            {{ label(session.sessionType) }}
                        </p>
                        <h2 class="mt-2 text-xl font-black">
                            {{ session.title }}
                        </h2>
                        <p class="mt-2 text-sm text-muted-foreground">
                            {{ session.enrollment.course.title }}
                        </p>
                    </div>
                    <span
                        class="inline-flex h-fit rounded-full border border-border px-3 py-1 text-xs font-black tracking-[0.18em] text-muted-foreground uppercase"
                    >
                        {{ label(session.status) }}
                    </span>
                </div>

                <div
                    class="mt-5 grid gap-3 text-sm text-muted-foreground md:grid-cols-2 xl:grid-cols-4"
                >
                    <p class="flex items-center gap-2">
                        <CalendarClock class="size-4 text-powerx-yellow" />
                        {{ dateLabel(session.startsAt) }}
                    </p>
                    <p class="flex items-center gap-2">
                        <MapPin class="size-4 text-powerx-yellow" />
                        {{ session.venue ?? 'Venue to be confirmed' }}
                    </p>
                    <p class="flex items-center gap-2">
                        <BookOpenCheck class="size-4 text-powerx-yellow" />
                        {{ session.batch.name }} ·
                        {{ label(session.batch.deliveryMode) }}
                    </p>
                    <p class="flex items-center gap-2">
                        <UserRound class="size-4 text-powerx-yellow" />
                        {{
                            session.batch.instructor ??
                            'Instructor to be assigned'
                        }}
                    </p>
                </div>

                <div
                    v-if="session.practicalOutcome || session.practicalComments"
                    class="mt-4 rounded-2xl bg-muted/50 p-4 text-sm"
                >
                    <p class="font-black">Practical result</p>
                    <p class="mt-1 text-muted-foreground">
                        {{ label(session.practicalOutcome) }}
                        <span v-if="session.practicalComments">
                            · {{ session.practicalComments }}
                        </span>
                    </p>
                </div>
            </article>
        </section>
    </div>
</template>
