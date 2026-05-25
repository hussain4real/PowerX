<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    BadgeCheck,
    BarChart3,
    BookOpenCheck,
    CalendarCheck,
    CheckCircle2,
    FileText,
    GraduationCap,
    PlayCircle,
    UsersRound,
} from 'lucide-vue-next';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import BrandStatusBadge from '@/components/powerx/BrandStatusBadge.vue';
import CourseModuleCard from '@/components/powerx/CourseModuleCard.vue';
import DocumentCard from '@/components/powerx/DocumentCard.vue';
import MetricCard from '@/components/powerx/MetricCard.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { home } from '@/routes';
import { index as coursesIndex } from '@/routes/courses';

const palette = [
    {
        name: 'PowerX ink',
        token: 'powerx-ink',
        value: '#020101',
        class: 'bg-powerx-ink',
    },
    {
        name: 'PowerX navy',
        token: 'powerx-navy',
        value: '#071523',
        class: 'bg-powerx-navy',
    },
    {
        name: 'Panel navy',
        token: 'powerx-panel',
        value: '#0B2034',
        class: 'bg-powerx-panel',
    },
    {
        name: 'Electric yellow',
        token: 'powerx-yellow',
        value: '#FFC107',
        class: 'bg-powerx-yellow',
    },
    {
        name: 'Success green',
        token: 'powerx-success',
        value: '#2DB921',
        class: 'bg-powerx-success',
    },
    {
        name: 'Course blue',
        token: 'powerx-blue',
        value: '#006BFF',
        class: 'bg-powerx-blue',
    },
    {
        name: 'Signal cyan',
        token: 'powerx-cyan',
        value: '#0DCAF0',
        class: 'bg-powerx-cyan',
    },
    {
        name: 'Surface',
        token: 'powerx-surface',
        value: '#F4F6F8',
        class: 'bg-powerx-surface',
    },
] as const;

const dashboardRows = [
    ['New leads', '124', '+18%', 'Ads and WhatsApp traffic'],
    ['Payment pending', '37', 'QAR 41.2k', 'Manual approval queue'],
    ['Exam pass rate', '91%', '+6%', 'Kahramaa mock exams'],
    ['Certificates issued', '58', '12 renewals', 'Public verification ready'],
] as const;
</script>

<template>
    <Head title="PowerX Style Guide" />

    <main class="min-h-screen bg-powerx-ink text-white">
        <section
            class="relative isolate overflow-hidden border-b border-white/10"
        >
            <div
                class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_20%_18%,rgba(255,193,7,0.22),transparent_24%),radial-gradient(circle_at_84%_18%,rgba(0,107,255,0.20),transparent_28%),linear-gradient(135deg,#020101_0%,#071523_48%,#0b2034_100%)]"
            />
            <div
                class="absolute inset-0 -z-10 [background-image:linear-gradient(rgba(255,255,255,0.08)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.08)_1px,transparent_1px)] [background-size:64px_64px] opacity-25"
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
                            Brand system
                        </span>
                    </span>
                </Link>

                <div class="flex items-center gap-3">
                    <Link
                        :href="home()"
                        class="rounded-full border border-white/15 px-4 py-2 text-sm font-bold text-white/80 transition hover:border-powerx-yellow hover:text-powerx-yellow"
                    >
                        Home
                    </Link>
                    <Link
                        :href="coursesIndex()"
                        class="rounded-full bg-powerx-yellow px-5 py-2 text-sm font-black text-powerx-navy transition hover:bg-white"
                    >
                        Register
                    </Link>
                </div>
            </header>

            <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-24">
                <BrandStatusBadge label="Style guide first" tone="warning" />
                <h1
                    class="mt-6 max-w-5xl text-5xl leading-[0.95] font-black tracking-tight uppercase sm:text-6xl lg:text-7xl"
                >
                    Dark-first electrical training UI for PowerX.
                </h1>
                <p class="mt-6 max-w-3xl text-lg leading-8 text-white/70">
                    The product interface should feel like the course promise:
                    practical, technical, high-energy, and clear enough for
                    mobile prospects, students, instructors, finance, and
                    management.
                </p>
            </div>
        </section>

        <section class="mx-auto grid max-w-7xl gap-8 px-6 py-14 lg:px-8">
            <div>
                <p
                    class="text-sm font-black tracking-[0.28em] text-powerx-yellow uppercase"
                >
                    01 / Color system
                </p>
                <h2 class="mt-3 text-3xl font-black">
                    Tokens from the brand references.
                </h2>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <article
                    v-for="color in palette"
                    :key="color.token"
                    class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.04]"
                >
                    <div :class="['h-28', color.class]" />
                    <div class="p-4">
                        <h3 class="font-black">{{ color.name }}</h3>
                        <p class="mt-1 text-sm text-white/55">
                            {{ color.token }} / {{ color.value }}
                        </p>
                    </div>
                </article>
            </div>
        </section>

        <section class="border-y border-white/10 bg-powerx-navy">
            <div
                class="mx-auto grid max-w-7xl gap-8 px-6 py-14 lg:grid-cols-[0.72fr_1.28fr] lg:px-8"
            >
                <div>
                    <p
                        class="text-sm font-black tracking-[0.28em] text-powerx-yellow uppercase"
                    >
                        02 / Typography
                    </p>
                    <h2 class="mt-3 text-3xl font-black">
                        Big claims, compact labels, readable operations.
                    </h2>
                </div>

                <div class="grid gap-5">
                    <div
                        class="rounded-3xl border border-white/10 bg-white/[0.04] p-6"
                    >
                        <p
                            class="text-xs font-black tracking-[0.3em] text-powerx-yellow uppercase"
                        >
                            Marketing headline
                        </p>
                        <p
                            class="mt-4 text-5xl leading-none font-black uppercase"
                        >
                            Pass Kahramaa exam in 7 days
                        </p>
                    </div>
                    <div
                        class="rounded-3xl border border-white/10 bg-white/[0.04] p-6"
                    >
                        <p
                            class="text-xs font-black tracking-[0.3em] text-powerx-yellow uppercase"
                        >
                            Product copy
                        </p>
                        <p
                            class="mt-4 max-w-3xl text-lg leading-8 text-white/70"
                        >
                            Students should understand the next step quickly:
                            enroll, upload payment proof, continue learning,
                            take mock exams, and download a certificate only
                            after eligibility rules pass.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto grid max-w-7xl gap-8 px-6 py-14 lg:px-8">
            <div>
                <p
                    class="text-sm font-black tracking-[0.28em] text-powerx-yellow uppercase"
                >
                    03 / Controls and states
                </p>
                <h2 class="mt-3 text-3xl font-black">
                    CTAs use yellow, status uses semantic signal colors.
                </h2>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <Card class="border-white/10 bg-white/[0.04] text-white">
                    <CardHeader>
                        <CardTitle>Buttons</CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-wrap gap-3">
                        <Button>Primary action</Button>
                        <Button variant="secondary">Navy action</Button>
                        <Button
                            variant="outline"
                            class="border-white/15 bg-transparent text-white hover:bg-white/10 hover:text-white"
                        >
                            Outline action
                        </Button>
                    </CardContent>
                </Card>

                <Card class="border-white/10 bg-white/[0.04] text-white">
                    <CardHeader>
                        <CardTitle>Status badges</CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-wrap gap-3">
                        <BrandStatusBadge
                            label="Payment approved"
                            tone="success"
                        />
                        <BrandStatusBadge
                            label="Follow-up due"
                            tone="warning"
                        />
                        <BrandStatusBadge label="Live course" tone="info" />
                        <BrandStatusBadge
                            label="Verified access"
                            tone="neutral"
                        />
                    </CardContent>
                </Card>
            </div>
        </section>

        <section class="border-y border-white/10 bg-powerx-navy">
            <div class="mx-auto grid max-w-7xl gap-8 px-6 py-14 lg:px-8">
                <div>
                    <p
                        class="text-sm font-black tracking-[0.28em] text-powerx-yellow uppercase"
                    >
                        04 / Product cards
                    </p>
                    <h2 class="mt-3 text-3xl font-black">
                        Reusable blocks for courses, documents, and dashboards.
                    </h2>
                </div>

                <div class="grid gap-5 lg:grid-cols-3">
                    <MetricCard
                        label="Weekly revenue"
                        value="QAR 25k"
                        detail="Management target placeholder until PowerX confirms reporting targets."
                    >
                        <template #icon>
                            <BarChart3 class="size-5" />
                        </template>
                    </MetricCard>
                    <MetricCard
                        label="Enrollments"
                        value="238"
                        detail="Course registrations across online, workshop, and corporate tracks."
                    >
                        <template #icon>
                            <UsersRound class="size-5" />
                        </template>
                    </MetricCard>
                    <MetricCard
                        label="Certificates"
                        value="58"
                        detail="Issued certificates with PDF file, number, and verification URL."
                    >
                        <template #icon>
                            <BadgeCheck class="size-5" />
                        </template>
                    </MetricCard>
                </div>

                <div class="grid gap-5 lg:grid-cols-2">
                    <div class="grid gap-3">
                        <CourseModuleCard
                            index="01"
                            title="Kahramaa rules and standards"
                            description="Regulations, safety requirements, approval process, and common mistakes."
                            status="complete"
                        />
                        <CourseModuleCard
                            index="02"
                            title="Electrical fundamentals"
                            description="Load calculation, cable sizing, and protection systems."
                            status="current"
                        />
                        <CourseModuleCard
                            index="03"
                            title="Past questions and exam prep"
                            description="Mock tests, answer history, and pass-question practice."
                            status="locked"
                        />
                    </div>

                    <div class="grid gap-5">
                        <DocumentCard
                            title="Certificate of completion"
                            meta="Generated PDF"
                            description="Certificate number, QR verification URL, issue date, approver, and approved wording."
                        />
                        <DocumentCard
                            title="Invoice and receipt"
                            meta="Finance PDF"
                            description="Student or company billing document with payment method, amount, status, and approver."
                        />
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto grid max-w-7xl gap-8 px-6 py-14 lg:px-8">
            <div>
                <p
                    class="text-sm font-black tracking-[0.28em] text-powerx-yellow uppercase"
                >
                    05 / Operations dashboard
                </p>
                <h2 class="mt-3 text-3xl font-black">
                    Dense enough for staff, clear enough for decisions.
                </h2>
            </div>

            <div
                class="overflow-hidden rounded-3xl border border-white/10 bg-white/[0.04] shadow-[0_36px_100px_rgba(0,0,0,0.35)]"
            >
                <div
                    class="grid gap-4 border-b border-white/10 bg-powerx-panel p-5 md:grid-cols-4"
                >
                    <div class="rounded-2xl bg-white/[0.05] p-4">
                        <PlayCircle class="size-6 text-powerx-yellow" />
                        <p class="mt-3 text-2xl font-black">12</p>
                        <p class="text-sm text-white/55">Live courses</p>
                    </div>
                    <div class="rounded-2xl bg-white/[0.05] p-4">
                        <CalendarCheck class="size-6 text-powerx-yellow" />
                        <p class="mt-3 text-2xl font-black">8</p>
                        <p class="text-sm text-white/55">Active batches</p>
                    </div>
                    <div class="rounded-2xl bg-white/[0.05] p-4">
                        <BookOpenCheck class="size-6 text-powerx-yellow" />
                        <p class="mt-3 text-2xl font-black">1,420</p>
                        <p class="text-sm text-white/55">Lesson completions</p>
                    </div>
                    <div class="rounded-2xl bg-white/[0.05] p-4">
                        <GraduationCap class="size-6 text-powerx-yellow" />
                        <p class="mt-3 text-2xl font-black">91%</p>
                        <p class="text-sm text-white/55">Mock pass rate</p>
                    </div>
                </div>

                <div class="divide-y divide-white/10">
                    <div
                        v-for="row in dashboardRows"
                        :key="row[0]"
                        class="grid gap-3 p-5 text-sm md:grid-cols-[1fr_0.5fr_0.5fr_1.4fr]"
                    >
                        <span class="font-bold text-white">{{ row[0] }}</span>
                        <span class="font-black text-powerx-yellow">{{
                            row[1]
                        }}</span>
                        <span class="text-powerx-success">{{ row[2] }}</span>
                        <span class="text-white/60">{{ row[3] }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-t border-white/10 bg-powerx-navy">
            <div
                class="mx-auto grid max-w-7xl gap-8 px-6 py-14 lg:grid-cols-2 lg:px-8"
            >
                <div>
                    <p
                        class="text-sm font-black tracking-[0.28em] text-powerx-yellow uppercase"
                    >
                        06 / Certificate preview
                    </p>
                    <h2 class="mt-3 text-3xl font-black">
                        Formal documents stay clean but keep brand authority.
                    </h2>
                    <p class="mt-4 text-white/65">
                        Certificate wording must remain configurable and legally
                        approved before production.
                    </p>
                </div>

                <div
                    class="rounded-[2rem] border border-powerx-yellow/35 bg-white p-8 text-powerx-navy shadow-[0_36px_100px_rgba(0,0,0,0.35)]"
                >
                    <div class="flex items-center justify-between gap-5">
                        <div class="flex items-center gap-3">
                            <span
                                class="flex size-12 items-center justify-center rounded-2xl bg-powerx-yellow text-powerx-navy"
                            >
                                <AppLogoIcon class="size-8" />
                            </span>
                            <div>
                                <p class="text-xl font-black">
                                    Power<span class="text-powerx-gold">X</span>
                                </p>
                                <p
                                    class="text-[10px] font-black tracking-[0.24em] text-powerx-steel uppercase"
                                >
                                    Training Center
                                </p>
                            </div>
                        </div>
                        <FileText class="size-8 text-powerx-gold" />
                    </div>

                    <div class="my-8 h-px bg-powerx-navy/15" />

                    <p
                        class="text-xs font-black tracking-[0.34em] text-powerx-steel uppercase"
                    >
                        Certificate of completion
                    </p>
                    <h3 class="mt-4 text-4xl font-black">
                        Kahramaa Exam Preparation
                    </h3>
                    <p class="mt-4 leading-7 text-powerx-steel">
                        Awarded after payment, attendance, course progress,
                        exam, and practical assessment rules are satisfied.
                    </p>

                    <div class="mt-8 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl bg-powerx-surface p-4">
                            <p class="text-xs font-bold text-powerx-steel">
                                Certificate no.
                            </p>
                            <p class="mt-1 font-black">PX-2026-0001</p>
                        </div>
                        <div class="rounded-2xl bg-powerx-surface p-4">
                            <p class="text-xs font-bold text-powerx-steel">
                                Verification
                            </p>
                            <p class="mt-1 flex items-center gap-2 font-black">
                                <CheckCircle2
                                    class="size-4 text-powerx-success"
                                />
                                Public QR ready
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</template>
