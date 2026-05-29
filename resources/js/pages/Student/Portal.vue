<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    Award,
    Banknote,
    BookOpen,
    BookOpenCheck,
    CalendarClock,
    CheckCircle2,
    GraduationCap,
    Search,
} from 'lucide-vue-next';
import type { Component } from 'vue';
import { computed } from 'vue';
import { portal as studentPortal } from '@/routes/student';
import { index as studentCatalogIndex } from '@/routes/student/catalog';
import { index as studentCertificatesIndex } from '@/routes/student/certificates';
import { index as studentCoursesIndex } from '@/routes/student/courses';
import { index as studentExamsIndex } from '@/routes/student/exams';
import { index as studentPaymentsIndex } from '@/routes/student/payments';
import { index as studentScheduleIndex } from '@/routes/student/schedule';
import type { StudentEnrollment, StudentPortalProps, Team } from '@/types';

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
        ],
    }),
});

const currentTeamSlug = computed(() => page.props.currentTeam?.slug);

const studentCoursesUrl = computed(() =>
    currentTeamSlug.value
        ? studentCoursesIndex(currentTeamSlug.value).url
        : '/',
);

const studentScheduleUrl = computed(() =>
    currentTeamSlug.value
        ? studentScheduleIndex(currentTeamSlug.value).url
        : '/',
);

const studentExamsUrl = computed(() =>
    currentTeamSlug.value ? studentExamsIndex(currentTeamSlug.value).url : '/',
);

const studentCertificatesUrl = computed(() =>
    currentTeamSlug.value
        ? studentCertificatesIndex(currentTeamSlug.value).url
        : '/',
);

const studentPaymentsUrl = computed(() =>
    currentTeamSlug.value
        ? studentPaymentsIndex(currentTeamSlug.value).url
        : '/',
);

const studentCatalogUrl = computed(() =>
    currentTeamSlug.value
        ? studentCatalogIndex(currentTeamSlug.value).url
        : '/',
);

const firstOpenEnrollment = computed(() =>
    props.enrollments.find((enrollment) => enrollment.hasPaidAccess),
);

const recentEnrollments = computed(() => props.enrollments.slice(0, 3));

const label = (value: string | null | undefined): string =>
    value ? value.replaceAll('_', ' ') : 'Not set';

const learningActionLabel = (enrollment: StudentEnrollment): string =>
    enrollment.progress.percentage > 0 ? 'Continue learning' : 'Start learning';

const courseLearningUrl = (enrollment: StudentEnrollment): string =>
    `${studentCoursesUrl.value}#course-${enrollment.id}-learning`;

const summaryCards = computed<
    {
        title: string;
        value: string;
        icon: Component;
        accent: string;
    }[]
>(() => [
    {
        title: 'Enrolled courses',
        value: String(props.summary.enrolledCourses),
        icon: BookOpenCheck,
        accent: 'text-powerx-yellow',
    },
    {
        title: 'Active enrollments',
        value: String(props.summary.activeEnrollments),
        icon: CheckCircle2,
        accent: 'text-powerx-success',
    },
    {
        title: 'Pending payments',
        value: String(props.summary.pendingPayments),
        icon: Banknote,
        accent: 'text-powerx-yellow',
    },
    {
        title: 'Certificates issued',
        value: String(props.summary.issuedCertificates),
        icon: Award,
        accent: 'text-powerx-success',
    },
    {
        title: 'Average progress',
        value: `${props.summary.averageProgress}%`,
        icon: GraduationCap,
        accent: 'text-powerx-cyan',
    },
]);

const studentSectionLinks = computed<
    {
        title: string;
        description: string;
        href: string;
        icon: Component;
        cta: string;
    }[]
>(() => [
    {
        title: 'My courses',
        description: 'Open paid courses, lessons, downloads, and LMS progress.',
        href: studentCoursesUrl.value,
        icon: BookOpen,
        cta: 'Start learning',
    },
    {
        title: 'Schedule',
        description: 'Review classroom, online, and practical sessions.',
        href: studentScheduleUrl.value,
        icon: CalendarClock,
        cta: 'View schedule',
    },
    {
        title: 'Exams',
        description: 'Check mock exams, attempts, scores, and readiness.',
        href: studentExamsUrl.value,
        icon: GraduationCap,
        cta: 'Review exams',
    },
    {
        title: 'Certificates',
        description: 'See issued certificates and verification links.',
        href: studentCertificatesUrl.value,
        icon: Award,
        cta: 'View certificates',
    },
    {
        title: 'Payments',
        description: 'Track pending invoices, receipts, and payment status.',
        href: studentPaymentsUrl.value,
        icon: Banknote,
        cta: 'Review payments',
    },
    {
        title: 'Browse courses',
        description: 'Compare available PowerX courses and packages.',
        href: studentCatalogUrl.value,
        icon: Search,
        cta: 'Browse catalog',
    },
]);
</script>

<template>
    <Head title="Student portal" />

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
                        Student overview
                    </p>
                    <h1 class="mt-3 text-3xl font-black md:text-4xl">
                        {{
                            profile
                                ? `${profile.fullName}'s training workspace`
                                : 'Start your PowerX training journey'
                        }}
                    </h1>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-white/65">
                        Use this overview as a launchpad. Courses, schedules,
                        exams, certificates, payments, and the catalog now have
                        dedicated pages so the LMS stays focused.
                    </p>
                    <div v-if="profile" class="mt-5 flex flex-wrap gap-3">
                        <Link
                            v-if="firstOpenEnrollment"
                            :href="courseLearningUrl(firstOpenEnrollment)"
                            class="inline-flex items-center justify-center gap-2 rounded-full bg-powerx-yellow px-5 py-3 text-sm font-black text-powerx-navy transition hover:bg-powerx-yellow/90"
                        >
                            {{ learningActionLabel(firstOpenEnrollment) }}
                            <ArrowRight class="size-4" />
                        </Link>
                        <Link
                            :href="studentCoursesUrl"
                            class="inline-flex items-center justify-center gap-2 rounded-full border border-white/15 bg-white/10 px-5 py-3 text-sm font-black text-white transition hover:border-powerx-yellow hover:text-powerx-yellow"
                        >
                            View my courses
                            <ArrowRight class="size-4" />
                        </Link>
                    </div>
                </div>
                <div
                    class="rounded-2xl border border-powerx-yellow/30 bg-powerx-yellow/10 p-4 text-powerx-yellow"
                >
                    <p class="text-xs font-black tracking-[0.2em] uppercase">
                        Next assigned session
                    </p>
                    <p class="mt-2 text-lg font-black">
                        {{ summary.nextSessionLabel }}
                    </p>
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
                enrollments, payments, lessons, classes, exams, and certificates
                will appear in the dedicated student pages.
            </p>
            <Link
                :href="studentCatalogUrl"
                class="mt-6 inline-flex items-center justify-center rounded-full bg-powerx-yellow px-5 py-3 text-sm font-black text-powerx-navy transition hover:bg-powerx-yellow/90"
            >
                Browse courses
            </Link>
        </section>

        <template v-else>
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <article
                    v-for="card in summaryCards"
                    :key="card.title"
                    class="rounded-2xl border bg-card p-5 shadow-sm"
                >
                    <component
                        :is="card.icon"
                        class="size-7"
                        :class="card.accent"
                    />
                    <p class="mt-4 text-3xl font-black">
                        {{ card.value }}
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ card.title }}
                    </p>
                </article>
            </section>

            <section
                class="rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm dark:border-sidebar-border"
            >
                <div class="flex flex-col justify-between gap-4 lg:flex-row">
                    <div>
                        <p
                            class="text-sm font-black tracking-[0.22em] text-powerx-yellow uppercase"
                        >
                            Student pages
                        </p>
                        <h2 class="mt-2 text-2xl font-black">
                            Go directly to the work you need
                        </h2>
                        <p class="mt-2 max-w-3xl text-sm text-muted-foreground">
                            Each menu item now opens its own page instead of
                            jumping to a section inside this dashboard.
                        </p>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <Link
                        v-for="section in studentSectionLinks"
                        :key="section.title"
                        :href="section.href"
                        class="rounded-2xl border border-border p-5 transition hover:border-powerx-yellow hover:bg-powerx-yellow/5"
                    >
                        <component
                            :is="section.icon"
                            class="size-7 text-powerx-yellow"
                        />
                        <h3 class="mt-4 text-lg font-black">
                            {{ section.title }}
                        </h3>
                        <p class="mt-2 text-sm leading-6 text-muted-foreground">
                            {{ section.description }}
                        </p>
                        <span
                            class="mt-4 inline-flex items-center gap-2 text-sm font-black text-powerx-yellow"
                        >
                            {{ section.cta }}
                            <ArrowRight class="size-4" />
                        </span>
                    </Link>
                </div>
            </section>

            <section
                class="rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm dark:border-sidebar-border"
            >
                <div class="flex flex-col justify-between gap-4 lg:flex-row">
                    <div>
                        <p
                            class="text-sm font-black tracking-[0.22em] text-powerx-yellow uppercase"
                        >
                            Recent enrollments
                        </p>
                        <h2 class="mt-2 text-2xl font-black">
                            Course order is unchanged
                        </h2>
                        <p class="mt-2 max-w-3xl text-sm text-muted-foreground">
                            The detailed LMS workspace lives on My Courses, but
                            this overview still shows the current enrollment
                            order for quick orientation.
                        </p>
                    </div>
                    <Link
                        :href="studentCoursesUrl"
                        class="inline-flex items-center justify-center gap-2 rounded-full border border-border px-4 py-2 text-sm font-black transition hover:border-powerx-yellow hover:text-powerx-yellow"
                    >
                        View all courses
                        <ArrowRight class="size-4" />
                    </Link>
                </div>

                <div
                    v-if="recentEnrollments.length > 0"
                    class="mt-5 grid gap-4"
                >
                    <article
                        v-for="enrollment in recentEnrollments"
                        :key="enrollment.id"
                        class="grid gap-4 rounded-2xl border border-border p-5 lg:grid-cols-[1fr_auto]"
                    >
                        <div>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    class="rounded-full border border-border px-3 py-1 text-xs font-black tracking-[0.18em] text-muted-foreground uppercase"
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
                                {{ label(enrollment.course.category) }} ·
                                {{ label(enrollment.course.deliveryMode) }}
                                delivery ·
                                {{ enrollment.package.name ?? 'Standard plan' }}
                            </p>
                        </div>
                        <div class="min-w-56 rounded-2xl bg-muted/50 p-4">
                            <div
                                class="flex items-center justify-between gap-4 text-sm"
                            >
                                <span class="font-bold text-muted-foreground">
                                    Progress
                                </span>
                                <span class="text-xl font-black">
                                    {{ enrollment.progress.percentage }}%
                                </span>
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
                    </article>
                </div>
                <p v-else class="mt-5 text-sm text-muted-foreground">
                    Enrollments will appear here after admissions registers you
                    for a course.
                </p>
            </section>
        </template>
    </div>
</template>
