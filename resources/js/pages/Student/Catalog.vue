<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, BookOpen, BookOpenCheck, Search } from 'lucide-vue-next';
import { computed } from 'vue';
import { portal as studentPortal } from '@/routes/student';
import { index as studentCatalogIndex } from '@/routes/student/catalog';
import { index as studentCoursesIndex } from '@/routes/student/courses';
import type {
    CourseCatalogItem,
    StudentEnrollment,
    StudentPortalProps,
    Team,
} from '@/types';

type CourseCatalogCard = CourseCatalogItem & {
    openEnrollment: StudentEnrollment | null;
};

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
            {
                title: 'Browse courses',
                href: props.currentTeam
                    ? studentCatalogIndex(props.currentTeam.slug).url
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

const openEnrollmentByCourseId = computed(
    () =>
        new Map<number, StudentEnrollment>(
            props.enrollments
                .filter((enrollment) => enrollment.hasPaidAccess)
                .map((enrollment) => [enrollment.course.id, enrollment]),
        ),
);

const courseCatalogCards = computed<CourseCatalogCard[]>(() =>
    props.courseCatalog.map((course) => ({
        ...course,
        openEnrollment: openEnrollmentByCourseId.value.get(course.id) ?? null,
    })),
);

const label = (value: string | null | undefined): string =>
    value ? value.replaceAll('_', ' ') : 'Not set';

const money = (amount: number | undefined, currency: string): string =>
    new Intl.NumberFormat('en-QA', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(amount ?? 0);

const learningActionLabel = (enrollment: StudentEnrollment): string =>
    enrollment.progress.percentage > 0 ? 'Continue learning' : 'Start learning';

const studentCourseLearningUrl = (enrollment: StudentEnrollment): string =>
    `${studentCoursesUrl.value}#course-${enrollment.id}-learning`;
</script>

<template>
    <Head title="Browse courses" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
        <section
            class="rounded-2xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border"
        >
            <p
                class="text-sm font-black tracking-[0.22em] text-powerx-yellow uppercase"
            >
                Browse courses
            </p>
            <div class="mt-3 flex flex-col justify-between gap-4 lg:flex-row">
                <div>
                    <h1 class="text-3xl font-black">PowerX course catalog</h1>
                    <p
                        class="mt-2 max-w-3xl text-sm leading-6 text-muted-foreground"
                    >
                        Compare available published courses, package pricing,
                        delivery modes, and LMS depth from a dedicated student
                        catalog page.
                    </p>
                </div>
                <div class="grid min-w-64 gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl bg-muted/50 p-4">
                        <p
                            class="text-xs font-black tracking-[0.18em] text-muted-foreground uppercase"
                        >
                            Available
                        </p>
                        <p class="mt-2 text-2xl font-black">
                            {{ courseCatalogCards.length }}
                        </p>
                    </div>
                    <div class="rounded-2xl bg-muted/50 p-4">
                        <p
                            class="text-xs font-black tracking-[0.18em] text-muted-foreground uppercase"
                        >
                            Enrolled
                        </p>
                        <p class="mt-2 text-2xl font-black">
                            {{ summary.enrolledCourses }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section
            v-if="courseCatalogCards.length === 0"
            class="rounded-2xl border border-sidebar-border/70 bg-card p-8 text-center shadow-sm dark:border-sidebar-border"
        >
            <Search class="mx-auto size-12 text-powerx-yellow" />
            <h2 class="mt-4 text-2xl font-black">
                No published courses are available yet
            </h2>
            <p
                class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-muted-foreground"
            >
                Published courses will appear here after admissions enables the
                catalog for this team.
            </p>
        </section>

        <section v-else class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="course in courseCatalogCards"
                :key="course.id"
                class="flex flex-col rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm dark:border-sidebar-border"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p
                            class="text-xs font-black tracking-[0.18em] text-powerx-yellow uppercase"
                        >
                            {{ label(course.category) }}
                        </p>
                        <h2 class="mt-2 text-xl font-black">
                            {{ course.title }}
                        </h2>
                    </div>
                    <span
                        v-if="course.isFeatured"
                        class="rounded-full bg-powerx-yellow/10 px-2 py-1 text-xs font-black text-powerx-yellow"
                    >
                        Featured
                    </span>
                </div>

                <p
                    class="mt-3 line-clamp-3 text-sm leading-6 text-muted-foreground"
                >
                    {{
                        course.summary ??
                        'Admissions can share the full syllabus and package details for this course.'
                    }}
                </p>

                <div class="mt-4 grid gap-2 text-sm text-muted-foreground">
                    <p class="flex items-center gap-2">
                        <BookOpen class="size-4 text-powerx-yellow" />
                        {{ label(course.deliveryMode) }} delivery ·
                        {{ course.modulesCount }} LMS modules
                    </p>
                    <p>
                        From
                        <span class="font-black text-foreground">
                            {{
                                money(
                                    course.lowestPackagePrice,
                                    course.currency,
                                )
                            }}
                        </span>
                        <span v-if="course.validityDays">
                            · {{ course.validityDays }} days access
                        </span>
                    </p>
                    <p>{{ course.enrollmentsCount }} enrollments recorded</p>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <span
                        v-for="coursePackage in course.packages.slice(0, 3)"
                        :key="coursePackage.id"
                        class="rounded-full border border-border px-3 py-1 text-xs font-bold text-muted-foreground"
                    >
                        {{ coursePackage.name }} ·
                        {{
                            money(
                                coursePackage.discountPrice ??
                                    coursePackage.price,
                                coursePackage.currency,
                            )
                        }}
                    </span>
                </div>

                <Link
                    v-if="course.openEnrollment"
                    :href="studentCourseLearningUrl(course.openEnrollment)"
                    class="mt-auto inline-flex items-center gap-2 pt-5 text-sm font-black text-powerx-yellow hover:underline"
                >
                    <BookOpenCheck class="size-4" />
                    {{ learningActionLabel(course.openEnrollment) }}
                </Link>
                <Link
                    v-else
                    :href="course.url"
                    class="mt-auto inline-flex items-center gap-2 pt-5 text-sm font-black text-powerx-yellow hover:underline"
                >
                    View course details
                    <ArrowRight class="size-4" />
                </Link>
            </article>
        </section>
    </div>
</template>
