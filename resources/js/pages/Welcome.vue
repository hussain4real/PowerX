<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    BadgeCheck,
    BookOpenCheck,
    Building2,
    CalendarCheck,
    CheckCircle2,
    ClipboardCheck,
    FileText,
    GraduationCap,
    MessageCircle,
    PlayCircle,
    ShieldCheck,
    Target,
    UsersRound,
    Wrench,
    Zap,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import BrandStatusBadge from '@/components/powerx/BrandStatusBadge.vue';
import CourseModuleCard from '@/components/powerx/CourseModuleCard.vue';
import MetricCard from '@/components/powerx/MetricCard.vue';
import { dashboard, home, login, styleGuide } from '@/routes';
import { create as corporateQuotationCreate } from '@/routes/corporate/quotations';
import { index as coursesIndex } from '@/routes/courses';

const page = usePage();

const dashboardUrl = computed(() =>
    page.props.currentTeam ? dashboard(page.props.currentTeam.slug).url : '/',
);

const courseModules = [
    {
        index: '01',
        title: 'Kahramaa rules and standards',
        description:
            'Regulations, safety requirements, approval flow, and exam-ready revision prompts.',
        status: 'complete',
    },
    {
        index: '02',
        title: 'Electrical fundamentals',
        description:
            'Load calculation, cable sizing, protection systems, and drawing practice.',
        status: 'current',
    },
    {
        index: '03',
        title: 'Mock exam practice',
        description:
            'Timed questions, answer explanations, weak-topic review, and attempt history.',
        status: 'current',
    },
    {
        index: '04',
        title: 'Practical workshop support',
        description:
            'Hands-on demonstrations, attendance records, and instructor assessment comments.',
        status: 'locked',
    },
] as const;

const featureCards = [
    {
        icon: FileText,
        title: 'PDF notes and references',
        description:
            'Curated notes, checklists, standards reminders, and downloadable study packs.',
    },
    {
        icon: PlayCircle,
        title: 'Practical field videos',
        description:
            'Step-by-step demonstrations for site-ready electrical techniques and safety routines.',
    },
    {
        icon: GraduationCap,
        title: 'Mock exams',
        description:
            'Timed practice, pass marks, attempt tracking, and reviewed answer explanations.',
    },
    {
        icon: Wrench,
        title: 'Hands-on workshops',
        description:
            'Blended and classroom sessions with attendance and practical assessment records.',
    },
    {
        icon: MessageCircle,
        title: 'Instructor support',
        description:
            'Follow-up through PowerX staff, reminders, and WhatsApp-ready communication drafts.',
    },
    {
        icon: BadgeCheck,
        title: 'PowerX completion records',
        description:
            'Eligibility-based PowerX certificates with safe public verification links.',
    },
] as const;

const programAreas = [
    {
        icon: Zap,
        title: 'Kahramaa exam preparation',
        description:
            'Focused preparation for engineers, technicians, and supervisors working in Qatar.',
    },
    {
        icon: ShieldCheck,
        title: 'Electrical safety and compliance',
        description:
            'Isolation, safe testing, site controls, practical assessment, and refresher training.',
    },
    {
        icon: Wrench,
        title: 'Cable jointing workshops',
        description:
            'Practical lab sessions for technicians who need structured instructor feedback.',
    },
    {
        icon: BookOpenCheck,
        title: 'Power distribution design',
        description:
            'Load schedules, voltage drop, cable sizing, protection, and drawing review.',
    },
    {
        icon: ClipboardCheck,
        title: 'QCDD-related awareness',
        description:
            'Safety and compliance-oriented training content without implying government issuance.',
    },
    {
        icon: Building2,
        title: 'Corporate team training',
        description:
            'Quotation, bulk enrollment, attendance, completion records, and company reporting.',
    },
] as const;

const workflowSteps = [
    'Choose a course or request a corporate track',
    'Submit an inquiry or registration',
    'Confirm admission and manual payment approval',
    'Access previews, paid lessons, PDFs, and videos',
    'Attend practical sessions and complete mock exams',
    'Receive eligible PowerX completion and verification records',
] as const;

const testimonials = [
    {
        quote:
            'The course helped me connect regulations, drawings, and practical site questions in one place.',
        name: 'A. Rahman',
        role: 'Electrical supervisor',
    },
    {
        quote:
            'Our technicians had a clear schedule, attendance records, and practical feedback after the workshop.',
        name: 'N. Coordinator',
        role: 'MEP training coordinator',
    },
    {
        quote:
            'The mock exams and notes gave our team a focused way to revise before assessment week.',
        name: 'B. Khan',
        role: 'Electrical technician',
    },
] as const;
</script>

<template>
    <Head title="PowerX Training Center" />

    <main class="min-h-screen bg-powerx-ink text-white">
        <section class="relative isolate overflow-hidden">
            <div
                class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_20%_20%,rgba(255,193,7,0.20),transparent_28%),radial-gradient(circle_at_78%_14%,rgba(0,107,255,0.18),transparent_24%),linear-gradient(135deg,#020101_0%,#071523_45%,#0b2034_100%)]"
            />
            <div
                class="absolute inset-0 -z-10 [background-image:linear-gradient(rgba(255,255,255,0.08)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.08)_1px,transparent_1px)] [background-size:72px_72px] opacity-30"
            />

            <header
                class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-6 py-6 lg:px-8"
            >
                <Link :href="home()" class="flex items-center gap-3">
                    <span
                        class="flex size-12 items-center justify-center rounded-2xl bg-powerx-yellow text-powerx-navy shadow-[0_0_34px_rgba(255,193,7,0.45)]"
                    >
                        <AppLogoIcon class="size-8 text-current" />
                    </span>
                    <span class="grid leading-none">
                        <span class="text-xl font-black tracking-wide">
                            POWER<span class="text-powerx-yellow">X</span>
                        </span>
                        <span
                            class="text-[11px] font-bold tracking-[0.28em] text-white/60 uppercase"
                        >
                            Training Center
                        </span>
                    </span>
                </Link>

                <nav class="flex items-center gap-3 text-sm font-semibold">
                    <Link
                        :href="styleGuide()"
                        class="hidden rounded-full border border-white/25 bg-white/[0.03] px-4 py-2 text-white/85 transition hover:border-powerx-yellow hover:text-powerx-yellow sm:inline-flex"
                    >
                        Style guide
                    </Link>
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboardUrl"
                        class="rounded-full bg-powerx-yellow px-5 py-2 text-powerx-navy shadow-[0_0_24px_rgba(255,193,7,0.35)] transition hover:bg-white"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-full border border-white/25 bg-white/[0.03] px-4 py-2 text-white/85 transition hover:border-white hover:text-white"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="coursesIndex()"
                            class="rounded-full bg-powerx-yellow px-5 py-2 text-powerx-navy shadow-[0_0_24px_rgba(255,193,7,0.35)] transition hover:bg-white"
                        >
                            Register
                        </Link>
                    </template>
                </nav>
            </header>

            <div
                class="mx-auto grid max-w-7xl gap-12 px-6 pt-10 pb-20 lg:grid-cols-[1.04fr_0.96fr] lg:px-8 lg:pt-20"
            >
                <div class="flex flex-col justify-center">
                    <div class="flex flex-wrap gap-3">
                        <BrandStatusBadge
                            label="Success-focused preparation"
                            tone="warning"
                        />
                        <BrandStatusBadge
                            label="Qatar electrical training"
                            tone="info"
                        />
                    </div>

                    <h1
                        class="mt-8 max-w-4xl text-5xl leading-[0.95] font-black tracking-tight text-white uppercase sm:text-6xl lg:text-7xl"
                    >
                        Prepare for Kahramaa exams with practical PowerX training.
                    </h1>

                    <p class="mt-6 max-w-2xl text-lg leading-8 text-white/70">
                        PowerX combines curated electrical exam preparation,
                        practical workshops, mock assessments, student access,
                        manual payment workflows, and safe completion records
                        for individuals and corporate teams.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <Link
                            :href="coursesIndex()"
                            class="inline-flex items-center justify-center gap-2 rounded-full bg-powerx-yellow px-6 py-3 text-sm font-black tracking-wide text-powerx-navy uppercase shadow-[0_0_36px_rgba(255,193,7,0.38)] transition hover:-translate-y-0.5 hover:bg-white"
                        >
                            Explore courses
                            <Zap class="size-4" />
                        </Link>
                        <Link
                            :href="corporateQuotationCreate()"
                            class="inline-flex items-center justify-center rounded-full border border-white/25 bg-white/[0.04] px-6 py-3 text-sm font-bold text-white/90 transition hover:border-powerx-yellow hover:bg-white/[0.08] hover:text-powerx-yellow"
                        >
                            Request corporate training
                        </Link>
                    </div>

                    <div class="mt-10 grid gap-4 sm:grid-cols-3">
                        <MetricCard
                            label="Learner path"
                            value="1 flow"
                            detail="Inquiry, registration, payment, learning, exam, and certificate."
                        >
                            <template #icon>
                                <UsersRound class="size-5" />
                            </template>
                        </MetricCard>
                        <MetricCard
                            label="Training modes"
                            value="3 types"
                            detail="Online prep, practical workshops, and corporate batches."
                        >
                            <template #icon>
                                <Building2 class="size-5" />
                            </template>
                        </MetricCard>
                        <MetricCard
                            label="Certificate checks"
                            value="Safe"
                            detail="Public verification shows only approved completion details."
                        >
                            <template #icon>
                                <ShieldCheck class="size-5" />
                            </template>
                        </MetricCard>
                    </div>
                </div>

                <div
                    class="relative rounded-[2rem] border border-white/10 bg-white/[0.06] p-4 shadow-[0_36px_100px_rgba(0,0,0,0.45)] backdrop-blur"
                >
                    <div
                        class="rounded-[1.5rem] border border-white/10 bg-powerx-navy p-5"
                    >
                        <div class="flex items-start justify-between gap-5">
                            <div>
                                <p
                                    class="text-xs font-black tracking-[0.24em] text-powerx-yellow uppercase"
                                >
                                    Featured pathway
                                </p>
                                <h2 class="mt-2 text-2xl font-black">
                                    Kahramaa Exam Preparation
                                </h2>
                                <p class="mt-1 text-sm text-white/60">
                                    Online prep + mock exams + practical support
                                </p>
                            </div>
                            <BrandStatusBadge label="Blended" tone="success" />
                        </div>

                        <div class="mt-6">
                            <div
                                class="flex justify-between text-xs font-bold text-white/60"
                            >
                                <span>Demo learner progress</span>
                                <span>72%</span>
                            </div>
                            <div class="mt-2 h-3 rounded-full bg-white/10">
                                <div
                                    class="h-full rounded-full bg-powerx-success shadow-[0_0_22px_rgba(45,185,33,0.5)]"
                                    style="width: 72%"
                                />
                            </div>
                        </div>

                        <div class="mt-6 grid gap-3">
                            <CourseModuleCard
                                v-for="module in courseModules"
                                :key="module.index"
                                :index="module.index"
                                :title="module.title"
                                :description="module.description"
                                :status="module.status"
                            />
                        </div>

                        <div class="mt-6 rounded-2xl bg-powerx-yellow p-4 text-powerx-navy">
                            <p
                                class="text-xs font-black tracking-[0.24em] uppercase"
                            >
                                Audience-safe promise
                            </p>
                            <p class="mt-2 text-sm font-bold leading-6">
                                PowerX markets practical preparation and
                                completion records without implying government
                                issuance or unapproved guarantees.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-t border-white/10 bg-powerx-navy py-16">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="grid gap-8 lg:grid-cols-[0.85fr_1.15fr]">
                    <div>
                        <p
                            class="text-sm font-black tracking-[0.28em] text-powerx-yellow uppercase"
                        >
                            What students get
                        </p>
                        <h2 class="mt-3 text-3xl font-black tracking-tight">
                            Complete preparation, practical learning, and clear records.
                        </h2>
                        <p class="mt-4 text-sm leading-7 text-white/60">
                            The homepage focuses on public-friendly training
                            value: notes, videos, mock questions, workshops,
                            support, and completion tracking. Internal CRM,
                            finance, audit, and reporting details stay behind
                            authenticated staff access.
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <article
                            v-for="feature in featureCards"
                            :key="feature.title"
                            class="rounded-2xl border border-white/10 bg-white/[0.04] p-5"
                        >
                            <component
                                :is="feature.icon"
                                class="size-8 text-powerx-yellow"
                            />
                            <h3 class="mt-4 font-black">
                                {{ feature.title }}
                            </h3>
                            <p class="mt-2 text-sm leading-6 text-white/60">
                                {{ feature.description }}
                            </p>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-t border-white/10 bg-powerx-ink py-16">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div
                    class="flex flex-col justify-between gap-6 md:flex-row md:items-end"
                >
                    <div>
                        <p
                            class="text-sm font-black tracking-[0.28em] text-powerx-yellow uppercase"
                        >
                            Programs
                        </p>
                        <h2 class="mt-3 max-w-3xl text-3xl font-black tracking-tight">
                            Training areas for Qatar engineers, technicians, supervisors, and companies.
                        </h2>
                    </div>
                    <Link
                        :href="coursesIndex()"
                        class="inline-flex items-center justify-center rounded-full border border-white/25 bg-white/[0.04] px-5 py-3 text-sm font-bold text-white/90 transition hover:border-powerx-yellow hover:bg-white/[0.08] hover:text-powerx-yellow"
                    >
                        View all courses
                    </Link>
                </div>

                <div class="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="program in programAreas"
                        :key="program.title"
                        class="group rounded-3xl border border-white/10 bg-white/[0.04] p-6 transition hover:-translate-y-1 hover:border-powerx-yellow/70 hover:bg-white/[0.07]"
                    >
                        <component
                            :is="program.icon"
                            class="size-9 text-powerx-yellow"
                        />
                        <h3 class="mt-5 text-xl font-black">
                            {{ program.title }}
                        </h3>
                        <p class="mt-3 text-sm leading-6 text-white/60">
                            {{ program.description }}
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section class="border-t border-white/10 bg-powerx-navy py-16">
            <div
                class="mx-auto grid max-w-7xl gap-10 px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8"
            >
                <div>
                    <p
                        class="text-sm font-black tracking-[0.28em] text-powerx-yellow uppercase"
                    >
                        How it works
                    </p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight">
                        A clear path from inquiry to eligible completion record.
                    </h2>
                    <p class="mt-4 text-sm leading-7 text-white/60">
                        PowerX can support individual learners and company
                        cohorts without exposing staff-only operational details
                        to the public website.
                    </p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <Link
                            :href="coursesIndex()"
                            class="inline-flex items-center justify-center rounded-full bg-powerx-yellow px-5 py-3 text-sm font-black text-powerx-navy transition hover:bg-white"
                        >
                            Start as a student
                        </Link>
                        <Link
                            :href="corporateQuotationCreate()"
                            class="inline-flex items-center justify-center rounded-full border border-white/25 bg-white/[0.04] px-5 py-3 text-sm font-bold text-white/90 transition hover:border-powerx-yellow hover:bg-white/[0.08] hover:text-powerx-yellow"
                        >
                            Plan a company batch
                        </Link>
                    </div>
                </div>

                <div class="grid gap-3">
                    <div
                        v-for="(step, index) in workflowSteps"
                        :key="step"
                        class="flex gap-4 rounded-2xl border border-white/10 bg-white/[0.04] p-4"
                    >
                        <span
                            class="flex size-10 shrink-0 items-center justify-center rounded-full bg-powerx-yellow text-sm font-black text-powerx-navy"
                        >
                            {{ index + 1 }}
                        </span>
                        <div class="flex items-center gap-3">
                            <CheckCircle2 class="size-5 text-powerx-success" />
                            <p class="text-sm font-bold text-white/80">
                                {{ step }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-t border-white/10 bg-powerx-ink py-16">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="grid gap-8 lg:grid-cols-3">
                    <div>
                        <p
                            class="text-sm font-black tracking-[0.28em] text-powerx-yellow uppercase"
                        >
                            Why PowerX
                        </p>
                        <h2 class="mt-3 text-3xl font-black tracking-tight">
                            Practical, Qatar-focused, and built for serious technical teams.
                        </h2>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 lg:col-span-2">
                        <div
                            class="rounded-2xl border border-white/10 bg-white/[0.04] p-5"
                        >
                            <Target class="size-8 text-powerx-yellow" />
                            <h3 class="mt-4 font-black">
                                Exam-focused learning
                            </h3>
                            <p class="mt-2 text-sm leading-6 text-white/60">
                                Structure revision around standards, common
                                mistakes, drawings, and timed question practice.
                            </p>
                        </div>
                        <div
                            class="rounded-2xl border border-white/10 bg-white/[0.04] p-5"
                        >
                            <CalendarCheck class="size-8 text-powerx-yellow" />
                            <h3 class="mt-4 font-black">
                                Batches and attendance
                            </h3>
                            <p class="mt-2 text-sm leading-6 text-white/60">
                                Support classroom schedules, practical sessions,
                                instructors, capacity, and assessment comments.
                            </p>
                        </div>
                        <div
                            class="rounded-2xl border border-white/10 bg-white/[0.04] p-5"
                        >
                            <Building2 class="size-8 text-powerx-yellow" />
                            <h3 class="mt-4 font-black">
                                Corporate coordination
                            </h3>
                            <p class="mt-2 text-sm leading-6 text-white/60">
                                Prepare quotations, group enrollments,
                                attendance summaries, and completion reports.
                            </p>
                        </div>
                        <div
                            class="rounded-2xl border border-white/10 bg-white/[0.04] p-5"
                        >
                            <BadgeCheck class="size-8 text-powerx-yellow" />
                            <h3 class="mt-4 font-black">
                                Safe verification
                            </h3>
                            <p class="mt-2 text-sm leading-6 text-white/60">
                                Public verification exposes only approved
                                PowerX completion fields, not private records.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-10 grid gap-4 lg:grid-cols-3">
                    <article
                        v-for="testimonial in testimonials"
                        :key="testimonial.name"
                        class="rounded-3xl border border-white/10 bg-white/[0.04] p-6"
                    >
                        <p class="text-sm leading-7 text-white/70">
                            "{{ testimonial.quote }}"
                        </p>
                        <div class="mt-5">
                            <p class="font-black text-white">
                                {{ testimonial.name }}
                            </p>
                            <p class="text-xs font-bold tracking-wide text-powerx-yellow uppercase">
                                {{ testimonial.role }}
                            </p>
                        </div>
                    </article>
                </div>
            </div>
        </section>
    </main>
</template>
