<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    Building2,
    CheckCircle2,
    GraduationCap,
    Mail,
    MapPin,
    MessageCircle,
    Phone,
    ShieldCheck,
    UsersRound,
    Wrench,
    Zap,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import BrandStatusBadge from '@/components/powerx/BrandStatusBadge.vue';
import MotionReveal from '@/components/powerx/MotionReveal.vue';
import PowerXAssistantPanel from '@/components/powerx/PowerXAssistantPanel.vue';
import PublicThemeSwitcher from '@/components/powerx/PublicThemeSwitcher.vue';
import { Button } from '@/components/ui/button';
import { dashboard, home, login, styleGuide } from '@/routes';
import { create as corporateQuotationCreate } from '@/routes/corporate/quotations';
import { index as coursesIndex, show as courseShow } from '@/routes/courses';
import { store as storeLead } from '@/routes/leads';

interface CoursePackage {
    id: number;
    name: string;
    currency: string;
    price: string | number;
    discountPrice: string | number | null;
    validityDays: number | null;
    includesCertificate: boolean;
}

interface FeaturedCourse {
    id: number;
    title: string;
    slug: string;
    category: string | null;
    summary: string | null;
    deliveryMode: string;
    currency: string;
    basePrice: string | number;
    validityDays: number | null;
    isFeatured: boolean;
    modulesCount: number;
    enrollmentsCount: number;
    lowestPackagePrice: string | number | null;
    packages: CoursePackage[];
}

interface PowerXAssistantConfig {
    enabled: boolean;
    endpoint: string | null;
    source: string;
    courseId?: number | null;
    courseTitle?: string | null;
}

defineProps<{
    featuredCourses: FeaturedCourse[];
    leadCourseOptions: Array<{
        id: number;
        title: string;
    }>;
    aiAssistant: PowerXAssistantConfig;
}>();

const page = usePage();

const dashboardUrl = computed(() =>
    page.props.currentTeam ? dashboard(page.props.currentTeam.slug).url : '/',
);

const trackingFields = computed(() => {
    const params = new URLSearchParams(page.url.split('?')[1] ?? '');

    return {
        source: params.get('source') ?? 'homepage',
        campaign: params.get('campaign') ?? params.get('utm_campaign') ?? '',
        utm_source: params.get('utm_source') ?? '',
        utm_medium: params.get('utm_medium') ?? '',
        utm_campaign: params.get('utm_campaign') ?? '',
        utm_content: params.get('utm_content') ?? '',
        utm_term: params.get('utm_term') ?? '',
        referral_name: params.get('referral_name') ?? '',
        referral_phone: params.get('referral_phone') ?? '',
        referral_email: params.get('referral_email') ?? '',
        referral_relationship: params.get('referral_relationship') ?? '',
    };
});

const trackingEntries = computed(() =>
    Object.entries(trackingFields.value).map(([name, value]) => ({
        name,
        value,
    })),
);

const marketingAssets = {
    practicalTraining: '/images/marketing/powerx-practical-training.jpg',
    electricalPanel: '/images/marketing/powerx-electrical-panel.png',
    trainingMeter: '/images/marketing/powerx-training-meter.png',
    support: '/images/marketing/powerx-support.png',
} as const;

const trustSignals = [
    {
        value: 'Qatar',
        label: 'Electrical training focus',
        detail: 'Kahramaa-aware exam preparation and site-ready practical sessions.',
    },
    {
        value: 'Blended',
        label: 'Learning model',
        detail: 'Course notes, videos, workshops, mock exams, and instructor follow-up.',
    },
    {
        value: 'Teams',
        label: 'Corporate ready',
        detail: 'Quotations, bulk enrollment, attendance, and completion records.',
    },
] as const;

const programAreas = [
    {
        icon: Zap,
        title: 'Kahramaa exam preparation',
        description:
            'Focused revision for regulations, drawings, load calculation, safety rules, and common assessment patterns.',
    },
    {
        icon: Wrench,
        title: 'HV cable jointing and termination',
        description:
            'Workshop-led practice for safe handling, preparation, testing, and instructor-reviewed technique.',
    },
    {
        icon: ShieldCheck,
        title: 'Electrical safety and compliance',
        description:
            'Isolation routines, safe testing, worksite controls, and compliance-first technician habits.',
    },
    {
        icon: Building2,
        title: 'Corporate workforce training',
        description:
            'Company batches for engineers, supervisors, technicians, and facility teams working in Qatar.',
    },
] as const;

const pathwayCards = [
    {
        icon: GraduationCap,
        title: 'For individual learners',
        description:
            'Explore courses, request registration, complete payment approval, study lessons, attend practical sessions, and track eligibility.',
        cta: 'Explore courses',
        href: coursesIndex(),
    },
    {
        icon: UsersRound,
        title: 'For companies and contractors',
        description:
            'Request a quotation, submit employee details, coordinate batches, review attendance, and receive completion summaries.',
        cta: 'Request corporate training',
        href: corporateQuotationCreate(),
    },
] as const;

const processSteps = [
    'Pick the right course or request a company batch',
    'Share your details and preferred training schedule',
    'Confirm admissions, documents, and payment instructions',
    'Prepare with lessons, references, practice tasks, and instructor support',
    'Attend practical sessions and receive reviewed completion records',
] as const;

const faqItems = [
    {
        question: 'Does PowerX guarantee exam results?',
        answer: 'No. PowerX provides structured preparation, practice, and instructor support, but final assessment results depend on the learner and the relevant authority requirements.',
    },
    {
        question: 'Can companies train several employees at once?',
        answer: 'Yes. Corporate requests can include employee details, preferred schedules, course interest, and notes for quotation follow-up.',
    },
    {
        question: 'What happens after I submit an inquiry?',
        answer: 'The PowerX team reviews your course interest, preferred contact route, and timing, then follows up with next steps.',
    },
] as const;

const money = (amount: string | number | null, currency: string): string =>
    new Intl.NumberFormat('en-QA', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(Number(amount ?? 0));
</script>

<template>
    <Head title="PowerX Training Center" />

    <main class="min-h-screen overflow-hidden bg-background text-foreground">
        <section
            class="relative isolate border-b border-border bg-background text-white"
        >
            <img
                :src="marketingAssets.practicalTraining"
                alt="Hands-on electrical testing practice"
                class="absolute inset-0 -z-20 h-full w-full object-cover"
            />
            <div
                class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgba(2,1,1,0.94)_0%,rgba(7,21,35,0.86)_46%,rgba(7,21,35,0.54)_100%)]"
            />
            <div
                class="absolute inset-0 -z-10 [background-image:linear-gradient(rgba(255,255,255,0.14)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.14)_1px,transparent_1px)] [background-size:72px_72px] opacity-25"
            />
            <div
                class="absolute inset-x-0 top-0 -z-10 h-28 bg-gradient-to-b from-powerx-yellow/18 to-transparent motion-safe:animate-powerx-pulse motion-reduce:animate-none"
            />
            <div
                class="absolute inset-0 -z-10 overflow-hidden opacity-35 motion-reduce:hidden"
            >
                <span
                    class="absolute top-0 left-1/2 h-full w-20 -translate-x-1/2 rotate-12 bg-gradient-to-b from-transparent via-powerx-yellow/20 to-transparent blur-xl motion-safe:animate-powerx-scan"
                />
                <span
                    class="absolute top-24 right-10 h-28 w-44 rounded-3xl border border-white/15"
                />
                <span
                    class="absolute bottom-16 left-8 h-20 w-32 rounded-3xl border border-powerx-yellow/25 motion-safe:animate-powerx-float"
                />
            </div>

            <header
                class="mx-auto flex max-w-7xl items-center justify-between gap-2 px-4 py-5 motion-safe:animate-in motion-safe:duration-700 motion-safe:ease-out motion-safe:fade-in-0 motion-safe:slide-in-from-top-4 sm:gap-4 sm:px-6 lg:px-8"
            >
                <Link :href="home()" class="flex min-w-0 items-center gap-3">
                    <span
                        class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white p-0.5 shadow-[0_0_30px_rgba(255,193,7,0.36)] sm:size-11"
                    >
                        <AppLogoIcon class="size-full" />
                    </span>
                    <span class="grid leading-none">
                        <span class="text-lg font-black tracking-wide">
                            POWER<span class="text-powerx-yellow">X</span>
                        </span>
                        <span
                            class="text-[10px] font-bold tracking-[0.24em] text-white/60 uppercase"
                        >
                            Training Center
                        </span>
                    </span>
                </Link>

                <nav
                    class="hidden items-center gap-5 text-sm font-bold text-white/75 lg:flex"
                    aria-label="Main navigation"
                >
                    <a href="#programs" class="transition hover:text-white">
                        Programs
                    </a>
                    <a href="#pathways" class="transition hover:text-white">
                        Pathways
                    </a>
                    <a href="#contact" class="transition hover:text-white">
                        Contact
                    </a>
                    <Link
                        :href="styleGuide()"
                        class="transition hover:text-white"
                    >
                        Style guide
                    </Link>
                </nav>

                <div class="flex items-center gap-2">
                    <PublicThemeSwitcher />
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboardUrl"
                        class="hidden rounded-full bg-powerx-yellow px-5 py-2 text-sm font-black text-powerx-navy transition hover:bg-white sm:inline-flex"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="hidden rounded-full border border-white/20 bg-white/5 px-4 py-2 text-sm font-bold text-white/85 backdrop-blur transition hover:border-white hover:text-white sm:inline-flex"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="coursesIndex()"
                            class="rounded-full bg-powerx-yellow px-5 py-2 text-sm font-black text-powerx-navy shadow-[0_0_28px_rgba(255,193,7,0.32)] transition hover:bg-white"
                        >
                            Register
                        </Link>
                    </template>
                </div>
            </header>

            <div class="mx-auto max-w-7xl px-6 pt-12 pb-24 lg:px-8 lg:pt-24">
                <div class="max-w-4xl">
                    <MotionReveal class="flex flex-wrap gap-3">
                        <BrandStatusBadge
                            label="Qatar electrical training"
                            tone="warning"
                        />
                        <BrandStatusBadge
                            label="Individual and corporate tracks"
                            tone="info"
                        />
                    </MotionReveal>

                    <MotionReveal
                        as="h1"
                        :delay="120"
                        class="mt-8 max-w-5xl text-4xl leading-[0.98] font-black text-white uppercase sm:text-6xl lg:text-7xl"
                    >
                        Practical electrical training for Qatar's site-ready
                        professionals.
                    </MotionReveal>

                    <MotionReveal
                        as="p"
                        :delay="220"
                        class="mt-6 max-w-2xl text-lg leading-8 text-white/76"
                    >
                        PowerX combines Kahramaa-aware exam preparation,
                        hands-on workshops, mock assessments, and clear
                        completion records for learners and technical teams.
                    </MotionReveal>

                    <MotionReveal
                        :delay="320"
                        class="mt-9 flex flex-col gap-3 sm:flex-row"
                    >
                        <Link
                            :href="coursesIndex()"
                            class="group inline-flex min-h-12 items-center justify-center gap-2 rounded-full bg-powerx-yellow px-6 py-3 text-sm font-black tracking-wide text-powerx-navy uppercase shadow-[0_0_34px_rgba(255,193,7,0.34)] hover:-translate-y-0.5 hover:bg-white motion-safe:transition-all motion-safe:duration-300 motion-safe:ease-out motion-reduce:hover:translate-y-0"
                        >
                            Explore courses
                            <ArrowRight
                                class="size-4 group-hover:translate-x-1 motion-safe:transition-transform motion-safe:duration-300 motion-reduce:group-hover:translate-x-0"
                            />
                        </Link>
                        <Link
                            :href="corporateQuotationCreate()"
                            class="inline-flex min-h-12 items-center justify-center gap-2 rounded-full border border-white/25 bg-white/8 px-6 py-3 text-sm font-bold text-white backdrop-blur hover:-translate-y-0.5 hover:border-powerx-yellow hover:text-powerx-yellow motion-safe:transition-all motion-safe:duration-300 motion-reduce:hover:translate-y-0"
                        >
                            Corporate training
                            <Building2 class="size-4" />
                        </Link>
                    </MotionReveal>
                </div>
            </div>
        </section>

        <section class="border-b border-border bg-card">
            <div
                class="mx-auto grid max-w-7xl gap-4 px-6 py-5 sm:grid-cols-3 lg:px-8"
            >
                <MotionReveal
                    v-for="(signal, index) in trustSignals"
                    :key="signal.label"
                    as="article"
                    :delay="index * 80"
                    class="grid gap-1 border-border py-3 hover:-translate-y-0.5 motion-safe:transition-transform motion-safe:duration-300 motion-reduce:hover:translate-y-0 sm:border-r sm:pr-5 last:sm:border-r-0"
                >
                    <p
                        class="text-2xl font-black text-powerx-navy dark:text-powerx-yellow"
                    >
                        {{ signal.value }}
                    </p>
                    <h2 class="text-sm font-black uppercase">
                        {{ signal.label }}
                    </h2>
                    <p class="text-sm leading-6 text-muted-foreground">
                        {{ signal.detail }}
                    </p>
                </MotionReveal>
            </div>
        </section>

        <section id="programs" class="bg-background py-16 lg:py-20">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div
                    class="flex flex-col justify-between gap-5 md:flex-row md:items-end"
                >
                    <MotionReveal>
                        <p
                            class="text-sm font-black tracking-[0.26em] text-powerx-gold uppercase dark:text-powerx-yellow"
                        >
                            Programs
                        </p>
                        <h2 class="mt-3 max-w-3xl text-4xl font-black">
                            Built for exam confidence, field practice, and
                            company readiness.
                        </h2>
                    </MotionReveal>
                    <Link
                        :href="coursesIndex()"
                        class="group inline-flex items-center justify-center gap-2 rounded-full border border-border bg-card px-5 py-3 text-sm font-bold text-card-foreground shadow-sm hover:-translate-y-0.5 hover:border-powerx-yellow hover:text-powerx-navy motion-safe:transition-all motion-safe:duration-300 motion-reduce:hover:translate-y-0 dark:hover:text-powerx-yellow"
                    >
                        View all courses
                        <ArrowRight
                            class="size-4 group-hover:translate-x-1 motion-safe:transition-transform motion-safe:duration-300 motion-reduce:group-hover:translate-x-0"
                        />
                    </Link>
                </div>

                <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                    <MotionReveal
                        v-for="(program, index) in programAreas"
                        :key="program.title"
                        as="article"
                        :delay="index * 90"
                        class="group rounded-2xl border border-border bg-card p-5 shadow-sm hover:-translate-y-1 hover:border-powerx-yellow hover:shadow-[0_22px_60px_rgba(255,193,7,0.14)] motion-safe:transition-all motion-safe:duration-300 motion-reduce:hover:translate-y-0"
                    >
                        <component
                            :is="program.icon"
                            class="size-9 text-powerx-gold group-hover:scale-110 motion-safe:transition-transform motion-safe:duration-300 dark:text-powerx-yellow"
                        />
                        <h3 class="mt-5 text-xl font-black">
                            {{ program.title }}
                        </h3>
                        <p class="mt-3 text-sm leading-6 text-muted-foreground">
                            {{ program.description }}
                        </p>
                    </MotionReveal>
                </div>
            </div>
        </section>

        <section
            v-if="featuredCourses.length"
            class="border-y border-border bg-powerx-soft py-16 dark:bg-powerx-navy"
        >
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div
                    class="grid gap-8 lg:grid-cols-[0.72fr_1.28fr] lg:items-start"
                >
                    <MotionReveal>
                        <p
                            class="text-sm font-black tracking-[0.26em] text-powerx-gold uppercase dark:text-powerx-yellow"
                        >
                            Featured courses
                        </p>
                        <h2 class="mt-3 text-3xl font-black">
                            Public programs ready for admissions follow-up.
                        </h2>
                        <p class="mt-4 text-sm leading-7 text-muted-foreground">
                            Pricing and access stay tied to the live course
                            catalog, while this page gives prospects a polished
                            path into the right program.
                        </p>
                    </MotionReveal>

                    <div class="grid gap-5 md:grid-cols-3">
                        <MotionReveal
                            v-for="(course, index) in featuredCourses"
                            :key="course.id"
                            as="article"
                            direction="up"
                            :delay="index * 110"
                            class="group flex min-h-full flex-col rounded-2xl border border-border bg-card p-5 shadow-sm hover:-translate-y-1 hover:border-powerx-yellow hover:shadow-[0_24px_70px_rgba(255,193,7,0.14)] motion-safe:transition-all motion-safe:duration-300 motion-reduce:hover:translate-y-0"
                        >
                            <BrandStatusBadge
                                :label="course.category ?? 'PowerX course'"
                                tone="warning"
                            />
                            <h3 class="mt-5 text-xl leading-tight font-black">
                                {{ course.title }}
                            </h3>
                            <p
                                class="mt-3 flex-1 text-sm leading-6 text-muted-foreground"
                            >
                                {{ course.summary }}
                            </p>
                            <div
                                class="mt-5 grid gap-2 rounded-xl border border-border bg-muted/70 p-3 text-sm"
                            >
                                <div class="flex justify-between gap-3">
                                    <span class="text-muted-foreground">
                                        Modules
                                    </span>
                                    <span class="font-black">
                                        {{ course.modulesCount }}
                                    </span>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <span class="text-muted-foreground">
                                        From
                                    </span>
                                    <span
                                        class="font-black text-powerx-navy dark:text-powerx-yellow"
                                    >
                                        {{
                                            money(
                                                course.lowestPackagePrice,
                                                course.currency,
                                            )
                                        }}
                                    </span>
                                </div>
                            </div>
                            <Link
                                :href="courseShow(course.slug)"
                                class="mt-5 inline-flex items-center justify-center gap-2 rounded-full bg-secondary px-4 py-2 text-sm font-bold text-secondary-foreground hover:bg-powerx-yellow hover:text-powerx-navy motion-safe:transition-all motion-safe:duration-300"
                            >
                                View course
                                <ArrowRight
                                    class="size-4 group-hover:translate-x-1 motion-safe:transition-transform motion-safe:duration-300 motion-reduce:group-hover:translate-x-0"
                                />
                            </Link>
                        </MotionReveal>
                    </div>
                </div>
            </div>
        </section>

        <section id="pathways" class="bg-background py-16 lg:py-20">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="grid gap-8 lg:grid-cols-[0.9fr_1.1fr]">
                    <MotionReveal direction="left">
                        <p
                            class="text-sm font-black tracking-[0.26em] text-powerx-gold uppercase dark:text-powerx-yellow"
                        >
                            Pathways
                        </p>
                        <h2 class="mt-3 text-4xl font-black">
                            One brand, two clear entry points.
                        </h2>
                        <p class="mt-4 text-sm leading-7 text-muted-foreground">
                            Arabian Infotech goes broad with a dense catalog.
                            PowerX should feel more focused: electrical
                            training, practical readiness, and direct conversion
                            paths.
                        </p>
                    </MotionReveal>

                    <div class="grid gap-5 md:grid-cols-2">
                        <MotionReveal
                            v-for="(pathway, index) in pathwayCards"
                            :key="pathway.title"
                            as="article"
                            direction="right"
                            :delay="index * 120"
                            class="group rounded-2xl border border-border bg-card p-6 shadow-sm hover:-translate-y-1 hover:border-powerx-yellow hover:shadow-[0_24px_70px_rgba(255,193,7,0.12)] motion-safe:transition-all motion-safe:duration-300 motion-reduce:hover:translate-y-0"
                        >
                            <component
                                :is="pathway.icon"
                                class="size-10 text-powerx-gold group-hover:scale-110 motion-safe:transition-transform motion-safe:duration-300 dark:text-powerx-yellow"
                            />
                            <h3 class="mt-5 text-2xl font-black">
                                {{ pathway.title }}
                            </h3>
                            <p
                                class="mt-3 text-sm leading-7 text-muted-foreground"
                            >
                                {{ pathway.description }}
                            </p>
                            <Link
                                :href="pathway.href"
                                class="mt-6 inline-flex items-center gap-2 text-sm font-black text-powerx-navy transition hover:text-powerx-gold dark:text-powerx-yellow"
                            >
                                {{ pathway.cta }}
                                <ArrowRight
                                    class="size-4 group-hover:translate-x-1 motion-safe:transition-transform motion-safe:duration-300 motion-reduce:group-hover:translate-x-0"
                                />
                            </Link>
                        </MotionReveal>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-y border-border bg-card py-16 lg:py-20">
            <div
                class="mx-auto grid max-w-7xl gap-10 px-6 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:px-8"
            >
                <MotionReveal direction="left" class="relative">
                    <img
                        :src="marketingAssets.practicalTraining"
                        alt="Electrical meter testing during practical training"
                        class="aspect-[4/3] w-full rounded-2xl object-cover shadow-xl hover:scale-[1.015] motion-safe:transition-transform motion-safe:duration-700 motion-reduce:hover:scale-100"
                    />
                    <div
                        class="absolute right-4 bottom-4 rounded-2xl border border-white/20 bg-powerx-ink/82 p-4 text-white shadow-xl backdrop-blur"
                    >
                        <p
                            class="text-xs font-black tracking-[0.22em] text-powerx-yellow uppercase"
                        >
                            Practical lab
                        </p>
                        <p class="mt-1 max-w-52 text-sm leading-6 font-bold">
                            Hands-on routines reinforce classroom and exam
                            preparation.
                        </p>
                    </div>
                </MotionReveal>

                <MotionReveal direction="right">
                    <p
                        class="text-sm font-black tracking-[0.26em] text-powerx-gold uppercase dark:text-powerx-yellow"
                    >
                        Learning experience
                    </p>
                    <h2 class="mt-3 text-4xl font-black">
                        Clear guidance from first enquiry to completion.
                    </h2>
                    <p class="mt-4 text-sm leading-7 text-muted-foreground">
                        PowerX keeps every step easy to understand for learners
                        and company teams, from choosing a program to preparing
                        for class, attending practical sessions, and receiving
                        responsible completion records.
                    </p>

                    <div class="mt-7 grid gap-3">
                        <MotionReveal
                            v-for="(step, index) in processSteps"
                            :key="step"
                            :delay="index * 90"
                            as="div"
                            direction="right"
                            distance="sm"
                            class="group relative flex gap-4 overflow-hidden rounded-2xl border border-border bg-background p-4 hover:-translate-y-0.5 hover:border-powerx-yellow/60 hover:shadow-sm motion-safe:transition-all motion-safe:duration-300 motion-reduce:hover:translate-y-0"
                        >
                            <span
                                class="pointer-events-none absolute inset-y-0 -left-16 w-12 rotate-12 bg-gradient-to-r from-transparent via-powerx-yellow/20 to-transparent opacity-0 group-hover:left-[110%] group-hover:opacity-100 motion-safe:transition-all motion-safe:duration-700 motion-reduce:hidden"
                            />
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-full bg-powerx-yellow text-sm font-black text-powerx-navy shadow-[0_0_20px_rgba(255,193,7,0.26)] group-hover:scale-110 motion-safe:transition-transform motion-safe:duration-300"
                            >
                                {{ index + 1 }}
                            </span>
                            <p class="self-center text-sm font-bold">
                                {{ step }}
                            </p>
                        </MotionReveal>
                    </div>
                </MotionReveal>
            </div>
        </section>

        <section class="bg-background py-16 lg:py-20">
            <div
                class="mx-auto grid max-w-7xl gap-10 px-6 lg:grid-cols-[0.88fr_1.12fr] lg:items-center lg:px-8"
            >
                <MotionReveal direction="left">
                    <p
                        class="text-sm font-black tracking-[0.26em] text-powerx-gold uppercase dark:text-powerx-yellow"
                    >
                        Completion records
                    </p>
                    <h2 class="mt-3 text-4xl font-black">
                        Clear PowerX completion records without overclaiming
                        authority.
                    </h2>
                    <p class="mt-4 text-sm leading-7 text-muted-foreground">
                        Certificates and verification pages should communicate
                        approved PowerX completion details only, keeping learner
                        privacy and public wording controlled.
                    </p>
                    <div class="mt-7 flex flex-wrap gap-3">
                        <BrandStatusBadge
                            label="Eligibility based"
                            tone="success"
                        />
                        <BrandStatusBadge
                            label="Public verification"
                            tone="info"
                        />
                        <BrandStatusBadge
                            label="Privacy aware"
                            tone="neutral"
                        />
                    </div>
                </MotionReveal>

                <MotionReveal
                    direction="right"
                    class="grid gap-5 rounded-2xl border border-border bg-card p-6 shadow-sm md:grid-cols-[0.8fr_1.2fr]"
                >
                    <div
                        class="flex items-center justify-center rounded-2xl bg-muted p-6"
                    >
                        <img
                            :src="marketingAssets.trainingMeter"
                            alt="PowerX electrical training illustration"
                            class="max-h-72 object-contain motion-safe:animate-powerx-float motion-reduce:animate-none"
                        />
                    </div>
                    <div class="grid content-center gap-4">
                        <div class="flex items-center gap-3">
                            <span
                                class="flex size-12 items-center justify-center overflow-hidden rounded-xl bg-white p-1"
                            >
                                <AppLogoIcon class="size-full" />
                            </span>
                            <div>
                                <p class="text-xl font-black">
                                    PowerX completion
                                </p>
                                <p
                                    class="text-xs font-black tracking-[0.22em] text-muted-foreground uppercase"
                                >
                                    Verification ready
                                </p>
                            </div>
                        </div>
                        <div class="grid gap-3 text-sm">
                            <div class="flex items-center gap-3">
                                <CheckCircle2
                                    class="size-5 text-powerx-success"
                                />
                                <span
                                    >Course progress and attendance
                                    checked</span
                                >
                            </div>
                            <div class="flex items-center gap-3">
                                <CheckCircle2
                                    class="size-5 text-powerx-success"
                                />
                                <span
                                    >Assessment and practical outcomes
                                    reviewed</span
                                >
                            </div>
                            <div class="flex items-center gap-3">
                                <CheckCircle2
                                    class="size-5 text-powerx-success"
                                />
                                <span
                                    >Public page exposes only approved
                                    fields</span
                                >
                            </div>
                        </div>
                    </div>
                </MotionReveal>
            </div>
        </section>

        <section
            id="contact"
            class="border-y border-border bg-powerx-soft py-16 lg:py-20 dark:bg-powerx-navy"
        >
            <div
                class="mx-auto grid max-w-7xl gap-10 px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8"
            >
                <MotionReveal direction="left">
                    <p
                        class="text-sm font-black tracking-[0.26em] text-powerx-gold uppercase dark:text-powerx-yellow"
                    >
                        Talk to PowerX
                    </p>
                    <h2 class="mt-3 text-4xl font-black">
                        Ask for the right course, schedule, or company training
                        plan.
                    </h2>
                    <p class="mt-4 text-sm leading-7 text-muted-foreground">
                        Send a quick inquiry and the team can follow up with
                        course guidance, timing, or corporate quotation next
                        steps.
                    </p>

                    <div class="mt-8 grid gap-4 text-sm">
                        <div class="flex items-start gap-3">
                            <Phone
                                class="mt-1 size-5 text-powerx-gold dark:text-powerx-yellow"
                            />
                            <span>+974 3035 8817</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <Mail
                                class="mt-1 size-5 text-powerx-gold dark:text-powerx-yellow"
                            />
                            <span>admin@powerxelect.com</span>
                        </div>
                        <div class="flex items-start gap-3">
                            <MapPin
                                class="mt-1 size-5 text-powerx-gold dark:text-powerx-yellow"
                            />
                            <span>
                                Office #1, 2nd Floor, Building 64, Street 950,
                                Zone 27, Doha, Qatar
                            </span>
                        </div>
                    </div>
                </MotionReveal>

                <MotionReveal
                    as="aside"
                    direction="right"
                    class="rounded-2xl border border-border bg-card p-5 text-card-foreground shadow-xl"
                >
                    <div class="grid gap-5 md:grid-cols-[0.58fr_1.42fr]">
                        <div
                            class="hidden items-center justify-center rounded-2xl bg-muted p-6 md:flex"
                        >
                            <img
                                :src="marketingAssets.support"
                                alt="PowerX support"
                                class="max-h-56 object-contain motion-safe:animate-powerx-float motion-reduce:animate-none"
                            />
                        </div>

                        <div>
                            <div class="flex items-center gap-3">
                                <MessageCircle
                                    class="size-7 text-powerx-gold dark:text-powerx-yellow"
                                />
                                <div>
                                    <p
                                        class="text-xs font-black tracking-[0.22em] text-muted-foreground uppercase"
                                    >
                                        Quick inquiry
                                    </p>
                                    <h3 class="text-2xl font-black">
                                        Request a callback
                                    </h3>
                                </div>
                            </div>

                            <Form
                                v-bind="storeLead.form()"
                                reset-on-success
                                class="mt-6 grid gap-4"
                                #default="{ errors, processing, wasSuccessful }"
                            >
                                <input
                                    v-for="field in trackingEntries"
                                    :key="field.name"
                                    type="hidden"
                                    :name="field.name"
                                    :value="field.value"
                                />

                                <label class="grid gap-2">
                                    <span class="text-sm font-bold">Name</span>
                                    <input
                                        name="name"
                                        class="h-12 rounded-xl border border-input bg-background px-4 text-foreground outline-none placeholder:text-muted-foreground focus:border-powerx-yellow focus:shadow-[0_0_0_3px_rgba(255,193,7,0.16)] motion-safe:transition-[border-color,box-shadow] motion-safe:duration-200"
                                        placeholder="Your full name"
                                    />
                                    <span
                                        v-if="errors.name"
                                        class="text-sm font-bold text-destructive"
                                    >
                                        {{ errors.name }}
                                    </span>
                                </label>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <label class="grid gap-2">
                                        <span class="text-sm font-bold">
                                            Email
                                        </span>
                                        <input
                                            name="email"
                                            type="email"
                                            class="h-12 rounded-xl border border-input bg-background px-4 text-foreground outline-none placeholder:text-muted-foreground focus:border-powerx-yellow focus:shadow-[0_0_0_3px_rgba(255,193,7,0.16)] motion-safe:transition-[border-color,box-shadow] motion-safe:duration-200"
                                            placeholder="name@example.com"
                                        />
                                        <span
                                            v-if="errors.email"
                                            class="text-sm font-bold text-destructive"
                                        >
                                            {{ errors.email }}
                                        </span>
                                    </label>
                                    <label class="grid gap-2">
                                        <span class="text-sm font-bold">
                                            Mobile
                                        </span>
                                        <input
                                            name="phone"
                                            class="h-12 rounded-xl border border-input bg-background px-4 text-foreground outline-none placeholder:text-muted-foreground focus:border-powerx-yellow focus:shadow-[0_0_0_3px_rgba(255,193,7,0.16)] motion-safe:transition-[border-color,box-shadow] motion-safe:duration-200"
                                            placeholder="+974..."
                                        />
                                        <span
                                            v-if="errors.phone"
                                            class="text-sm font-bold text-destructive"
                                        >
                                            {{ errors.phone }}
                                        </span>
                                    </label>
                                </div>

                                <label class="grid gap-2">
                                    <span class="text-sm font-bold">
                                        Course interest
                                    </span>
                                    <select
                                        name="course_id"
                                        class="h-12 rounded-xl border border-input bg-background px-4 text-foreground outline-none focus:border-powerx-yellow focus:shadow-[0_0_0_3px_rgba(255,193,7,0.16)] motion-safe:transition-[border-color,box-shadow] motion-safe:duration-200"
                                    >
                                        <option value="">Not sure yet</option>
                                        <option
                                            v-for="course in leadCourseOptions"
                                            :key="course.id"
                                            :value="course.id"
                                        >
                                            {{ course.title }}
                                        </option>
                                    </select>
                                    <span
                                        v-if="errors.course_id"
                                        class="text-sm font-bold text-destructive"
                                    >
                                        {{ errors.course_id }}
                                    </span>
                                </label>

                                <label class="grid gap-2">
                                    <span class="text-sm font-bold">
                                        Message
                                    </span>
                                    <textarea
                                        name="message"
                                        rows="4"
                                        class="rounded-xl border border-input bg-background px-4 py-3 text-foreground outline-none placeholder:text-muted-foreground focus:border-powerx-yellow focus:shadow-[0_0_0_3px_rgba(255,193,7,0.16)] motion-safe:transition-[border-color,box-shadow] motion-safe:duration-200"
                                        placeholder="Tell us what you want to learn or when your team prefers to attend."
                                    />
                                    <span
                                        v-if="errors.message"
                                        class="text-sm font-bold text-destructive"
                                    >
                                        {{ errors.message }}
                                    </span>
                                </label>

                                <Button
                                    type="submit"
                                    :disabled="processing"
                                    class="h-12 rounded-full bg-powerx-yellow text-sm font-black tracking-wide text-powerx-navy uppercase hover:-translate-y-0.5 hover:bg-powerx-gold disabled:opacity-60 motion-safe:transition-all motion-safe:duration-300 motion-reduce:hover:translate-y-0"
                                >
                                    {{
                                        processing
                                            ? 'Sending...'
                                            : 'Request callback'
                                    }}
                                    <Phone class="size-4" />
                                </Button>

                                <p
                                    v-if="wasSuccessful"
                                    class="rounded-xl border border-powerx-success/30 bg-powerx-success/10 px-4 py-3 text-sm font-bold text-powerx-success motion-safe:animate-in motion-safe:duration-300 motion-safe:fade-in-0 motion-safe:slide-in-from-bottom-2"
                                >
                                    Inquiry received. PowerX will follow up
                                    shortly.
                                </p>
                            </Form>
                        </div>
                    </div>
                </MotionReveal>
            </div>
        </section>

        <PowerXAssistantPanel :assistant="aiAssistant" tone="light" />

        <section class="bg-background py-16">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div
                    class="grid gap-8 lg:grid-cols-[0.75fr_1.25fr] lg:items-start"
                >
                    <MotionReveal direction="left">
                        <p
                            class="text-sm font-black tracking-[0.26em] text-powerx-gold uppercase dark:text-powerx-yellow"
                        >
                            FAQ
                        </p>
                        <h2 class="mt-3 text-4xl font-black">
                            Public copy that stays clear and responsible.
                        </h2>
                    </MotionReveal>
                    <div class="grid gap-4">
                        <MotionReveal
                            v-for="(item, index) in faqItems"
                            :key="item.question"
                            as="article"
                            direction="right"
                            :delay="index * 90"
                            class="rounded-2xl border border-border bg-card p-5 shadow-sm hover:-translate-y-0.5 hover:border-powerx-yellow/60 motion-safe:transition-all motion-safe:duration-300 motion-reduce:hover:translate-y-0"
                        >
                            <h3 class="font-black">{{ item.question }}</h3>
                            <p
                                class="mt-2 text-sm leading-7 text-muted-foreground"
                            >
                                {{ item.answer }}
                            </p>
                        </MotionReveal>
                    </div>
                </div>
            </div>
        </section>

        <footer class="border-t border-border bg-powerx-ink text-white">
            <div
                class="mx-auto flex max-w-7xl flex-col gap-6 px-6 py-8 lg:flex-row lg:items-center lg:justify-between lg:px-8"
            >
                <div class="flex items-center gap-3">
                    <span
                        class="flex size-11 items-center justify-center overflow-hidden rounded-xl bg-white p-1"
                    >
                        <AppLogoIcon class="size-full" />
                    </span>
                    <div>
                        <p class="font-black">
                            Power<span class="text-powerx-yellow">X</span>
                            Training Center
                        </p>
                        <p class="text-sm text-white/55">
                            Practical electrical training in Qatar.
                        </p>
                    </div>
                </div>
                <div
                    class="flex flex-wrap gap-3 text-sm font-bold text-white/70"
                >
                    <Link :href="coursesIndex()" class="hover:text-white">
                        Courses
                    </Link>
                    <Link
                        :href="corporateQuotationCreate()"
                        class="hover:text-white"
                    >
                        Corporate
                    </Link>
                    <Link :href="styleGuide()" class="hover:text-white">
                        Style guide
                    </Link>
                </div>
            </div>
        </footer>
    </main>
</template>
