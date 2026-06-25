<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    BadgeCheck,
    CalendarCheck,
    CheckCircle2,
    Clock3,
    FileText,
    GraduationCap,
    PlayCircle,
    Send,
    ShieldCheck,
    Wrench,
} from 'lucide-vue-next';
import { computed } from 'vue';
import type { Component } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import BrandStatusBadge from '@/components/powerx/BrandStatusBadge.vue';
import MotionReveal from '@/components/powerx/MotionReveal.vue';
import PowerXAssistantPanel from '@/components/powerx/PowerXAssistantPanel.vue';
import PublicThemeSwitcher from '@/components/powerx/PublicThemeSwitcher.vue';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';
import { index as coursesIndex } from '@/routes/courses';
import { store as storePreviewEvent } from '@/routes/courses/preview-events';
import { store as storeRegistration } from '@/routes/courses/registrations';
interface CoursePackage {
    id: number;
    name: string;
    packageType: string;
    currency: string;
    price: string | number;
    discountPrice: string | number | null;
    validityDays: number | null;
    maxExamAttempts: number | null;
    includesCertificate: boolean;
    allowsFreePreview: boolean;
}
interface Lesson {
    id: number;
    title: string;
    lessonType: string;
    durationMinutes: number | null;
    isPreview: boolean;
}
interface CourseModule {
    id: number;
    title: string;
    summary: string | null;
    sortOrder: number;
    lessons: Lesson[];
}
interface CourseDetail {
    id: number;
    title: string;
    slug: string;
    category: string | null;
    summary: string | null;
    description: string | null;
    deliveryMode: string;
    currency: string;
    basePrice: string | number;
    validityDays: number | null;
    isFeatured: boolean;
    modulesCount: number;
    enrollmentsCount: number;
    questionsCount: number;
    lowestPackagePrice: string | number | null;
    packages: CoursePackage[];
    modules: CourseModule[];
}
interface PowerXAssistantConfig {
    enabled: boolean;
    endpoint: string | null;
    source: string;
    courseId?: number | null;
    courseTitle?: string | null;
}
defineProps<{ course: CourseDetail; aiAssistant: PowerXAssistantConfig }>();
const page = usePage();
const trackingFields = computed(() => {
    const params = new URLSearchParams(page.url.split('?')[1] ?? '');

    return {
        source: params.get('source') ?? 'course_detail',
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
const money = (amount: string | number | null, currency: string): string =>
    new Intl.NumberFormat('en-QA', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(Number(amount ?? 0));
const lessonIcon = (lessonType: string) => {
    const icons: Record<string, Component> = {
        video: PlayCircle,
        document: FileText,
        quiz: GraduationCap,
        practical: Wrench,
    };

    return icons[lessonType] ?? Clock3;
};
</script>
<template>
    <Head :title="course.title" />
    <main
        class="min-h-screen overflow-x-hidden bg-background text-foreground dark:bg-powerx-ink dark:text-white"
    >
        <section
            class="relative isolate overflow-hidden border-b border-border dark:border-white/10"
        >
            <div
                class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_18%_18%,rgba(255,193,7,0.28),transparent_28%),radial-gradient(circle_at_82%_10%,rgba(13,202,240,0.12),transparent_26%),linear-gradient(135deg,#f8fafc_0%,#edf3f8_48%,#dce9f5_100%)] dark:hidden"
            />
            <div
                class="absolute inset-0 -z-10 hidden bg-[radial-gradient(circle_at_18%_18%,rgba(255,193,7,0.22),transparent_28%),radial-gradient(circle_at_82%_10%,rgba(13,202,240,0.16),transparent_26%),linear-gradient(135deg,#020101_0%,#071523_46%,#0b2034_100%)] dark:block"
            />
            <div
                class="absolute inset-0 -z-10 [background-image:linear-gradient(rgba(7,21,35,0.08)_1px,transparent_1px),linear-gradient(90deg,rgba(7,21,35,0.08)_1px,transparent_1px)] [background-size:72px_72px] opacity-25 dark:hidden"
            />
            <div
                class="absolute inset-0 -z-10 hidden [background-image:linear-gradient(rgba(255,255,255,0.08)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.08)_1px,transparent_1px)] [background-size:72px_72px] opacity-25 dark:block"
            />
            <div
                class="absolute inset-0 -z-10 overflow-hidden opacity-45 motion-reduce:hidden"
            >
                <span
                    class="absolute top-0 left-1/2 h-full w-16 -translate-x-1/2 rotate-12 bg-gradient-to-b from-transparent via-powerx-yellow/18 to-transparent blur-xl motion-safe:animate-powerx-scan"
                />
                <span
                    class="absolute bottom-20 left-8 h-24 w-44 rounded-3xl border border-powerx-cyan/20 motion-safe:animate-powerx-float"
                />
            </div>
            <header
                class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-6 motion-safe:animate-in motion-safe:duration-700 motion-safe:fade-in-0 motion-safe:slide-in-from-top-4 sm:px-6 lg:px-8"
            >
                <Link :href="home()" class="flex min-w-0 items-center gap-3">
                    <span
                        class="flex size-12 items-center justify-center overflow-hidden rounded-2xl bg-white p-1 shadow-[0_0_34px_rgba(255,193,7,0.45)]"
                    >
                        <AppLogoIcon class="size-full" />
                    </span>
                    <span class="grid leading-none">
                        <span class="text-xl font-black tracking-wide">
                            POWER<span class="text-powerx-yellow">X</span>
                        </span>
                        <span
                            class="text-[11px] font-bold tracking-[0.28em] text-muted-foreground uppercase dark:text-white/60"
                        >
                            Course details
                        </span>
                    </span>
                </Link>
                <div class="flex items-center gap-2 sm:gap-3">
                    <PublicThemeSwitcher />
                    <Link
                        :href="coursesIndex()"
                        class="inline-flex items-center gap-2 rounded-full border border-border px-4 py-2 text-sm font-bold text-muted-foreground transition hover:border-powerx-yellow hover:text-powerx-yellow dark:border-white/15 dark:text-white/80"
                    >
                        <ArrowLeft class="size-4" /> Courses
                    </Link>
                </div>
            </header>
            <div
                class="mx-auto grid max-w-7xl gap-10 px-6 py-16 lg:grid-cols-[1.05fr_0.95fr] lg:px-8 lg:py-24"
            >
                <div class="min-w-0">
                    <MotionReveal class="flex flex-wrap gap-3">
                        <BrandStatusBadge
                            :label="course.category ?? 'PowerX course'"
                            tone="warning"
                        />
                        <BrandStatusBadge
                            :label="`${course.deliveryMode} delivery`"
                            tone="info"
                        />
                    </MotionReveal>
                    <MotionReveal
                        as="h1"
                        :delay="120"
                        class="mt-8 max-w-4xl text-4xl leading-[0.95] font-black break-words uppercase sm:text-6xl lg:text-7xl"
                    >
                        {{ course.title }}
                    </MotionReveal>
                    <MotionReveal
                        as="p"
                        :delay="220"
                        class="mt-6 max-w-2xl text-lg leading-8 text-muted-foreground dark:text-white/70"
                    >
                        {{ course.description ?? course.summary }}
                    </MotionReveal>
                    <div class="mt-10 grid gap-4 sm:grid-cols-3">
                        <MotionReveal
                            :delay="300"
                            class="rounded-2xl border border-border bg-muted/70 p-5 dark:border-white/10 dark:bg-white/[0.05]"
                        >
                            <p
                                class="text-xs font-black tracking-[0.24em] text-powerx-yellow uppercase"
                            >
                                Starts from
                            </p>
                            <p class="mt-3 text-3xl font-black">
                                {{
                                    money(
                                        course.lowestPackagePrice,
                                        course.currency,
                                    )
                                }}
                            </p>
                        </MotionReveal>
                        <MotionReveal
                            :delay="390"
                            class="rounded-2xl border border-border bg-muted/70 p-5 dark:border-white/10 dark:bg-white/[0.05]"
                        >
                            <p
                                class="text-xs font-black tracking-[0.24em] text-powerx-yellow uppercase"
                            >
                                Modules
                            </p>
                            <p class="mt-3 text-3xl font-black">
                                {{ course.modules.length }}
                            </p>
                        </MotionReveal>
                        <MotionReveal
                            :delay="480"
                            class="rounded-2xl border border-border bg-muted/70 p-5 dark:border-white/10 dark:bg-white/[0.05]"
                        >
                            <p
                                class="text-xs font-black tracking-[0.24em] text-powerx-yellow uppercase"
                            >
                                Questions
                            </p>
                            <p class="mt-3 text-3xl font-black">
                                {{ course.questionsCount }}
                            </p>
                        </MotionReveal>
                    </div>
                </div>
                <MotionReveal
                    as="aside"
                    id="registration"
                    direction="right"
                    :delay="220"
                    class="rounded-[2rem] border border-border bg-card/90 p-5 shadow-[0_36px_100px_rgba(0,0,0,0.45)] backdrop-blur dark:border-white/10 dark:bg-white/[0.06]"
                >
                    <div
                        class="rounded-[1.5rem] border border-border bg-card p-6 dark:border-white/10 dark:bg-powerx-navy"
                    >
                        <p
                            class="text-xs font-black tracking-[0.26em] text-powerx-yellow uppercase"
                        >
                            Admissions request
                        </p>
                        <h2 class="mt-3 text-2xl font-black">
                            Register interest for this course
                        </h2>
                        <p
                            class="mt-2 text-sm leading-6 text-muted-foreground dark:text-white/60"
                        >
                            Submit your profile. PowerX will confirm documents,
                            payment method, and batch timing before access
                            opens.
                        </p>
                        <Form
                            v-bind="storeRegistration.form(course.slug)"
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
                                <span
                                    class="text-sm font-bold text-muted-foreground dark:text-white/75"
                                >
                                    Full name
                                </span>
                                <input
                                    name="full_name"
                                    class="h-12 rounded-2xl border border-border bg-background px-4 text-foreground outline-none placeholder:text-muted-foreground focus:border-powerx-yellow dark:border-white/10 dark:bg-white/[0.07] dark:text-white dark:placeholder:text-white/35"
                                    placeholder="Student full name"
                                />
                                <span
                                    v-if="errors.full_name"
                                    class="text-sm text-powerx-yellow"
                                >
                                    {{ errors.full_name }}
                                </span>
                            </label>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <label class="grid gap-2">
                                    <span
                                        class="text-sm font-bold text-muted-foreground dark:text-white/75"
                                    >
                                        Email
                                    </span>
                                    <input
                                        name="email"
                                        type="email"
                                        class="h-12 rounded-2xl border border-border bg-background px-4 text-foreground outline-none placeholder:text-muted-foreground focus:border-powerx-yellow dark:border-white/10 dark:bg-white/[0.07] dark:text-white dark:placeholder:text-white/35"
                                        placeholder="name@example.com"
                                    />
                                    <span
                                        v-if="errors.email"
                                        class="text-sm text-powerx-yellow"
                                    >
                                        {{ errors.email }}
                                    </span>
                                </label>
                                <label class="grid gap-2">
                                    <span
                                        class="text-sm font-bold text-muted-foreground dark:text-white/75"
                                    >
                                        Mobile
                                    </span>
                                    <input
                                        name="mobile"
                                        class="h-12 rounded-2xl border border-border bg-background px-4 text-foreground outline-none placeholder:text-muted-foreground focus:border-powerx-yellow dark:border-white/10 dark:bg-white/[0.07] dark:text-white dark:placeholder:text-white/35"
                                        placeholder="+974..."
                                    />
                                    <span
                                        v-if="errors.mobile"
                                        class="text-sm text-powerx-yellow"
                                    >
                                        {{ errors.mobile }}
                                    </span>
                                </label>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <label class="grid gap-2">
                                    <span
                                        class="text-sm font-bold text-muted-foreground dark:text-white/75"
                                    >
                                        Profession
                                    </span>
                                    <input
                                        name="profession"
                                        class="h-12 rounded-2xl border border-border bg-background px-4 text-foreground outline-none placeholder:text-muted-foreground focus:border-powerx-yellow dark:border-white/10 dark:bg-white/[0.07] dark:text-white dark:placeholder:text-white/35"
                                        placeholder="Technician, engineer..."
                                    />
                                </label>
                                <label class="grid gap-2">
                                    <span
                                        class="text-sm font-bold text-muted-foreground dark:text-white/75"
                                    >
                                        Qatar location
                                    </span>
                                    <input
                                        name="qatar_location"
                                        class="h-12 rounded-2xl border border-border bg-background px-4 text-foreground outline-none placeholder:text-muted-foreground focus:border-powerx-yellow dark:border-white/10 dark:bg-white/[0.07] dark:text-white dark:placeholder:text-white/35"
                                        placeholder="Doha, Al Wakrah..."
                                    />
                                </label>
                            </div>
                            <label class="grid gap-2">
                                <span
                                    class="text-sm font-bold text-muted-foreground dark:text-white/75"
                                >
                                    Company
                                </span>
                                <input
                                    name="company_name"
                                    class="h-12 rounded-2xl border border-border bg-background px-4 text-foreground outline-none placeholder:text-muted-foreground focus:border-powerx-yellow dark:border-white/10 dark:bg-white/[0.07] dark:text-white dark:placeholder:text-white/35"
                                    placeholder="Optional company name"
                                />
                            </label>
                            <label class="grid gap-2">
                                <span
                                    class="text-sm font-bold text-muted-foreground dark:text-white/75"
                                >
                                    Package
                                </span>
                                <select
                                    name="course_package_id"
                                    class="h-12 rounded-2xl border border-border bg-background px-4 text-foreground outline-none focus:border-powerx-yellow dark:border-white/10 dark:bg-powerx-panel dark:text-white"
                                >
                                    <option value="">Let PowerX advise</option>
                                    <option
                                        v-for="coursePackage in course.packages"
                                        :key="coursePackage.id"
                                        :value="coursePackage.id"
                                    >
                                        {{ coursePackage.name }} -
                                        {{
                                            money(
                                                coursePackage.discountPrice ??
                                                    coursePackage.price,
                                                coursePackage.currency,
                                            )
                                        }}
                                    </option>
                                </select>
                                <span
                                    v-if="errors.course_package_id"
                                    class="text-sm text-powerx-yellow"
                                >
                                    {{ errors.course_package_id }}
                                </span>
                            </label>
                            <label class="grid gap-2">
                                <span
                                    class="text-sm font-bold text-muted-foreground dark:text-white/75"
                                >
                                    Preferred schedule
                                </span>
                                <input
                                    name="preferred_schedule"
                                    class="h-12 rounded-2xl border border-border bg-background px-4 text-foreground outline-none placeholder:text-muted-foreground focus:border-powerx-yellow dark:border-white/10 dark:bg-white/[0.07] dark:text-white dark:placeholder:text-white/35"
                                    placeholder="Weekend, evening, intensive..."
                                />
                            </label>
                            <label class="grid gap-2">
                                <span
                                    class="text-sm font-bold text-muted-foreground dark:text-white/75"
                                >
                                    Notes
                                </span>
                                <textarea
                                    name="message"
                                    rows="4"
                                    class="rounded-2xl border border-border bg-background px-4 py-3 text-foreground outline-none placeholder:text-muted-foreground focus:border-powerx-yellow dark:border-white/10 dark:bg-white/[0.07] dark:text-white dark:placeholder:text-white/35"
                                    placeholder="Share payment, batch, or corporate training details."
                                />
                            </label>
                            <Button
                                type="submit"
                                :disabled="processing"
                                class="h-12 rounded-full bg-powerx-yellow text-sm font-black tracking-wide text-powerx-navy uppercase hover:-translate-y-0.5 hover:bg-white disabled:opacity-60 motion-safe:transition-all motion-safe:duration-300 motion-reduce:hover:translate-y-0"
                            >
                                {{
                                    processing
                                        ? 'Submitting...'
                                        : 'Submit registration request'
                                }}
                                <Send class="size-4" />
                            </Button>
                            <p
                                v-if="wasSuccessful"
                                class="rounded-2xl border border-powerx-success/30 bg-powerx-success/10 px-4 py-3 text-sm font-bold text-powerx-success motion-safe:animate-in motion-safe:duration-300 motion-safe:fade-in-0 motion-safe:slide-in-from-bottom-2"
                            >
                                Request received. Admissions will review and
                                follow up with payment instructions.
                            </p>
                        </Form>
                    </div>
                </MotionReveal>
            </div>
        </section>
        <MotionReveal>
            <PowerXAssistantPanel :assistant="aiAssistant" />
        </MotionReveal>
        <section
            class="mx-auto grid max-w-7xl gap-10 px-6 py-16 lg:grid-cols-[0.92fr_1.08fr] lg:px-8"
        >
            <MotionReveal direction="left" class="min-w-0">
                <p
                    class="text-sm font-black tracking-[0.28em] text-powerx-yellow uppercase"
                >
                    Course structure
                </p>
                <h2 class="mt-3 text-3xl font-black">
                    Modules, lessons, and practical readiness.
                </h2>
                <p class="mt-4 text-muted-foreground dark:text-white/60">
                    Lessons stay private by default. Preview flags and package
                    access rules are ready for the LMS milestone.
                </p>
                <div class="mt-8 grid gap-4">
                    <div class="flex items-start gap-4">
                        <BadgeCheck class="mt-1 size-6 text-powerx-success" />
                        <div>
                            <h3 class="font-black">Certificate-ready</h3>
                            <p
                                class="mt-1 text-sm leading-6 text-muted-foreground dark:text-white/60"
                            >
                                Packages can include certificate eligibility
                                after payment, attendance, and assessment rules.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <CalendarCheck class="mt-1 size-6 text-powerx-yellow" />
                        <div>
                            <h3 class="font-black">Batch scheduling</h3>
                            <p
                                class="mt-1 text-sm leading-6 text-muted-foreground dark:text-white/60"
                            >
                                Admissions requests connect to upcoming
                                instructor-led training batches.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <ShieldCheck class="mt-1 size-6 text-powerx-cyan" />
                        <div>
                            <h3 class="font-black">Access control</h3>
                            <p
                                class="mt-1 text-sm leading-6 text-muted-foreground dark:text-white/60"
                            >
                                Paid content, proofs, and generated PDFs use
                                private storage collections.
                            </p>
                        </div>
                    </div>
                </div>
            </MotionReveal>
            <div class="grid min-w-0 gap-4">
                <MotionReveal
                    v-for="(module, moduleIndex) in course.modules"
                    :key="module.id"
                    as="article"
                    direction="right"
                    :delay="(moduleIndex % 3) * 100"
                    class="rounded-[1.5rem] border border-border bg-muted/70 p-5 hover:-translate-y-0.5 hover:border-powerx-yellow/60 hover:shadow-[0_24px_70px_rgba(255,193,7,0.12)] motion-safe:transition-all motion-safe:duration-300 motion-reduce:hover:translate-y-0 dark:border-white/10 dark:bg-white/[0.05]"
                >
                    <div class="flex items-start gap-4">
                        <span
                            class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-powerx-yellow text-sm font-black text-powerx-navy shadow-[0_0_22px_rgba(255,193,7,0.28)]"
                        >
                            {{ String(moduleIndex + 1).padStart(2, '0') }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-xl font-black">
                                {{ module.title }}
                            </h3>
                            <p
                                class="mt-2 text-sm leading-6 text-muted-foreground dark:text-white/60"
                            >
                                {{ module.summary }}
                            </p>
                            <div class="mt-4 grid gap-2">
                                <div
                                    v-for="lesson in module.lessons"
                                    :key="lesson.id"
                                    class="group/lesson flex items-center justify-between gap-4 rounded-2xl border border-border bg-card/70 px-4 py-3 hover:-translate-y-0.5 hover:border-powerx-yellow/50 hover:shadow-sm motion-safe:transition-all motion-safe:duration-300 motion-reduce:hover:translate-y-0 dark:border-white/10 dark:bg-white/[0.04]"
                                >
                                    <div class="flex items-center gap-3">
                                        <component
                                            :is="lessonIcon(lesson.lessonType)"
                                            class="size-5 text-powerx-yellow group-hover/lesson:scale-110 motion-safe:transition-transform motion-safe:duration-300"
                                        />
                                        <div>
                                            <p class="font-bold">
                                                {{ lesson.title }}
                                            </p>
                                            <p
                                                class="text-xs font-bold tracking-wide text-muted-foreground uppercase dark:text-white/45"
                                            >
                                                {{ lesson.lessonType }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div
                                            v-if="lesson.isPreview"
                                            class="flex flex-wrap justify-end gap-2"
                                        >
                                            <Form
                                                v-bind="
                                                    storePreviewEvent.form(
                                                        course.slug,
                                                    )
                                                "
                                                #default="{ processing }"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="event_type"
                                                    value="started"
                                                />
                                                <input
                                                    type="hidden"
                                                    name="lesson_id"
                                                    :value="lesson.id"
                                                />
                                                <input
                                                    v-for="field in trackingEntries"
                                                    :key="`start-${lesson.id}-${field.name}`"
                                                    type="hidden"
                                                    :name="field.name"
                                                    :value="field.value"
                                                />
                                                <button
                                                    type="submit"
                                                    :disabled="processing"
                                                    class="rounded-full border border-powerx-success/30 bg-powerx-success/10 px-3 py-1 text-xs font-bold text-powerx-success hover:-translate-y-0.5 hover:border-powerx-success disabled:opacity-60 motion-safe:transition-all motion-safe:duration-200 motion-reduce:hover:translate-y-0"
                                                >
                                                    Start preview
                                                </button>
                                            </Form>
                                            <Form
                                                v-bind="
                                                    storePreviewEvent.form(
                                                        course.slug,
                                                    )
                                                "
                                                #default="{ processing }"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="event_type"
                                                    value="completed"
                                                />
                                                <input
                                                    type="hidden"
                                                    name="lesson_id"
                                                    :value="lesson.id"
                                                />
                                                <input
                                                    v-for="field in trackingEntries"
                                                    :key="`complete-${lesson.id}-${field.name}`"
                                                    type="hidden"
                                                    :name="field.name"
                                                    :value="field.value"
                                                />
                                                <button
                                                    type="submit"
                                                    :disabled="processing"
                                                    class="rounded-full border border-border px-3 py-1 text-xs font-bold text-muted-foreground hover:-translate-y-0.5 hover:border-powerx-yellow hover:text-powerx-yellow disabled:opacity-60 motion-safe:transition-all motion-safe:duration-200 motion-reduce:hover:translate-y-0 dark:border-white/15 dark:text-white/70"
                                                >
                                                    Complete preview
                                                </button>
                                            </Form>
                                        </div>
                                        <span
                                            class="text-sm text-muted-foreground dark:text-white/50"
                                        >
                                            {{
                                                lesson.durationMinutes ?? 'TBD'
                                            }}
                                            min
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </MotionReveal>
                <MotionReveal
                    v-if="!course.modules.length"
                    class="rounded-[1.5rem] border border-border bg-muted/70 p-8 text-center dark:border-white/10 dark:bg-white/[0.05]"
                >
                    <CheckCircle2 class="mx-auto size-10 text-powerx-yellow" />
                    <h3 class="mt-4 text-2xl font-black">
                        Curriculum being prepared
                    </h3>
                    <p class="mt-2 text-muted-foreground dark:text-white/60">
                        Modules will appear here as operations publishes the LMS
                        structure.
                    </p>
                </MotionReveal>
            </div>
        </section>
    </main>
</template>
