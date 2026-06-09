<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    BadgeCheck,
    BookOpenCheck,
    Building2,
    CheckCircle2,
    FileText,
    GraduationCap,
    PlayCircle,
    ShieldCheck,
    UsersRound,
} from 'lucide-vue-next';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import BrandStatusBadge from '@/components/powerx/BrandStatusBadge.vue';
import PublicThemeSwitcher from '@/components/powerx/PublicThemeSwitcher.vue';
import { Button } from '@/components/ui/button';
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
        name: 'Soft surface',
        token: 'powerx-soft',
        value: 'theme-aware',
        class: 'bg-powerx-soft',
    },
] as const;

const themePanels = [
    {
        mode: 'Light variant',
        class: '',
        eyebrow: 'Bright admissions surface',
        headline: 'Technical training without a heavy interface.',
        copy: 'Use light mode for prospects who need fast scanning, forms, and catalog comparison.',
    },
    {
        mode: 'Dark variant',
        class: 'dark',
        eyebrow: 'PowerX command surface',
        headline: 'Navy, yellow, and precise technical contrast.',
        copy: 'Use dark mode where the brand needs authority, focus, and a high-energy training feel.',
    },
] as const;

const productCards = [
    {
        icon: BookOpenCheck,
        title: 'Course card',
        detail: 'Category, delivery mode, modules, price, and a direct route into registration.',
    },
    {
        icon: UsersRound,
        title: 'Corporate pathway',
        detail: 'Quotation, employee list, batch planning, attendance, and completion summaries.',
    },
    {
        icon: BadgeCheck,
        title: 'Completion record',
        detail: 'Eligibility-based PowerX certificate with privacy-aware public verification.',
    },
] as const;
</script>

<template>
    <Head title="PowerX Style Guide" />

    <main class="min-h-screen bg-background text-foreground">
        <section class="border-b border-border bg-card">
            <header
                class="mx-auto flex max-w-7xl items-center justify-between gap-2 px-4 py-5 sm:gap-5 sm:px-6 lg:px-8"
            >
                <Link :href="home()" class="flex items-center gap-3">
                    <span
                        class="flex size-10 items-center justify-center overflow-hidden rounded-xl bg-white p-0.5 sm:size-11"
                    >
                        <AppLogoIcon class="size-full" />
                    </span>
                    <span class="grid leading-none">
                        <span class="text-lg font-black tracking-wide">
                            POWER<span class="text-powerx-gold">X</span>
                        </span>
                        <span
                            class="text-[10px] font-bold tracking-[0.24em] text-muted-foreground uppercase"
                        >
                            Brand system
                        </span>
                    </span>
                </Link>

                <div class="flex items-center gap-3">
                    <PublicThemeSwitcher />
                    <Link
                        :href="home()"
                        class="hidden rounded-full border border-border bg-background px-4 py-2 text-sm font-bold transition hover:border-powerx-yellow sm:inline-flex"
                    >
                        Home
                    </Link>
                    <Link
                        :href="coursesIndex()"
                        class="rounded-full bg-powerx-yellow px-5 py-2 text-sm font-black text-powerx-navy transition hover:bg-powerx-gold"
                    >
                        Register
                    </Link>
                </div>
            </header>

            <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">
                <BrandStatusBadge label="Theme switcher ready" tone="warning" />
                <h1
                    class="mt-6 max-w-5xl text-4xl leading-[0.98] font-black uppercase sm:text-6xl lg:text-7xl"
                >
                    PowerX design system for light and dark public surfaces.
                </h1>
                <p
                    class="mt-6 max-w-3xl text-lg leading-8 text-muted-foreground"
                >
                    The public website should feel practical, technical,
                    polished, and conversion-ready without relying on unsafe
                    claims or generic training templates.
                </p>
            </div>
        </section>

        <section class="mx-auto grid max-w-7xl gap-8 px-6 py-14 lg:px-8">
            <div>
                <p
                    class="text-sm font-black tracking-[0.26em] text-powerx-gold uppercase dark:text-powerx-yellow"
                >
                    01 / Color system
                </p>
                <h2 class="mt-3 text-3xl font-black">
                    Brand tokens stay stable; semantic surfaces adapt.
                </h2>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <article
                    v-for="color in palette"
                    :key="color.token"
                    class="overflow-hidden rounded-2xl border border-border bg-card"
                >
                    <div :class="['h-24', color.class]" />
                    <div class="p-4">
                        <h3 class="font-black">{{ color.name }}</h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ color.token }} / {{ color.value }}
                        </p>
                    </div>
                </article>
            </div>

            <div class="grid gap-5 lg:grid-cols-2">
                <article
                    v-for="panel in themePanels"
                    :key="panel.mode"
                    :class="[
                        panel.class,
                        'rounded-2xl border border-border bg-background p-6 text-foreground shadow-sm',
                    ]"
                >
                    <p
                        class="text-xs font-black tracking-[0.28em] text-powerx-gold uppercase dark:text-powerx-yellow"
                    >
                        {{ panel.mode }}
                    </p>
                    <div
                        class="mt-5 rounded-2xl border border-border bg-card p-5"
                    >
                        <p class="text-sm font-black text-muted-foreground">
                            {{ panel.eyebrow }}
                        </p>
                        <h3 class="mt-3 text-3xl leading-tight font-black">
                            {{ panel.headline }}
                        </h3>
                        <p class="mt-3 text-sm leading-7 text-muted-foreground">
                            {{ panel.copy }}
                        </p>
                        <div class="mt-5 flex flex-wrap gap-3">
                            <Button
                                class="rounded-full bg-powerx-yellow font-black text-powerx-navy hover:bg-powerx-gold"
                            >
                                Primary
                            </Button>
                            <Button variant="outline" class="rounded-full">
                                Secondary
                            </Button>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <section class="border-y border-border bg-card py-14">
            <div
                class="mx-auto grid max-w-7xl gap-8 px-6 lg:grid-cols-[0.78fr_1.22fr] lg:px-8"
            >
                <div>
                    <p
                        class="text-sm font-black tracking-[0.26em] text-powerx-gold uppercase dark:text-powerx-yellow"
                    >
                        02 / Typography
                    </p>
                    <h2 class="mt-3 text-3xl font-black">
                        Compact labels, strong headlines, readable forms.
                    </h2>
                </div>

                <div class="grid gap-5">
                    <div
                        class="rounded-2xl border border-border bg-background p-6"
                    >
                        <p
                            class="text-xs font-black tracking-[0.28em] text-powerx-gold uppercase dark:text-powerx-yellow"
                        >
                            Marketing headline
                        </p>
                        <p
                            class="mt-4 text-4xl leading-none font-black uppercase sm:text-5xl"
                        >
                            Practical electrical training for Qatar teams
                        </p>
                    </div>
                    <div
                        class="rounded-2xl border border-border bg-background p-6"
                    >
                        <p
                            class="text-xs font-black tracking-[0.28em] text-powerx-gold uppercase dark:text-powerx-yellow"
                        >
                            Product copy
                        </p>
                        <p
                            class="mt-4 max-w-3xl text-lg leading-8 text-muted-foreground"
                        >
                            Learners should understand the next step quickly:
                            explore courses, request a callback, confirm
                            admissions, study, attend workshops, and receive
                            eligible PowerX completion records.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto grid max-w-7xl gap-8 px-6 py-14 lg:px-8">
            <div>
                <p
                    class="text-sm font-black tracking-[0.26em] text-powerx-gold uppercase dark:text-powerx-yellow"
                >
                    03 / Components
                </p>
                <h2 class="mt-3 text-3xl font-black">
                    Landing page blocks should stay direct and operational.
                </h2>
            </div>

            <div class="grid gap-5 lg:grid-cols-3">
                <article
                    v-for="card in productCards"
                    :key="card.title"
                    class="rounded-2xl border border-border bg-card p-5 shadow-sm"
                >
                    <component
                        :is="card.icon"
                        class="size-9 text-powerx-gold dark:text-powerx-yellow"
                    />
                    <h3 class="mt-5 text-xl font-black">{{ card.title }}</h3>
                    <p class="mt-3 text-sm leading-6 text-muted-foreground">
                        {{ card.detail }}
                    </p>
                </article>
            </div>

            <div class="grid gap-5 lg:grid-cols-2">
                <div
                    class="rounded-2xl border border-border bg-card p-6 shadow-sm"
                >
                    <p
                        class="text-xs font-black tracking-[0.28em] text-powerx-gold uppercase dark:text-powerx-yellow"
                    >
                        Lead form
                    </p>
                    <div class="mt-5 grid gap-4">
                        <input
                            class="h-12 rounded-xl border border-input bg-background px-4 text-foreground outline-none placeholder:text-muted-foreground focus:border-powerx-yellow"
                            placeholder="Your full name"
                        />
                        <div class="grid gap-4 sm:grid-cols-2">
                            <input
                                class="h-12 rounded-xl border border-input bg-background px-4 text-foreground outline-none placeholder:text-muted-foreground focus:border-powerx-yellow"
                                placeholder="Email address"
                            />
                            <input
                                class="h-12 rounded-xl border border-input bg-background px-4 text-foreground outline-none placeholder:text-muted-foreground focus:border-powerx-yellow"
                                placeholder="+974..."
                            />
                        </div>
                        <Button
                            class="h-12 rounded-full bg-powerx-yellow font-black text-powerx-navy hover:bg-powerx-gold"
                        >
                            Request callback
                        </Button>
                    </div>
                </div>

                <div
                    class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm"
                >
                    <div
                        class="relative min-h-72 bg-[url('/images/marketing/powerx-practical-training.jpg')] bg-cover bg-center"
                    >
                        <div
                            class="absolute inset-0 bg-[linear-gradient(180deg,rgba(2,1,1,0.08)_0%,rgba(2,1,1,0.78)_100%)]"
                        />
                        <div
                            class="absolute right-5 bottom-5 left-5 text-white"
                        >
                            <p
                                class="text-xs font-black tracking-[0.28em] text-powerx-yellow uppercase"
                            >
                                Hero surface
                            </p>
                            <h3 class="mt-2 text-3xl font-black">
                                Use real training imagery with controlled copy.
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-t border-border bg-powerx-ink py-14 text-white">
            <div
                class="mx-auto grid max-w-7xl gap-8 px-6 lg:grid-cols-[0.82fr_1.18fr] lg:px-8"
            >
                <div>
                    <p
                        class="text-sm font-black tracking-[0.26em] text-powerx-yellow uppercase"
                    >
                        04 / States
                    </p>
                    <h2 class="mt-3 text-3xl font-black">
                        Status color has meaning.
                    </h2>
                    <p class="mt-4 text-sm leading-7 text-white/65">
                        Yellow is the conversion color. Green confirms
                        completion. Cyan and blue support informational states.
                    </p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div
                        class="rounded-2xl border border-white/10 bg-white/[0.04] p-5"
                    >
                        <PlayCircle class="size-8 text-powerx-yellow" />
                        <h3 class="mt-4 font-black">Live course</h3>
                        <p class="mt-2 text-sm leading-6 text-white/60">
                            Published, priced, and ready for admissions
                            follow-up.
                        </p>
                    </div>
                    <div
                        class="rounded-2xl border border-white/10 bg-white/[0.04] p-5"
                    >
                        <ShieldCheck class="size-8 text-powerx-success" />
                        <h3 class="mt-4 font-black">Verified access</h3>
                        <p class="mt-2 text-sm leading-6 text-white/60">
                            Learner content unlocks after internal approval.
                        </p>
                    </div>
                    <div
                        class="rounded-2xl border border-white/10 bg-white/[0.04] p-5"
                    >
                        <Building2 class="size-8 text-powerx-cyan" />
                        <h3 class="mt-4 font-black">Company batch</h3>
                        <p class="mt-2 text-sm leading-6 text-white/60">
                            Bulk registration, attendance, and quotation
                            coordination.
                        </p>
                    </div>
                    <div
                        class="rounded-2xl border border-white/10 bg-white/[0.04] p-5"
                    >
                        <FileText class="size-8 text-powerx-blue" />
                        <h3 class="mt-4 font-black">Completion record</h3>
                        <p class="mt-2 text-sm leading-6 text-white/60">
                            Public verification shows only approved completion
                            details.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-background py-14">
            <div
                class="mx-auto grid max-w-7xl gap-8 px-6 lg:grid-cols-[0.82fr_1.18fr] lg:px-8"
            >
                <div>
                    <p
                        class="text-sm font-black tracking-[0.26em] text-powerx-gold uppercase dark:text-powerx-yellow"
                    >
                        05 / Certificate preview
                    </p>
                    <h2 class="mt-3 text-3xl font-black">
                        Formal documents stay restrained.
                    </h2>
                </div>

                <div
                    class="rounded-2xl border border-powerx-yellow/45 bg-card p-8 text-card-foreground shadow-sm"
                >
                    <div class="flex items-center justify-between gap-5">
                        <div class="flex items-center gap-3">
                            <span
                                class="flex size-12 items-center justify-center overflow-hidden rounded-xl bg-white p-1"
                            >
                                <AppLogoIcon class="size-full" />
                            </span>
                            <div>
                                <p class="text-xl font-black">
                                    Power<span class="text-powerx-gold">X</span>
                                </p>
                                <p
                                    class="text-[10px] font-black tracking-[0.24em] text-muted-foreground uppercase"
                                >
                                    Training Center
                                </p>
                            </div>
                        </div>
                        <GraduationCap class="size-8 text-powerx-gold" />
                    </div>

                    <div class="my-8 h-px bg-border" />

                    <p
                        class="text-xs font-black tracking-[0.3em] text-muted-foreground uppercase"
                    >
                        Certificate of completion
                    </p>
                    <h3 class="mt-4 text-4xl font-black">
                        Kahramaa Exam Preparation
                    </h3>
                    <p class="mt-4 leading-7 text-muted-foreground">
                        Awarded after configured payment, attendance, progress,
                        assessment, and practical rules are satisfied.
                    </p>

                    <div class="mt-8 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl bg-muted p-4">
                            <p class="text-xs font-bold text-muted-foreground">
                                Certificate no.
                            </p>
                            <p class="mt-1 font-black">PX-2026-0001</p>
                        </div>
                        <div class="rounded-2xl bg-muted p-4">
                            <p class="text-xs font-bold text-muted-foreground">
                                Verification
                            </p>
                            <p class="mt-1 flex items-center gap-2 font-black">
                                <CheckCircle2
                                    class="size-4 text-powerx-success"
                                />
                                Public page ready
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</template>
