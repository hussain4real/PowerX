<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Award, BookOpenCheck, ExternalLink } from 'lucide-vue-next';
import { computed } from 'vue';
import { portal as studentPortal } from '@/routes/student';
import { index as studentCertificatesIndex } from '@/routes/student/certificates';
import type {
    StudentCertificate,
    StudentEnrollment,
    StudentPortalProps,
    Team,
} from '@/types';

type CertificateRow = StudentCertificate & {
    enrollment: StudentEnrollment;
};

const props = defineProps<StudentPortalProps>();

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
                title: 'Certificates',
                href: props.currentTeam
                    ? studentCertificatesIndex(props.currentTeam.slug).url
                    : '/',
            },
        ],
    }),
});

const certificateRows = computed<CertificateRow[]>(() =>
    props.enrollments.flatMap((enrollment) =>
        enrollment.certificates.map((certificate) => ({
            ...certificate,
            enrollment,
        })),
    ),
);

const issuedCertificates = computed(
    () =>
        certificateRows.value.filter(
            (certificate) => certificate.status === 'issued',
        ).length,
);

const label = (value: string | null | undefined): string =>
    value ? value.replaceAll('_', ' ') : 'Not set';

const dateLabel = (value: string | null): string =>
    value
        ? new Intl.DateTimeFormat('en-QA', {
              dateStyle: 'medium',
          }).format(new Date(value))
        : 'Not set';
</script>

<template>
    <Head title="Student certificates" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
        <section
            class="rounded-2xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border"
        >
            <p
                class="text-sm font-black tracking-[0.22em] text-powerx-yellow uppercase"
            >
                Certificates
            </p>
            <div class="mt-3 flex flex-col justify-between gap-4 lg:flex-row">
                <div>
                    <h1 class="text-3xl font-black">
                        {{
                            profile
                                ? `${profile.fullName}'s certificates`
                                : 'Certificates'
                        }}
                    </h1>
                    <p
                        class="mt-2 max-w-3xl text-sm leading-6 text-muted-foreground"
                    >
                        View issued completion records and open verification
                        links when certificates are available.
                    </p>
                </div>
                <div class="grid min-w-64 gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl bg-muted/50 p-4">
                        <p
                            class="text-xs font-black tracking-[0.18em] text-muted-foreground uppercase"
                        >
                            Issued
                        </p>
                        <p class="mt-2 text-2xl font-black">
                            {{ issuedCertificates }}
                        </p>
                    </div>
                    <div class="rounded-2xl bg-muted/50 p-4">
                        <p
                            class="text-xs font-black tracking-[0.18em] text-muted-foreground uppercase"
                        >
                            Total
                        </p>
                        <p class="mt-2 text-2xl font-black">
                            {{ certificateRows.length }}
                        </p>
                    </div>
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
                Certificate records will appear here after admissions links your
                student profile.
            </p>
        </section>

        <section
            v-else-if="certificateRows.length === 0"
            class="rounded-2xl border border-sidebar-border/70 bg-card p-8 text-center shadow-sm dark:border-sidebar-border"
        >
            <Award class="mx-auto size-12 text-powerx-yellow" />
            <h2 class="mt-4 text-2xl font-black">No certificates issued yet</h2>
            <p
                class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-muted-foreground"
            >
                Certificates will appear here after you meet the course
                completion and assessment requirements.
            </p>
        </section>

        <section v-else class="grid gap-4 lg:grid-cols-2">
            <article
                v-for="certificate in certificateRows"
                :key="`${certificate.enrollment.id}-${certificate.id}`"
                class="rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm dark:border-sidebar-border"
            >
                <div class="flex flex-col justify-between gap-4 sm:flex-row">
                    <div>
                        <p
                            class="text-xs font-black tracking-[0.18em] text-powerx-yellow uppercase"
                        >
                            {{ label(certificate.status) }}
                        </p>
                        <h2 class="mt-2 text-xl font-black">
                            {{ certificate.certificateNumber }}
                        </h2>
                        <p class="mt-2 text-sm text-muted-foreground">
                            {{ certificate.enrollment.course.title }}
                        </p>
                    </div>
                    <a
                        :href="certificate.verifyUrl"
                        class="inline-flex h-fit items-center justify-center gap-2 rounded-full border border-border px-4 py-2 text-sm font-black transition hover:border-powerx-yellow hover:text-powerx-yellow"
                    >
                        Verify
                        <ExternalLink class="size-4" />
                    </a>
                </div>

                <div
                    class="mt-5 grid gap-3 text-sm text-muted-foreground sm:grid-cols-3"
                >
                    <p>Result: {{ label(certificate.result) }}</p>
                    <p>Issued: {{ dateLabel(certificate.issuedAt) }}</p>
                    <p>Expires: {{ dateLabel(certificate.expiresAt) }}</p>
                </div>
            </article>
        </section>
    </div>
</template>
