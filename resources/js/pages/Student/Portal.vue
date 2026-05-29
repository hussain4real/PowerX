<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Award,
    Banknote,
    BookOpenCheck,
    CalendarClock,
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
import { computed } from 'vue';
import { index as coursesIndex } from '@/routes/courses';
import type { Team } from '@/types';

interface StudentProfile {
    id: number;
    fullName: string;
    email: string;
    mobile: string | null;
    profession: string | null;
    qatarLocation: string | null;
    preferredSchedule: string | null;
    documentStatus: string;
}

interface StudentSummary {
    enrolledCourses: number;
    activeEnrollments: number;
    pendingPayments: number;
    issuedCertificates: number;
    averageProgress: number;
    nextSessionLabel: string;
}

interface Lesson {
    id: number;
    title: string;
    lessonType: string;
    durationMinutes: number | null;
    isPreview: boolean;
    isLocked: boolean;
    progressPercentage: number;
    isCompleted: boolean;
    media: LessonMedia[];
}

interface LessonMedia {
    id: number;
    name: string;
    fileName: string;
    collectionName: string;
    collectionLabel: string;
    mimeType: string;
    size: number;
    humanReadableSize: string;
    url: string;
    expiresAt: string;
}

interface CourseModule {
    id: number;
    title: string;
    summary: string | null;
    lessons: Lesson[];
}

interface ScheduleSession {
    id: number;
    title: string;
    sessionType: string;
    venue: string | null;
    status: string;
    practicalOutcome: string | null;
    practicalComments: string | null;
    startsAt: string | null;
    endsAt: string | null;
    batch: {
        name: string;
        deliveryMode: string;
        instructor: string | null;
    };
}

interface StudentExam {
    id: number;
    title: string;
    examType: string;
    durationMinutes: number;
    passMark: number;
    maxAttempts: number;
    attemptsUsed: number;
    bestScore: string | number | null;
    lastResult: string | null;
    canStart: boolean;
}

interface StudentCertificate {
    id: number;
    certificateNumber: string;
    status: string;
    result: string | null;
    issuedAt: string | null;
    expiresAt: string | null;
    verifyUrl: string;
}

interface FinanceItem {
    id: number;
    number?: string;
    method?: string;
    reference?: string | null;
    type?: string;
    status: string;
    currency: string;
    total?: number;
    amount?: number;
    dueAt?: string | null;
    paidAt?: string | null;
}

interface StudentEnrollment {
    id: number;
    status: string;
    paymentStatus: string;
    accessStatus: string;
    hasPaidAccess: boolean;
    accessStartsAt: string | null;
    accessExpiresAt: string | null;
    course: {
        id: number;
        title: string;
        slug: string;
        category: string | null;
        deliveryMode: string;
        url: string;
    };
    package: {
        name: string | null;
        packageType: string | null;
        validityDays: number | null;
        includesCertificate: boolean | null;
    };
    progress: {
        completedLessons: number;
        totalLessons: number;
        percentage: number;
    };
    modules: CourseModule[];
    schedule: ScheduleSession[];
    exams: StudentExam[];
    certificates: StudentCertificate[];
    finance: {
        invoices: FinanceItem[];
        payments: FinanceItem[];
    };
}

const props = defineProps<{
    profile: StudentProfile | null;
    summary: StudentSummary;
    enrollments: StudentEnrollment[];
}>();

defineOptions({
    layout: (props: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Student portal',
                href: props.currentTeam
                    ? `/${props.currentTeam.slug}/student-portal`
                    : '/',
            },
        ],
    }),
});

const accessClasses: Record<string, string> = {
    open: 'border-powerx-success/30 bg-powerx-success/10 text-powerx-success',
    payment_pending:
        'border-powerx-yellow/30 bg-powerx-yellow/10 text-powerx-yellow',
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

const dateLabel = (value: string | null): string =>
    value
        ? new Intl.DateTimeFormat('en-QA', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(value))
        : 'To be confirmed';

const money = (amount: number | undefined, currency: string): string =>
    new Intl.NumberFormat('en-QA', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(amount ?? 0);

const lessonIcon = (lessonType: string): Component =>
    lessonIcons[lessonType] ?? Clock3;

const firstOpenEnrollmentId = computed(
    () => props.enrollments.find((enrollment) => enrollment.hasPaidAccess)?.id,
);

const firstPaymentPendingEnrollmentId = computed(
    () =>
        props.enrollments.find(
            (enrollment) => enrollment.paymentStatus !== 'paid',
        )?.id,
);

const firstFinanceEnrollmentId = computed(
    () =>
        props.enrollments.find(
            (enrollment) =>
                enrollment.paymentStatus !== 'paid' ||
                enrollment.finance.invoices.length > 0 ||
                enrollment.finance.payments.length > 0,
        )?.id,
);

const firstScheduledEnrollmentId = computed(
    () =>
        props.enrollments.find((enrollment) => enrollment.schedule.length > 0)
            ?.id,
);

const firstExamEnrollmentId = computed(
    () =>
        props.enrollments.find((enrollment) => enrollment.exams.length > 0)?.id,
);

const firstCertificateEnrollmentId = computed(
    () =>
        props.enrollments.find(
            (enrollment) => enrollment.certificates.length > 0,
        )?.id,
);

const sectionHref = (
    enrollmentId: number | undefined,
    section: string,
): string =>
    enrollmentId
        ? `#enrollment-${enrollmentId}-${section}`
        : '#student-actions';

const learningActionLabel = (enrollment: StudentEnrollment): string =>
    enrollment.progress.percentage > 0 ? 'Continue learning' : 'Start learning';
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
                        Student learning portal
                    </p>
                    <h1 class="mt-3 text-3xl font-black md:text-4xl">
                        {{
                            profile
                                ? `${profile.fullName}'s training dashboard`
                                : 'Start your PowerX training journey'
                        }}
                    </h1>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-white/65">
                        Track enrolled courses, paid access, classroom
                        schedules, LMS progress, mock exams, payments, and
                        certificate records from one responsive workspace.
                    </p>
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
                will appear here.
            </p>
            <Link
                :href="coursesIndex()"
                class="mt-6 inline-flex items-center justify-center rounded-full bg-powerx-yellow px-5 py-3 text-sm font-black text-powerx-navy transition hover:bg-powerx-yellow/90"
            >
                Browse courses
            </Link>
        </section>

        <template v-else>
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <article class="rounded-2xl border bg-card p-5 shadow-sm">
                    <BookOpenCheck class="size-7 text-powerx-yellow" />
                    <p class="mt-4 text-3xl font-black">
                        {{ summary.enrolledCourses }}
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Enrolled courses
                    </p>
                </article>
                <article class="rounded-2xl border bg-card p-5 shadow-sm">
                    <CheckCircle2 class="size-7 text-powerx-success" />
                    <p class="mt-4 text-3xl font-black">
                        {{ summary.activeEnrollments }}
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Active enrollments
                    </p>
                </article>
                <article class="rounded-2xl border bg-card p-5 shadow-sm">
                    <Banknote class="size-7 text-powerx-yellow" />
                    <p class="mt-4 text-3xl font-black">
                        {{ summary.pendingPayments }}
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Pending payments
                    </p>
                </article>
                <article class="rounded-2xl border bg-card p-5 shadow-sm">
                    <Award class="size-7 text-powerx-success" />
                    <p class="mt-4 text-3xl font-black">
                        {{ summary.issuedCertificates }}
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Certificates issued
                    </p>
                </article>
                <article class="rounded-2xl border bg-card p-5 shadow-sm">
                    <GraduationCap class="size-7 text-powerx-cyan" />
                    <p class="mt-4 text-3xl font-black">
                        {{ summary.averageProgress }}%
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Average progress
                    </p>
                </article>
            </section>

            <section
                id="student-actions"
                class="rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm dark:border-sidebar-border"
            >
                <div class="flex flex-col justify-between gap-4 lg:flex-row">
                    <div>
                        <p
                            class="text-sm font-black tracking-[0.22em] text-powerx-yellow uppercase"
                        >
                            Student actions
                        </p>
                        <h2 class="mt-2 text-2xl font-black">
                            Access your learning workspace
                        </h2>
                        <p class="mt-2 max-w-3xl text-sm text-muted-foreground">
                            Use these shortcuts to jump into lessons, resolve
                            pending payments, review assigned sessions, check
                            exams, or verify certificates.
                        </p>
                    </div>
                    <Link
                        :href="coursesIndex()"
                        class="inline-flex items-center justify-center gap-2 rounded-full border border-border px-4 py-2 text-sm font-black transition hover:border-powerx-yellow hover:text-powerx-yellow"
                    >
                        Browse courses
                        <ArrowRight class="size-4" />
                    </Link>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                    <a
                        v-if="firstOpenEnrollmentId"
                        :href="sectionHref(firstOpenEnrollmentId, 'learning')"
                        class="rounded-2xl border border-powerx-yellow/40 bg-powerx-yellow/10 p-4 text-sm transition hover:border-powerx-yellow hover:bg-powerx-yellow/15"
                    >
                        <p class="font-black text-powerx-yellow">
                            Continue course
                        </p>
                        <p class="mt-1 text-muted-foreground">
                            Open paid lessons and downloads.
                        </p>
                    </a>
                    <a
                        v-if="firstPaymentPendingEnrollmentId"
                        :href="
                            sectionHref(
                                firstPaymentPendingEnrollmentId,
                                'finance',
                            )
                        "
                        class="rounded-2xl border border-powerx-yellow/40 bg-powerx-yellow/10 p-4 text-sm transition hover:border-powerx-yellow hover:bg-powerx-yellow/15"
                    >
                        <p class="font-black text-powerx-yellow">
                            Resolve payment
                        </p>
                        <p class="mt-1 text-muted-foreground">
                            Check pending invoice and payment status.
                        </p>
                    </a>
                    <a
                        v-if="firstScheduledEnrollmentId"
                        :href="
                            sectionHref(firstScheduledEnrollmentId, 'schedule')
                        "
                        class="rounded-2xl border border-border p-4 text-sm transition hover:border-powerx-yellow hover:text-powerx-yellow"
                    >
                        <p class="font-black">View schedule</p>
                        <p class="mt-1 text-muted-foreground">
                            See assigned classroom or practical sessions.
                        </p>
                    </a>
                    <a
                        v-if="firstExamEnrollmentId"
                        :href="sectionHref(firstExamEnrollmentId, 'exams')"
                        class="rounded-2xl border border-border p-4 text-sm transition hover:border-powerx-yellow hover:text-powerx-yellow"
                    >
                        <p class="font-black">Review exams</p>
                        <p class="mt-1 text-muted-foreground">
                            Check attempts and readiness.
                        </p>
                    </a>
                    <a
                        v-if="firstCertificateEnrollmentId"
                        :href="
                            sectionHref(
                                firstCertificateEnrollmentId,
                                'certificates',
                            )
                        "
                        class="rounded-2xl border border-border p-4 text-sm transition hover:border-powerx-yellow hover:text-powerx-yellow"
                    >
                        <p class="font-black">Certificates</p>
                        <p class="mt-1 text-muted-foreground">
                            Verify issued completion records.
                        </p>
                    </a>
                </div>
            </section>

            <span
                v-if="!firstScheduledEnrollmentId"
                id="student-schedule"
                class="scroll-mt-6"
            />
            <span
                v-if="!firstExamEnrollmentId"
                id="student-exams"
                class="scroll-mt-6"
            />
            <span
                v-if="!firstCertificateEnrollmentId"
                id="student-certificates"
                class="scroll-mt-6"
            />
            <span
                v-if="!firstFinanceEnrollmentId"
                id="student-payments"
                class="scroll-mt-6"
            />

            <section id="student-enrollments" class="grid scroll-mt-6 gap-6">
                <article
                    v-for="enrollment in enrollments"
                    :key="enrollment.id"
                    :id="`enrollment-${enrollment.id}`"
                    class="overflow-hidden rounded-2xl border border-sidebar-border/70 bg-card shadow-sm dark:border-sidebar-border"
                >
                    <div
                        class="grid gap-6 border-b border-border bg-gradient-to-r from-powerx-ink to-powerx-panel p-6 text-white xl:grid-cols-[1fr_auto]"
                    >
                        <div>
                            <div class="flex flex-wrap gap-3">
                                <span
                                    class="rounded-full border px-3 py-1 text-xs font-black tracking-[0.18em] uppercase"
                                    :class="
                                        accessClasses[
                                            enrollment.accessStatus
                                        ] ?? accessClasses.expired
                                    "
                                >
                                    {{ label(enrollment.accessStatus) }}
                                </span>
                                <span
                                    class="rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-black tracking-[0.18em] text-white/75 uppercase"
                                >
                                    {{ label(enrollment.paymentStatus) }}
                                </span>
                            </div>
                            <h2 class="mt-4 text-2xl font-black">
                                {{ enrollment.course.title }}
                            </h2>
                            <p class="mt-2 text-sm leading-6 text-white/65">
                                {{ label(enrollment.course.category) }} ·
                                {{ label(enrollment.course.deliveryMode) }}
                                delivery ·
                                {{ enrollment.package.name ?? 'Standard plan' }}
                            </p>
                            <div class="mt-5 flex flex-wrap gap-3">
                                <a
                                    v-if="enrollment.hasPaidAccess"
                                    :href="`#enrollment-${enrollment.id}-learning`"
                                    class="inline-flex items-center gap-2 rounded-full bg-powerx-yellow px-4 py-2 text-sm font-black text-powerx-navy transition hover:bg-powerx-yellow/90"
                                >
                                    {{ learningActionLabel(enrollment) }}
                                    <ArrowRight class="size-4" />
                                </a>
                                <a
                                    v-else-if="
                                        enrollment.paymentStatus !== 'paid'
                                    "
                                    :href="`#enrollment-${enrollment.id}-finance`"
                                    class="inline-flex items-center gap-2 rounded-full border border-powerx-yellow/50 bg-powerx-yellow/10 px-4 py-2 text-sm font-black text-powerx-yellow transition hover:border-powerx-yellow"
                                >
                                    Payment details
                                    <ArrowRight class="size-4" />
                                </a>
                                <a
                                    v-else
                                    :href="`#enrollment-${enrollment.id}-finance`"
                                    class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-black text-white transition hover:border-powerx-yellow hover:text-powerx-yellow"
                                >
                                    Review access status
                                    <ArrowRight class="size-4" />
                                </a>
                                <a
                                    v-if="enrollment.schedule.length > 0"
                                    :href="`#enrollment-${enrollment.id}-schedule`"
                                    class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-black text-white transition hover:border-powerx-yellow hover:text-powerx-yellow"
                                >
                                    Schedule
                                </a>
                                <a
                                    v-if="enrollment.exams.length > 0"
                                    :href="`#enrollment-${enrollment.id}-exams`"
                                    class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-black text-white transition hover:border-powerx-yellow hover:text-powerx-yellow"
                                >
                                    Exams
                                </a>
                                <a
                                    v-if="enrollment.certificates.length > 0"
                                    :href="`#enrollment-${enrollment.id}-certificates`"
                                    class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-black text-white transition hover:border-powerx-yellow hover:text-powerx-yellow"
                                >
                                    Certificates
                                </a>
                                <Link
                                    :href="enrollment.course.url"
                                    class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-black text-white transition hover:border-powerx-yellow hover:text-powerx-yellow"
                                >
                                    Course info
                                </Link>
                            </div>
                        </div>
                        <div class="min-w-64 rounded-2xl bg-white/10 p-4">
                            <div
                                class="flex items-center justify-between gap-4"
                            >
                                <p class="text-sm font-bold text-white/70">
                                    Course progress
                                </p>
                                <p class="text-2xl font-black">
                                    {{ enrollment.progress.percentage }}%
                                </p>
                            </div>
                            <div
                                class="mt-4 h-3 overflow-hidden rounded-full bg-white/15"
                            >
                                <div
                                    class="h-full rounded-full bg-powerx-yellow"
                                    :style="{
                                        width: `${enrollment.progress.percentage}%`,
                                    }"
                                />
                            </div>
                            <p class="mt-3 text-xs text-white/60">
                                {{ enrollment.progress.completedLessons }} of
                                {{ enrollment.progress.totalLessons }} lessons
                                complete
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-6 p-6 xl:grid-cols-[1.1fr_0.9fr]">
                        <section
                            :id="`enrollment-${enrollment.id}-learning`"
                            class="scroll-mt-6"
                        >
                            <div
                                class="flex items-center justify-between gap-4"
                            >
                                <h3 class="text-xl font-black">
                                    Lessons and resources
                                </h3>
                                <Link
                                    :href="enrollment.course.url"
                                    class="text-sm font-black text-powerx-yellow hover:underline"
                                >
                                    Course page
                                </Link>
                            </div>

                            <div class="mt-4 grid gap-4">
                                <div
                                    v-for="module in enrollment.modules"
                                    :key="module.id"
                                    class="rounded-2xl border border-border p-4"
                                >
                                    <h4 class="font-black">
                                        {{ module.title }}
                                    </h4>
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
                                                class="flex items-center justify-between gap-4"
                                            >
                                                <div
                                                    class="flex items-center gap-3"
                                                >
                                                    <component
                                                        :is="
                                                            lessonIcon(
                                                                lesson.lessonType,
                                                            )
                                                        "
                                                        class="size-5 text-powerx-yellow"
                                                    />
                                                    <div>
                                                        <p
                                                            class="text-sm font-bold"
                                                        >
                                                            {{ lesson.title }}
                                                        </p>
                                                        <p
                                                            class="text-xs text-muted-foreground"
                                                        >
                                                            {{
                                                                label(
                                                                    lesson.lessonType,
                                                                )
                                                            }}
                                                            ·
                                                            {{
                                                                lesson.durationMinutes ??
                                                                0
                                                            }}
                                                            mins
                                                        </p>
                                                    </div>
                                                </div>
                                                <div
                                                    class="flex items-center gap-2 text-xs font-black"
                                                >
                                                    <CheckCircle2
                                                        v-if="
                                                            lesson.isCompleted
                                                        "
                                                        class="size-4 text-powerx-success"
                                                    />
                                                    <LockKeyhole
                                                        v-else-if="
                                                            lesson.isLocked
                                                        "
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
                                                v-if="lesson.media.length > 0"
                                                class="mt-3 flex flex-wrap gap-2 ps-8"
                                            >
                                                <a
                                                    v-for="media in lesson.media"
                                                    :key="media.id"
                                                    :href="media.url"
                                                    class="inline-flex items-center gap-2 rounded-full border border-border bg-background px-3 py-1.5 text-xs font-black text-foreground transition hover:border-powerx-yellow hover:text-powerx-yellow"
                                                >
                                                    <Download
                                                        class="size-3.5"
                                                    />
                                                    {{
                                                        media.collectionLabel
                                                    }}
                                                    · {{ media.fileName }} ·
                                                    {{
                                                        media.humanReadableSize
                                                    }}
                                                </a>
                                            </div>
                                            <p
                                                v-else-if="lesson.isLocked"
                                                class="mt-3 ps-8 text-xs font-bold text-muted-foreground"
                                            >
                                                Complete payment/admissions
                                                approval to unlock this lesson.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <aside class="grid content-start gap-6">
                            <span
                                v-if="
                                    enrollment.id === firstScheduledEnrollmentId
                                "
                                id="student-schedule"
                                class="scroll-mt-6"
                            />
                            <section
                                :id="`enrollment-${enrollment.id}-schedule`"
                                class="rounded-2xl border border-border p-5"
                            >
                                <div class="flex items-center gap-3">
                                    <CalendarClock
                                        class="size-6 text-powerx-yellow"
                                    />
                                    <h3 class="text-lg font-black">
                                        Assigned schedule
                                    </h3>
                                </div>
                                <div class="mt-4 grid gap-3">
                                    <div
                                        v-for="session in enrollment.schedule"
                                        :key="session.id"
                                        class="rounded-xl bg-muted/50 p-3"
                                    >
                                        <p class="font-bold">
                                            {{ session.title }}
                                        </p>
                                        <p
                                            class="mt-1 text-sm text-muted-foreground"
                                        >
                                            {{ dateLabel(session.startsAt) }} ·
                                            {{ session.venue ?? 'Venue TBC' }}
                                        </p>
                                        <p
                                            class="mt-1 text-xs font-bold text-powerx-yellow"
                                        >
                                            {{ session.batch.name }} ·
                                            {{
                                                session.batch.instructor ??
                                                'TBC'
                                            }}
                                        </p>
                                    </div>
                                    <p
                                        v-if="enrollment.schedule.length === 0"
                                        class="text-sm text-muted-foreground"
                                    >
                                        No classroom or practical session has
                                        been assigned yet.
                                    </p>
                                </div>
                            </section>

                            <span
                                v-if="enrollment.id === firstExamEnrollmentId"
                                id="student-exams"
                                class="scroll-mt-6"
                            />
                            <section
                                :id="`enrollment-${enrollment.id}-exams`"
                                class="rounded-2xl border border-border p-5"
                            >
                                <div class="flex items-center gap-3">
                                    <GraduationCap
                                        class="size-6 text-powerx-cyan"
                                    />
                                    <h3 class="text-lg font-black">
                                        Exams and attempts
                                    </h3>
                                </div>
                                <div class="mt-4 grid gap-3">
                                    <div
                                        v-for="exam in enrollment.exams"
                                        :key="exam.id"
                                        class="rounded-xl bg-muted/50 p-3"
                                    >
                                        <div
                                            class="flex items-center justify-between gap-3"
                                        >
                                            <p class="font-bold">
                                                {{ exam.title }}
                                            </p>
                                            <span
                                                class="rounded-full px-2 py-1 text-xs font-black"
                                                :class="
                                                    exam.canStart
                                                        ? 'bg-powerx-success/10 text-powerx-success'
                                                        : 'bg-muted text-muted-foreground'
                                                "
                                            >
                                                {{
                                                    exam.canStart
                                                        ? 'Ready'
                                                        : 'Locked'
                                                }}
                                            </span>
                                        </div>
                                        <p
                                            class="mt-2 text-sm text-muted-foreground"
                                        >
                                            {{ exam.attemptsUsed }} /
                                            {{ exam.maxAttempts }} attempts ·
                                            best {{ exam.bestScore ?? 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                            </section>

                            <span
                                v-if="
                                    enrollment.id ===
                                    firstCertificateEnrollmentId
                                "
                                id="student-certificates"
                                class="scroll-mt-6"
                            />
                            <section
                                :id="`enrollment-${enrollment.id}-certificates`"
                                class="rounded-2xl border border-border p-5"
                            >
                                <div class="flex items-center gap-3">
                                    <Award class="size-6 text-powerx-success" />
                                    <h3 class="text-lg font-black">
                                        Certificates
                                    </h3>
                                </div>
                                <div class="mt-4 grid gap-3">
                                    <div
                                        v-for="certificate in enrollment.certificates"
                                        :key="certificate.id"
                                        class="rounded-xl bg-muted/50 p-3"
                                    >
                                        <p class="font-bold">
                                            {{ certificate.certificateNumber }}
                                        </p>
                                        <p
                                            class="mt-1 text-sm text-muted-foreground"
                                        >
                                            {{ label(certificate.status) }} ·
                                            issued
                                            {{
                                                dateLabel(certificate.issuedAt)
                                            }}
                                        </p>
                                        <Link
                                            :href="certificate.verifyUrl"
                                            class="mt-2 inline-flex text-sm font-black text-powerx-yellow hover:underline"
                                        >
                                            Verify certificate
                                        </Link>
                                    </div>
                                    <p
                                        v-if="
                                            enrollment.certificates.length === 0
                                        "
                                        class="text-sm text-muted-foreground"
                                    >
                                        Certificates appear after eligibility is
                                        confirmed.
                                    </p>
                                </div>
                            </section>

                            <span
                                v-if="
                                    enrollment.id === firstFinanceEnrollmentId
                                "
                                id="student-payments"
                                class="scroll-mt-6"
                            />
                            <section
                                :id="`enrollment-${enrollment.id}-finance`"
                                class="rounded-2xl border border-border p-5"
                            >
                                <div class="flex items-center gap-3">
                                    <Banknote
                                        class="size-6 text-powerx-yellow"
                                    />
                                    <h3 class="text-lg font-black">
                                        Finance status
                                    </h3>
                                </div>
                                <div class="mt-4 grid gap-3">
                                    <div
                                        v-for="invoice in enrollment.finance
                                            .invoices"
                                        :key="`invoice-${invoice.id}`"
                                        class="rounded-xl bg-muted/50 p-3"
                                    >
                                        <p class="font-bold">
                                            {{ invoice.number }}
                                        </p>
                                        <p
                                            class="mt-1 text-sm text-muted-foreground"
                                        >
                                            {{ label(invoice.status) }} ·
                                            {{
                                                money(
                                                    invoice.total,
                                                    invoice.currency,
                                                )
                                            }}
                                        </p>
                                    </div>
                                    <div
                                        v-for="payment in enrollment.finance
                                            .payments"
                                        :key="`payment-${payment.id}`"
                                        class="rounded-xl bg-muted/50 p-3"
                                    >
                                        <p class="font-bold">
                                            {{ label(payment.method) }}
                                        </p>
                                        <p
                                            class="mt-1 text-sm text-muted-foreground"
                                        >
                                            {{ label(payment.status) }} ·
                                            {{
                                                money(
                                                    payment.amount,
                                                    payment.currency,
                                                )
                                            }}
                                        </p>
                                    </div>
                                    <p
                                        v-if="
                                            enrollment.finance.invoices
                                                .length === 0 &&
                                            enrollment.finance.payments
                                                .length === 0
                                        "
                                        class="text-sm text-muted-foreground"
                                    >
                                        No invoice or payment record is linked
                                        yet. PowerX admissions will update this
                                        section after review.
                                    </p>
                                </div>
                            </section>
                        </aside>
                    </div>
                </article>
            </section>
        </template>
    </div>
</template>
