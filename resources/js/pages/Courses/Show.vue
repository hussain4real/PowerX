<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
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
import type { Component } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import BrandStatusBadge from '@/components/powerx/BrandStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';
import { index as coursesIndex } from '@/routes/courses';
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

defineProps<{
    course: CourseDetail;
}>();

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

    <main class="min-h-screen bg-powerx-ink text-white">
        <section
            class="relative isolate overflow-hidden border-b border-white/10"
        >
            <div
                class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_18%_18%,rgba(255,193,7,0.22),transparent_28%),radial-gradient(circle_at_82%_10%,rgba(13,202,240,0.16),transparent_26%),linear-gradient(135deg,#020101_0%,#071523_46%,#0b2034_100%)]"
            />
            <div
                class="absolute inset-0 -z-10 [background-image:linear-gradient(rgba(255,255,255,0.08)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.08)_1px,transparent_1px)] [background-size:72px_72px] opacity-25"
            />

            <header
                class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-6 py-6 lg:px-8"
            >
                <Link :href="home()" class="flex items-center gap-3">
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
                            class="text-[11px] font-bold tracking-[0.28em] text-white/60 uppercase"
                        >
                            Course details
                        </span>
                    </span>
                </Link>

                <Link
                    :href="coursesIndex()"
                    class="inline-flex items-center gap-2 rounded-full border border-white/15 px-4 py-2 text-sm font-bold text-white/80 transition hover:border-powerx-yellow hover:text-powerx-yellow"
                >
                    <ArrowLeft class="size-4" />
                    Courses
                </Link>
            </header>

            <div
                class="mx-auto grid max-w-7xl gap-10 px-6 py-16 lg:grid-cols-[1.05fr_0.95fr] lg:px-8 lg:py-24"
            >
                <div>
                    <div class="flex flex-wrap gap-3">
                        <BrandStatusBadge
                            :label="course.category ?? 'PowerX course'"
                            tone="warning"
                        />
                        <BrandStatusBadge
                            :label="`${course.deliveryMode} delivery`"
                            tone="info"
                        />
                    </div>

                    <h1
                        class="mt-8 max-w-4xl text-5xl leading-[0.95] font-black tracking-tight uppercase sm:text-6xl lg:text-7xl"
                    >
                        {{ course.title }}
                    </h1>
                    <p class="mt-6 max-w-2xl text-lg leading-8 text-white/70">
                        {{ course.description ?? course.summary }}
                    </p>

                    <div class="mt-10 grid gap-4 sm:grid-cols-3">
                        <div
                            class="rounded-2xl border border-white/10 bg-white/[0.05] p-5"
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
                        </div>
                        <div
                            class="rounded-2xl border border-white/10 bg-white/[0.05] p-5"
                        >
                            <p
                                class="text-xs font-black tracking-[0.24em] text-powerx-yellow uppercase"
                            >
                                Modules
                            </p>
                            <p class="mt-3 text-3xl font-black">
                                {{ course.modules.length }}
                            </p>
                        </div>
                        <div
                            class="rounded-2xl border border-white/10 bg-white/[0.05] p-5"
                        >
                            <p
                                class="text-xs font-black tracking-[0.24em] text-powerx-yellow uppercase"
                            >
                                Questions
                            </p>
                            <p class="mt-3 text-3xl font-black">
                                {{ course.questionsCount }}
                            </p>
                        </div>
                    </div>
                </div>

                <aside
                    id="registration"
                    class="rounded-[2rem] border border-white/10 bg-white/[0.06] p-5 shadow-[0_36px_100px_rgba(0,0,0,0.45)] backdrop-blur"
                >
                    <div
                        class="rounded-[1.5rem] border border-white/10 bg-powerx-navy p-6"
                    >
                        <p
                            class="text-xs font-black tracking-[0.26em] text-powerx-yellow uppercase"
                        >
                            Admissions request
                        </p>
                        <h2 class="mt-3 text-2xl font-black">
                            Register interest for this course
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-white/60">
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
                            <label class="grid gap-2">
                                <span class="text-sm font-bold text-white/75">
                                    Full name
                                </span>
                                <input
                                    name="full_name"
                                    class="h-12 rounded-2xl border border-white/10 bg-white/[0.07] px-4 text-white transition outline-none placeholder:text-white/35 focus:border-powerx-yellow"
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
                                        class="text-sm font-bold text-white/75"
                                    >
                                        Email
                                    </span>
                                    <input
                                        name="email"
                                        type="email"
                                        class="h-12 rounded-2xl border border-white/10 bg-white/[0.07] px-4 text-white transition outline-none placeholder:text-white/35 focus:border-powerx-yellow"
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
                                        class="text-sm font-bold text-white/75"
                                    >
                                        Mobile
                                    </span>
                                    <input
                                        name="mobile"
                                        class="h-12 rounded-2xl border border-white/10 bg-white/[0.07] px-4 text-white transition outline-none placeholder:text-white/35 focus:border-powerx-yellow"
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
                                        class="text-sm font-bold text-white/75"
                                    >
                                        Profession
                                    </span>
                                    <input
                                        name="profession"
                                        class="h-12 rounded-2xl border border-white/10 bg-white/[0.07] px-4 text-white transition outline-none placeholder:text-white/35 focus:border-powerx-yellow"
                                        placeholder="Technician, engineer..."
                                    />
                                </label>
                                <label class="grid gap-2">
                                    <span
                                        class="text-sm font-bold text-white/75"
                                    >
                                        Qatar location
                                    </span>
                                    <input
                                        name="qatar_location"
                                        class="h-12 rounded-2xl border border-white/10 bg-white/[0.07] px-4 text-white transition outline-none placeholder:text-white/35 focus:border-powerx-yellow"
                                        placeholder="Doha, Al Wakrah..."
                                    />
                                </label>
                            </div>

                            <label class="grid gap-2">
                                <span class="text-sm font-bold text-white/75">
                                    Company
                                </span>
                                <input
                                    name="company_name"
                                    class="h-12 rounded-2xl border border-white/10 bg-white/[0.07] px-4 text-white transition outline-none placeholder:text-white/35 focus:border-powerx-yellow"
                                    placeholder="Optional company name"
                                />
                            </label>

                            <label class="grid gap-2">
                                <span class="text-sm font-bold text-white/75">
                                    Package
                                </span>
                                <select
                                    name="course_package_id"
                                    class="h-12 rounded-2xl border border-white/10 bg-powerx-panel px-4 text-white transition outline-none focus:border-powerx-yellow"
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
                                <span class="text-sm font-bold text-white/75">
                                    Preferred schedule
                                </span>
                                <input
                                    name="preferred_schedule"
                                    class="h-12 rounded-2xl border border-white/10 bg-white/[0.07] px-4 text-white transition outline-none placeholder:text-white/35 focus:border-powerx-yellow"
                                    placeholder="Weekend, evening, intensive..."
                                />
                            </label>

                            <label class="grid gap-2">
                                <span class="text-sm font-bold text-white/75">
                                    Notes
                                </span>
                                <textarea
                                    name="message"
                                    rows="4"
                                    class="rounded-2xl border border-white/10 bg-white/[0.07] px-4 py-3 text-white transition outline-none placeholder:text-white/35 focus:border-powerx-yellow"
                                    placeholder="Share payment, batch, or corporate training details."
                                />
                            </label>

                            <Button
                                type="submit"
                                :disabled="processing"
                                class="h-12 rounded-full bg-powerx-yellow text-sm font-black tracking-wide text-powerx-navy uppercase hover:bg-white disabled:opacity-60"
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
                                class="rounded-2xl border border-powerx-success/30 bg-powerx-success/10 px-4 py-3 text-sm font-bold text-powerx-success"
                            >
                                Request received. Admissions will review and
                                follow up with payment instructions.
                            </p>
                        </Form>
                    </div>
                </aside>
            </div>
        </section>

        <section
            class="mx-auto grid max-w-7xl gap-10 px-6 py-16 lg:grid-cols-[0.92fr_1.08fr] lg:px-8"
        >
            <div>
                <p
                    class="text-sm font-black tracking-[0.28em] text-powerx-yellow uppercase"
                >
                    Course structure
                </p>
                <h2 class="mt-3 text-3xl font-black">
                    Modules, lessons, and practical readiness.
                </h2>
                <p class="mt-4 text-white/60">
                    Lessons stay private by default. Preview flags and package
                    access rules are ready for the LMS milestone.
                </p>

                <div class="mt-8 grid gap-4">
                    <div class="flex items-start gap-4">
                        <BadgeCheck class="mt-1 size-6 text-powerx-success" />
                        <div>
                            <h3 class="font-black">Certificate-ready</h3>
                            <p class="mt-1 text-sm leading-6 text-white/60">
                                Packages can include certificate eligibility
                                after payment, attendance, and assessment rules.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <CalendarCheck class="mt-1 size-6 text-powerx-yellow" />
                        <div>
                            <h3 class="font-black">Batch scheduling</h3>
                            <p class="mt-1 text-sm leading-6 text-white/60">
                                Admissions requests connect to upcoming
                                instructor-led training batches.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <ShieldCheck class="mt-1 size-6 text-powerx-cyan" />
                        <div>
                            <h3 class="font-black">Access control</h3>
                            <p class="mt-1 text-sm leading-6 text-white/60">
                                Paid content, proofs, and generated PDFs use
                                private storage collections.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-4">
                <article
                    v-for="(module, moduleIndex) in course.modules"
                    :key="module.id"
                    class="rounded-[1.5rem] border border-white/10 bg-white/[0.05] p-5"
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
                            <p class="mt-2 text-sm leading-6 text-white/60">
                                {{ module.summary }}
                            </p>

                            <div class="mt-4 grid gap-2">
                                <div
                                    v-for="lesson in module.lessons"
                                    :key="lesson.id"
                                    class="flex items-center justify-between gap-4 rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3"
                                >
                                    <div class="flex items-center gap-3">
                                        <component
                                            :is="lessonIcon(lesson.lessonType)"
                                            class="size-5 text-powerx-yellow"
                                        />
                                        <div>
                                            <p class="font-bold">
                                                {{ lesson.title }}
                                            </p>
                                            <p
                                                class="text-xs font-bold tracking-wide text-white/45 uppercase"
                                            >
                                                {{ lesson.lessonType }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span
                                            v-if="lesson.isPreview"
                                            class="rounded-full border border-powerx-success/30 bg-powerx-success/10 px-3 py-1 text-xs font-bold text-powerx-success"
                                        >
                                            Preview
                                        </span>
                                        <span class="text-sm text-white/50">
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
                </article>

                <div
                    v-if="!course.modules.length"
                    class="rounded-[1.5rem] border border-white/10 bg-white/[0.05] p-8 text-center"
                >
                    <CheckCircle2 class="mx-auto size-10 text-powerx-yellow" />
                    <h3 class="mt-4 text-2xl font-black">
                        Curriculum being prepared
                    </h3>
                    <p class="mt-2 text-white/60">
                        Modules will appear here as operations publishes the LMS
                        structure.
                    </p>
                </div>
            </div>
        </section>
    </main>
</template>
