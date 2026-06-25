<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    BookOpenCheck,
    Building2,
    CalendarCheck,
    CheckCircle2,
    GraduationCap,
    Mail,
    Phone,
    ShieldCheck,
    Zap,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import BrandStatusBadge from '@/components/powerx/BrandStatusBadge.vue';
import MetricCard from '@/components/powerx/MetricCard.vue';
import MotionReveal from '@/components/powerx/MotionReveal.vue';
import PowerXAssistantPanel from '@/components/powerx/PowerXAssistantPanel.vue';
import PublicThemeSwitcher from '@/components/powerx/PublicThemeSwitcher.vue';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';
import { index as coursesIndex, show as showCourse } from '@/routes/courses';
import { store as storeLead } from '@/routes/leads';

interface CoursePackage {
    id: number;
    name: string;
    currency: string;
    price: string | number;
    discountPrice: string | number | null;
    validityDays: number | null;
    maxExamAttempts: number | null;
    includesCertificate: boolean;
}

interface CourseCard {
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
    courses: CourseCard[];
    leadCourseOptions: Array<{ id: number; title: string }>;
    aiAssistant: PowerXAssistantConfig;
}>();

const page = usePage();

const trackingFields = computed(() => {
    const params = new URLSearchParams(page.url.split('?')[1] ?? '');

    return {
        source: params.get('source') ?? 'catalog',
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

const money = (amount: string | number | null, currency: string): string =>
    new Intl.NumberFormat('en-QA', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(Number(amount ?? 0));
</script>
<template>
    <Head title="PowerX Courses" />
    <main
        class="min-h-screen overflow-x-hidden bg-background text-foreground dark:bg-powerx-ink dark:text-white"
    >
        <section
            class="relative isolate overflow-hidden border-b border-border dark:border-white/10"
        >
            <div
                class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_18%_18%,rgba(255,193,7,0.28),transparent_28%),radial-gradient(circle_at_82%_8%,rgba(0,107,255,0.12),transparent_26%),linear-gradient(135deg,#f8fafc_0%,#edf3f8_48%,#dce9f5_100%)] dark:hidden"
            />
            <div
                class="absolute inset-0 -z-10 hidden bg-[radial-gradient(circle_at_18%_18%,rgba(255,193,7,0.22),transparent_28%),radial-gradient(circle_at_82%_8%,rgba(0,107,255,0.18),transparent_26%),linear-gradient(135deg,#020101_0%,#071523_46%,#0b2034_100%)] dark:block"
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
                    class="absolute top-24 right-8 h-24 w-40 rounded-3xl border border-powerx-yellow/20 motion-safe:animate-powerx-float"
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
                            Course catalog
                        </span>
                    </span>
                </Link>
                <nav
                    class="flex items-center gap-2 text-sm font-semibold sm:gap-3"
                >
                    <PublicThemeSwitcher />
                    <Link
                        :href="home()"
                        class="rounded-full border border-border px-4 py-2 text-muted-foreground transition hover:border-powerx-yellow hover:text-powerx-yellow dark:border-white/15 dark:text-white/80"
                    >
                        Home
                    </Link>
                    <Link
                        :href="coursesIndex()"
                        class="rounded-full bg-powerx-yellow px-5 py-2 font-black text-powerx-navy shadow-[0_0_24px_rgba(255,193,7,0.35)] transition hover:bg-white"
                    >
                        Courses
                    </Link>
                </nav>
            </header>
            <div
                class="mx-auto grid max-w-7xl gap-10 px-6 py-16 lg:grid-cols-[1.05fr_0.95fr] lg:px-8 lg:py-24"
            >
                <div>
                    <MotionReveal class="flex flex-wrap gap-3">
                        <BrandStatusBadge
                            label="Public admissions open"
                            tone="warning"
                        />
                        <BrandStatusBadge
                            label="Manual payment approval"
                            tone="info"
                        />
                    </MotionReveal>
                    <MotionReveal
                        as="h1"
                        :delay="120"
                        class="mt-8 max-w-4xl text-5xl leading-[0.95] font-black uppercase sm:text-6xl lg:text-7xl"
                    >
                        Choose a PowerX course and start your training request.
                    </MotionReveal>
                    <MotionReveal
                        as="p"
                        :delay="220"
                        class="mt-6 max-w-2xl text-lg leading-8 text-muted-foreground dark:text-white/70"
                    >
                        Browse Kahramaa exam preparation, electrical safety, and
                        practical training programs. Submit an inquiry or open a
                        course to request registration.
                    </MotionReveal>
                    <div class="mt-10 grid gap-4 sm:grid-cols-3">
                        <MotionReveal :delay="300">
                            <MetricCard
                                label="Catalog"
                                :value="String(courses.length)"
                                detail="Published programs ready for lead capture."
                                class="h-full"
                            >
                                <template #icon>
                                    <BookOpenCheck class="size-5" />
                                </template>
                            </MetricCard>
                        </MotionReveal>
                        <MotionReveal :delay="390">
                            <MetricCard
                                label="Admissions"
                                value="Pending"
                                detail="Registration requests start in review."
                                class="h-full"
                            >
                                <template #icon>
                                    <GraduationCap class="size-5" />
                                </template>
                            </MetricCard>
                        </MotionReveal>
                        <MotionReveal :delay="480">
                            <MetricCard
                                label="Access"
                                value="Private"
                                detail="Content unlocks after payment approval."
                                class="h-full"
                            >
                                <template #icon>
                                    <ShieldCheck class="size-5" />
                                </template>
                            </MetricCard>
                        </MotionReveal>
                    </div>
                </div>
                <MotionReveal
                    as="aside"
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
                            Quick inquiry
                        </p>
                        <h2 class="mt-3 text-2xl font-black">
                            Ask PowerX to call you back
                        </h2>
                        <p
                            class="mt-2 text-sm leading-6 text-muted-foreground dark:text-white/60"
                        >
                            Use this for course questions, corporate batches, or
                            schedule guidance.
                        </p>
                        <Form
                            v-bind="storeLead.form()"
                            reset-on-success
                            class="mt-6 grid gap-4"
                            #default="{ errors, processing, wasSuccessful }"
                        >
                            <input
                                type="hidden"
                                name="source"
                                :value="trackingFields.source"
                            />
                            <input
                                type="hidden"
                                name="campaign"
                                :value="trackingFields.campaign"
                            />
                            <input
                                type="hidden"
                                name="utm_source"
                                :value="trackingFields.utm_source"
                            />
                            <input
                                type="hidden"
                                name="utm_medium"
                                :value="trackingFields.utm_medium"
                            />
                            <input
                                type="hidden"
                                name="utm_campaign"
                                :value="trackingFields.utm_campaign"
                            />
                            <input
                                type="hidden"
                                name="utm_content"
                                :value="trackingFields.utm_content"
                            />
                            <input
                                type="hidden"
                                name="utm_term"
                                :value="trackingFields.utm_term"
                            />
                            <input
                                type="hidden"
                                name="referral_name"
                                :value="trackingFields.referral_name"
                            />
                            <input
                                type="hidden"
                                name="referral_phone"
                                :value="trackingFields.referral_phone"
                            />
                            <input
                                type="hidden"
                                name="referral_email"
                                :value="trackingFields.referral_email"
                            />
                            <input
                                type="hidden"
                                name="referral_relationship"
                                :value="trackingFields.referral_relationship"
                            />
                            <label class="grid gap-2">
                                <span
                                    class="text-sm font-bold text-muted-foreground dark:text-white/75"
                                >
                                    Name
                                </span>
                                <input
                                    name="name"
                                    class="h-12 rounded-2xl border border-border bg-background px-4 text-foreground outline-none placeholder:text-muted-foreground focus:border-powerx-yellow dark:border-white/10 dark:bg-white/[0.07] dark:text-white dark:placeholder:text-white/35"
                                    placeholder="Your full name"
                                />
                                <span
                                    v-if="errors.name"
                                    class="text-sm text-powerx-yellow"
                                >
                                    {{ errors.name }}
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
                                        name="phone"
                                        class="h-12 rounded-2xl border border-border bg-background px-4 text-foreground outline-none placeholder:text-muted-foreground focus:border-powerx-yellow dark:border-white/10 dark:bg-white/[0.07] dark:text-white dark:placeholder:text-white/35"
                                        placeholder="+974..."
                                    />
                                    <span
                                        v-if="errors.phone"
                                        class="text-sm text-powerx-yellow"
                                    >
                                        {{ errors.phone }}
                                    </span>
                                </label>
                            </div>
                            <label class="grid gap-2">
                                <span
                                    class="text-sm font-bold text-muted-foreground dark:text-white/75"
                                >
                                    Course interest
                                </span>
                                <select
                                    name="course_id"
                                    class="h-12 rounded-2xl border border-border bg-background px-4 text-foreground outline-none focus:border-powerx-yellow dark:border-white/10 dark:bg-powerx-panel dark:text-white"
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
                                    class="text-sm text-powerx-yellow"
                                >
                                    {{ errors.course_id }}
                                </span>
                            </label>
                            <label class="grid gap-2">
                                <span
                                    class="text-sm font-bold text-muted-foreground dark:text-white/75"
                                >
                                    Message
                                </span>
                                <textarea
                                    name="message"
                                    rows="4"
                                    class="rounded-2xl border border-border bg-background px-4 py-3 text-foreground outline-none placeholder:text-muted-foreground focus:border-powerx-yellow dark:border-white/10 dark:bg-white/[0.07] dark:text-white dark:placeholder:text-white/35"
                                    placeholder="Tell us what you want to learn or when you prefer to attend."
                                />
                                <span
                                    v-if="errors.message"
                                    class="text-sm text-powerx-yellow"
                                >
                                    {{ errors.message }}
                                </span>
                            </label>
                            <Button
                                type="submit"
                                :disabled="processing"
                                class="h-12 rounded-full bg-powerx-yellow text-sm font-black tracking-wide text-powerx-navy uppercase hover:-translate-y-0.5 hover:bg-white disabled:opacity-60 motion-safe:transition-all motion-safe:duration-300 motion-reduce:hover:translate-y-0"
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
                                class="rounded-2xl border border-powerx-success/30 bg-powerx-success/10 px-4 py-3 text-sm font-bold text-powerx-success motion-safe:animate-in motion-safe:duration-300 motion-safe:fade-in-0 motion-safe:slide-in-from-bottom-2"
                            >
                                Inquiry received. We will follow up shortly.
                            </p>
                        </Form>
                    </div>
                </MotionReveal>
            </div>
        </section>
        <MotionReveal>
            <PowerXAssistantPanel :assistant="aiAssistant" />
        </MotionReveal>
        <section class="mx-auto max-w-7xl px-6 py-16 lg:px-8">
            <div
                class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end"
            >
                <MotionReveal direction="left">
                    <p
                        class="text-sm font-black tracking-[0.28em] text-powerx-yellow uppercase"
                    >
                        Published courses
                    </p>
                    <h2 class="mt-3 text-3xl font-black">
                        Electrical training programs ready for admissions.
                    </h2>
                </MotionReveal>
                <MotionReveal
                    direction="right"
                    class="flex items-center gap-2 text-sm text-muted-foreground dark:text-white/60"
                >
                    <Mail class="size-4 text-powerx-yellow" />
                    <span>Need corporate pricing? Use the inquiry form.</span>
                </MotionReveal>
            </div>
            <div v-if="courses.length" class="mt-10 grid gap-6 lg:grid-cols-3">
                <MotionReveal
                    v-for="(course, index) in courses"
                    :key="course.id"
                    as="article"
                    :delay="(index % 3) * 110"
                    class="group relative flex min-h-full flex-col overflow-hidden rounded-[1.75rem] border border-border bg-muted/70 p-5 shadow-[0_24px_70px_rgba(0,0,0,0.28)] hover:-translate-y-1 hover:border-powerx-yellow/50 hover:bg-white/[0.08] hover:shadow-[0_30px_80px_rgba(255,193,7,0.16)] motion-safe:transition-all motion-safe:duration-300 motion-reduce:hover:translate-y-0 dark:border-white/10 dark:bg-white/[0.05]"
                >
                    <span
                        class="pointer-events-none absolute inset-y-0 -left-24 w-20 rotate-12 bg-gradient-to-r from-transparent via-white/30 to-transparent opacity-0 group-hover:left-[120%] group-hover:opacity-60 motion-safe:transition-all motion-safe:duration-700 motion-reduce:hidden"
                    />
                    <div class="flex items-start justify-between gap-4">
                        <BrandStatusBadge
                            :label="course.category ?? 'PowerX course'"
                            tone="warning"
                        />
                        <span
                            v-if="course.isFeatured"
                            class="rounded-full border border-powerx-success/30 bg-powerx-success/10 px-3 py-1 text-xs font-black tracking-wide text-powerx-success uppercase"
                        >
                            Featured
                        </span>
                    </div>
                    <h3 class="mt-5 text-2xl leading-tight font-black">
                        {{ course.title }}
                    </h3>
                    <p
                        class="mt-3 min-h-20 text-sm leading-6 text-muted-foreground dark:text-white/60"
                    >
                        {{ course.summary }}
                    </p>
                    <div class="mt-5 grid gap-3 text-sm">
                        <div
                            class="flex items-center justify-between gap-3 rounded-2xl border border-border bg-card/70 px-4 py-3 dark:border-white/10 dark:bg-white/[0.04]"
                        >
                            <span
                                class="text-muted-foreground dark:text-white/55"
                                >Starts from</span
                            >
                            <span class="font-black text-powerx-yellow">
                                {{
                                    money(
                                        course.lowestPackagePrice,
                                        course.currency,
                                    )
                                }}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div
                                class="rounded-2xl border border-border bg-card/70 px-4 py-3 dark:border-white/10 dark:bg-white/[0.04]"
                            >
                                <p
                                    class="text-muted-foreground dark:text-white/45"
                                >
                                    Modules
                                </p>
                                <p class="mt-1 font-black">
                                    {{ course.modulesCount }}
                                </p>
                            </div>
                            <div
                                class="rounded-2xl border border-border bg-card/70 px-4 py-3 dark:border-white/10 dark:bg-white/[0.04]"
                            >
                                <p
                                    class="text-muted-foreground dark:text-white/45"
                                >
                                    Mode
                                </p>
                                <p class="mt-1 font-black capitalize">
                                    {{ course.deliveryMode }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <Link
                        :href="showCourse(course.slug)"
                        class="mt-6 inline-flex items-center justify-center gap-2 rounded-full bg-powerx-yellow px-5 py-3 text-sm font-black tracking-wide text-powerx-navy uppercase group-hover:bg-white motion-safe:transition-all motion-safe:duration-300"
                    >
                        View course
                        <ArrowRight
                            class="size-4 group-hover:translate-x-1 motion-safe:transition-transform motion-safe:duration-300 motion-reduce:group-hover:translate-x-0"
                        />
                    </Link>
                </MotionReveal>
            </div>
            <MotionReveal
                v-else
                class="mt-10 rounded-[2rem] border border-border bg-muted/70 p-8 text-center dark:border-white/10 dark:bg-white/[0.05]"
            >
                <Zap class="mx-auto size-10 text-powerx-yellow" />
                <h3 class="mt-4 text-2xl font-black">Courses coming soon</h3>
                <p class="mt-2 text-muted-foreground dark:text-white/60">
                    Publish courses from operations to show them in the public
                    catalog.
                </p>
            </MotionReveal>
        </section>
        <section
            class="border-t border-border bg-card dark:border-white/10 dark:bg-powerx-navy"
        >
            <div
                class="mx-auto grid max-w-7xl gap-6 px-6 py-12 sm:grid-cols-3 lg:px-8"
            >
                <MotionReveal class="flex items-start gap-4">
                    <CheckCircle2 class="mt-1 size-6 text-powerx-success" />
                    <div>
                        <h3 class="font-black">Lead captured</h3>
                        <p
                            class="mt-1 text-sm leading-6 text-muted-foreground dark:text-white/60"
                        >
                            Sales can qualify inquiries and assign follow-up.
                        </p>
                    </div>
                </MotionReveal>
                <MotionReveal :delay="100" class="flex items-start gap-4">
                    <CalendarCheck class="mt-1 size-6 text-powerx-yellow" />
                    <div>
                        <h3 class="font-black">Registration requested</h3>
                        <p
                            class="mt-1 text-sm leading-6 text-muted-foreground dark:text-white/60"
                        >
                            Admissions reviews profile, package, and schedule.
                        </p>
                    </div>
                </MotionReveal>
                <MotionReveal :delay="200" class="flex items-start gap-4">
                    <Building2 class="mt-1 size-6 text-powerx-cyan" />
                    <div>
                        <h3 class="font-black">Corporate-ready</h3>
                        <p
                            class="mt-1 text-sm leading-6 text-muted-foreground dark:text-white/60"
                        >
                            Company details link prospects and enrollments.
                        </p>
                    </div>
                </MotionReveal>
            </div>
        </section>
    </main>
</template>
