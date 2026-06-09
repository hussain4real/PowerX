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

defineProps<{
    featuredCourses: FeaturedCourse[];
    leadCourseOptions: Array<{
        id: number;
        title: string;
    }>;
}>();

const page = usePage();

const dashboardUrl = computed(() =>
    page.props.currentTeam ? dashboard(page.props.currentTeam.slug).url : '/',
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
    'Choose a course or request a company batch',
    'Submit a callback, registration, or quotation request',
    'Confirm admissions and manual payment approval',
    'Access lessons, PDF references, videos, and instructor guidance',
    'Complete practical sessions, mock assessments, and eligibility checks',
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

            <header
                class="mx-auto flex max-w-7xl items-center justify-between gap-2 px-4 py-5 sm:gap-4 sm:px-6 lg:px-8"
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
                    <div class="flex flex-wrap gap-3">
                        <BrandStatusBadge
                            label="Qatar electrical training"
                            tone="warning"
                        />
                        <BrandStatusBadge
                            label="Individual and corporate tracks"
                            tone="info"
                        />
                    </div>

                    <h1
                        class="mt-8 max-w-5xl text-4xl leading-[0.98] font-black text-white uppercase sm:text-6xl lg:text-7xl"
                    >
                        Practical electrical training for Qatar's site-ready
                        professionals.
                    </h1>

                    <p class="mt-6 max-w-2xl text-lg leading-8 text-white/76">
                        PowerX combines Kahramaa-aware exam preparation,
                        hands-on workshops, mock assessments, and clear
                        completion records for learners and technical teams.
                    </p>

                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <Link
                            :href="coursesIndex()"
                            class="inline-flex min-h-12 items-center justify-center gap-2 rounded-full bg-powerx-yellow px-6 py-3 text-sm font-black tracking-wide text-powerx-navy uppercase shadow-[0_0_34px_rgba(255,193,7,0.34)] transition hover:-translate-y-0.5 hover:bg-white"
                        >
                            Explore courses
                            <ArrowRight class="size-4" />
                        </Link>
                        <Link
                            :href="corporateQuotationCreate()"
                            class="inline-flex min-h-12 items-center justify-center gap-2 rounded-full border border-white/25 bg-white/8 px-6 py-3 text-sm font-bold text-white backdrop-blur transition hover:border-powerx-yellow hover:text-powerx-yellow"
                        >
                            Corporate training
                            <Building2 class="size-4" />
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-b border-border bg-card">
            <div
                class="mx-auto grid max-w-7xl gap-4 px-6 py-5 sm:grid-cols-3 lg:px-8"
            >
                <article
                    v-for="signal in trustSignals"
                    :key="signal.label"
                    class="grid gap-1 border-border py-3 sm:border-r sm:pr-5 last:sm:border-r-0"
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
                </article>
            </div>
        </section>

        <section id="programs" class="bg-background py-16 lg:py-20">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div
                    class="flex flex-col justify-between gap-5 md:flex-row md:items-end"
                >
                    <div>
                        <p
                            class="text-sm font-black tracking-[0.26em] text-powerx-gold uppercase dark:text-powerx-yellow"
                        >
                            Programs
                        </p>
                        <h2 class="mt-3 max-w-3xl text-4xl font-black">
                            Built for exam confidence, field practice, and
                            company readiness.
                        </h2>
                    </div>
                    <Link
                        :href="coursesIndex()"
                        class="inline-flex items-center justify-center gap-2 rounded-full border border-border bg-card px-5 py-3 text-sm font-bold text-card-foreground shadow-sm transition hover:border-powerx-yellow hover:text-powerx-navy dark:hover:text-powerx-yellow"
                    >
                        View all courses
                        <ArrowRight class="size-4" />
                    </Link>
                </div>

                <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-4">
                    <article
                        v-for="program in programAreas"
                        :key="program.title"
                        class="rounded-2xl border border-border bg-card p-5 shadow-sm transition hover:-translate-y-1 hover:border-powerx-yellow"
                    >
                        <component
                            :is="program.icon"
                            class="size-9 text-powerx-gold dark:text-powerx-yellow"
                        />
                        <h3 class="mt-5 text-xl font-black">
                            {{ program.title }}
                        </h3>
                        <p class="mt-3 text-sm leading-6 text-muted-foreground">
                            {{ program.description }}
                        </p>
                    </article>
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
                    <div>
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
                    </div>

                    <div class="grid gap-5 md:grid-cols-3">
                        <article
                            v-for="course in featuredCourses"
                            :key="course.id"
                            class="flex min-h-full flex-col rounded-2xl border border-border bg-card p-5 shadow-sm"
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
                                class="mt-5 inline-flex items-center justify-center gap-2 rounded-full bg-secondary px-4 py-2 text-sm font-bold text-secondary-foreground transition hover:bg-powerx-yellow hover:text-powerx-navy"
                            >
                                View course
                                <ArrowRight class="size-4" />
                            </Link>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section id="pathways" class="bg-background py-16 lg:py-20">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="grid gap-8 lg:grid-cols-[0.9fr_1.1fr]">
                    <div>
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
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <article
                            v-for="pathway in pathwayCards"
                            :key="pathway.title"
                            class="rounded-2xl border border-border bg-card p-6 shadow-sm"
                        >
                            <component
                                :is="pathway.icon"
                                class="size-10 text-powerx-gold dark:text-powerx-yellow"
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
                                <ArrowRight class="size-4" />
                            </Link>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-y border-border bg-card py-16 lg:py-20">
            <div
                class="mx-auto grid max-w-7xl gap-10 px-6 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:px-8"
            >
                <div class="relative">
                    <img
                        :src="marketingAssets.practicalTraining"
                        alt="Electrical meter testing during practical training"
                        class="aspect-[4/3] w-full rounded-2xl object-cover shadow-xl"
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
                </div>

                <div>
                    <p
                        class="text-sm font-black tracking-[0.26em] text-powerx-gold uppercase dark:text-powerx-yellow"
                    >
                        Learning experience
                    </p>
                    <h2 class="mt-3 text-4xl font-black">
                        Technical enough for engineers, clear enough for busy
                        teams.
                    </h2>
                    <p class="mt-4 text-sm leading-7 text-muted-foreground">
                        The public site should sell the training promise while
                        the platform handles the operational depth behind the
                        scenes: payments, lesson access, attendance, exams, and
                        certificates.
                    </p>

                    <div class="mt-7 grid gap-3">
                        <div
                            v-for="(step, index) in processSteps"
                            :key="step"
                            class="flex gap-4 rounded-2xl border border-border bg-background p-4"
                        >
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-full bg-powerx-yellow text-sm font-black text-powerx-navy"
                            >
                                {{ index + 1 }}
                            </span>
                            <p class="self-center text-sm font-bold">
                                {{ step }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-background py-16 lg:py-20">
            <div
                class="mx-auto grid max-w-7xl gap-10 px-6 lg:grid-cols-[0.88fr_1.12fr] lg:items-center lg:px-8"
            >
                <div>
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
                </div>

                <div
                    class="grid gap-5 rounded-2xl border border-border bg-card p-6 shadow-sm md:grid-cols-[0.8fr_1.2fr]"
                >
                    <div
                        class="flex items-center justify-center rounded-2xl bg-muted p-6"
                    >
                        <img
                            :src="marketingAssets.trainingMeter"
                            alt="PowerX electrical training illustration"
                            class="max-h-72 object-contain"
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
                </div>
            </div>
        </section>

        <section
            id="contact"
            class="border-y border-border bg-powerx-soft py-16 lg:py-20 dark:bg-powerx-navy"
        >
            <div
                class="mx-auto grid max-w-7xl gap-10 px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8"
            >
                <div>
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
                </div>

                <aside
                    class="rounded-2xl border border-border bg-card p-5 text-card-foreground shadow-xl"
                >
                    <div class="grid gap-5 md:grid-cols-[0.58fr_1.42fr]">
                        <div
                            class="hidden items-center justify-center rounded-2xl bg-muted p-6 md:flex"
                        >
                            <img
                                :src="marketingAssets.support"
                                alt="PowerX support"
                                class="max-h-56 object-contain"
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
                                    type="hidden"
                                    name="source"
                                    value="homepage"
                                />

                                <label class="grid gap-2">
                                    <span class="text-sm font-bold">Name</span>
                                    <input
                                        name="name"
                                        class="h-12 rounded-xl border border-input bg-background px-4 text-foreground transition outline-none placeholder:text-muted-foreground focus:border-powerx-yellow"
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
                                            class="h-12 rounded-xl border border-input bg-background px-4 text-foreground transition outline-none placeholder:text-muted-foreground focus:border-powerx-yellow"
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
                                            class="h-12 rounded-xl border border-input bg-background px-4 text-foreground transition outline-none placeholder:text-muted-foreground focus:border-powerx-yellow"
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
                                        class="h-12 rounded-xl border border-input bg-background px-4 text-foreground transition outline-none focus:border-powerx-yellow"
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
                                        class="rounded-xl border border-input bg-background px-4 py-3 text-foreground transition outline-none placeholder:text-muted-foreground focus:border-powerx-yellow"
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
                                    class="h-12 rounded-full bg-powerx-yellow text-sm font-black tracking-wide text-powerx-navy uppercase hover:bg-powerx-gold disabled:opacity-60"
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
                                    class="rounded-xl border border-powerx-success/30 bg-powerx-success/10 px-4 py-3 text-sm font-bold text-powerx-success"
                                >
                                    Inquiry received. PowerX will follow up
                                    shortly.
                                </p>
                            </Form>
                        </div>
                    </div>
                </aside>
            </div>
        </section>

        <section class="bg-background py-16">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div
                    class="grid gap-8 lg:grid-cols-[0.75fr_1.25fr] lg:items-start"
                >
                    <div>
                        <p
                            class="text-sm font-black tracking-[0.26em] text-powerx-gold uppercase dark:text-powerx-yellow"
                        >
                            FAQ
                        </p>
                        <h2 class="mt-3 text-4xl font-black">
                            Public copy that stays clear and responsible.
                        </h2>
                    </div>
                    <div class="grid gap-4">
                        <article
                            v-for="item in faqItems"
                            :key="item.question"
                            class="rounded-2xl border border-border bg-card p-5 shadow-sm"
                        >
                            <h3 class="font-black">{{ item.question }}</h3>
                            <p
                                class="mt-2 text-sm leading-7 text-muted-foreground"
                            >
                                {{ item.answer }}
                            </p>
                        </article>
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
