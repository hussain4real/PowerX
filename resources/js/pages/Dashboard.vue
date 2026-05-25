<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import {
    BadgeCheck,
    Banknote,
    BookOpenCheck,
    CheckCircle2,
    Download,
    FileText,
    GraduationCap,
    TrendingUp,
    UsersRound,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { dashboard } from '@/routes';
import {
    csv as operationalCsv,
    pdf as operationalPdf,
} from '@/routes/reports/operational';
import type { Team } from '@/types';

interface SummaryCard {
    label: string;
    value: string;
    detail: string;
    tone: 'warning' | 'success' | 'info' | 'neutral';
}

defineProps<{
    metrics: {
        summaryCards: SummaryCard[];
        leads: {
            total: number;
            new: number;
            qualified: number;
            converted: number;
        };
        enrollments: {
            total: number;
            pending: number;
            active: number;
            completed: number;
            paid: number;
        };
        finance: {
            pending_payments: number;
            approved_revenue_label: string;
        };
        learning: {
            attendance_total: number;
            attendance_present: number;
            attendance_rate: number;
            exam_attempts: number;
            passed_attempts: number;
            exam_pass_rate: number;
            certificates_issued: number;
        };
        growth: {
            renewal_opportunities: number;
            overdue_renewals: number;
            campaigns_tracked: number;
        };
    };
}>();

const page = usePage();

const reportLinks = computed(() => {
    const currentTeam = page.props.currentTeam;

    if (!currentTeam) {
        return null;
    }

    return {
        csv: operationalCsv(currentTeam.slug).url,
        pdf: operationalPdf(currentTeam.slug).url,
    };
});

defineOptions({
    layout: (props: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: props.currentTeam
                    ? dashboard(props.currentTeam.slug)
                    : '/',
            },
        ],
    }),
});

const toneClasses: Record<SummaryCard['tone'], string> = {
    warning: 'border-powerx-yellow/30 bg-powerx-yellow/10 text-powerx-yellow',
    success:
        'border-powerx-success/30 bg-powerx-success/10 text-powerx-success',
    info: 'border-powerx-blue/30 bg-powerx-blue/10 text-powerx-cyan',
    neutral: 'border-sidebar-border bg-card text-foreground',
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
        <section
            class="rounded-2xl border border-sidebar-border/70 bg-gradient-to-br from-powerx-ink via-powerx-navy to-powerx-panel p-6 text-white shadow-sm dark:border-sidebar-border"
        >
            <div
                class="flex flex-col justify-between gap-5 lg:flex-row lg:items-end"
            >
                <div>
                    <p
                        class="text-sm font-black tracking-[0.28em] text-powerx-yellow uppercase"
                    >
                        PowerX operations
                    </p>
                    <h1 class="mt-3 text-3xl font-black md:text-4xl">
                        Training center dashboard
                    </h1>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-white/65">
                        Live MVP metrics for leads, admissions, manual finance,
                        attendance, exams, and certificates scoped to the
                        current team.
                    </p>
                </div>
                <div class="flex flex-col items-start gap-3 lg:items-end">
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-powerx-yellow/30 bg-powerx-yellow/10 px-4 py-2 text-sm font-black text-powerx-yellow"
                    >
                        <TrendingUp class="size-4" />
                        MVP reporting
                    </div>
                    <div v-if="reportLinks" class="flex flex-wrap gap-2">
                        <a
                            :href="reportLinks.csv"
                            class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-black tracking-[0.16em] text-white uppercase transition hover:border-powerx-yellow hover:text-powerx-yellow"
                        >
                            <Download class="size-4" />
                            CSV export
                        </a>
                        <a
                            :href="reportLinks.pdf"
                            target="_blank"
                            rel="noreferrer"
                            class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-black tracking-[0.16em] text-white uppercase transition hover:border-powerx-yellow hover:text-powerx-yellow"
                        >
                            <FileText class="size-4" />
                            PDF report
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article
                v-for="card in metrics.summaryCards"
                :key="card.label"
                class="rounded-2xl border bg-card p-5 shadow-sm"
                :class="toneClasses[card.tone]"
            >
                <p class="text-xs font-black tracking-[0.22em] uppercase">
                    {{ card.label }}
                </p>
                <p class="mt-3 text-3xl font-black tracking-tight">
                    {{ card.value }}
                </p>
                <p class="mt-3 text-sm leading-6 opacity-75">
                    {{ card.detail }}
                </p>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1fr_1fr]">
            <article
                class="rounded-2xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border"
            >
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p
                            class="text-sm font-black tracking-[0.22em] text-powerx-yellow uppercase"
                        >
                            Lead pipeline
                        </p>
                        <h2 class="mt-2 text-2xl font-black">
                            CRM conversion snapshot
                        </h2>
                    </div>
                    <UsersRound class="size-8 text-powerx-yellow" />
                </div>
                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl border border-border p-4">
                        <p class="text-sm text-muted-foreground">Total leads</p>
                        <p class="mt-2 text-2xl font-black">
                            {{ metrics.leads.total }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-border p-4">
                        <p class="text-sm text-muted-foreground">Qualified</p>
                        <p class="mt-2 text-2xl font-black">
                            {{ metrics.leads.qualified }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-border p-4">
                        <p class="text-sm text-muted-foreground">Converted</p>
                        <p class="mt-2 text-2xl font-black">
                            {{ metrics.leads.converted }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-border p-4">
                        <p class="text-sm text-muted-foreground">New</p>
                        <p class="mt-2 text-2xl font-black">
                            {{ metrics.leads.new }}
                        </p>
                    </div>
                </div>
            </article>

            <article
                class="rounded-2xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border"
            >
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p
                            class="text-sm font-black tracking-[0.22em] text-powerx-yellow uppercase"
                        >
                            Finance
                        </p>
                        <h2 class="mt-2 text-2xl font-black">
                            Manual payment review
                        </h2>
                    </div>
                    <Banknote class="size-8 text-powerx-success" />
                </div>
                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl border border-border p-4">
                        <p class="text-sm text-muted-foreground">
                            Approved revenue
                        </p>
                        <p class="mt-2 text-2xl font-black">
                            {{ metrics.finance.approved_revenue_label }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-border p-4">
                        <p class="text-sm text-muted-foreground">
                            Pending payments
                        </p>
                        <p class="mt-2 text-2xl font-black">
                            {{ metrics.finance.pending_payments }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-border p-4">
                        <p class="text-sm text-muted-foreground">
                            Paid enrollments
                        </p>
                        <p class="mt-2 text-2xl font-black">
                            {{ metrics.enrollments.paid }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-border p-4">
                        <p class="text-sm text-muted-foreground">
                            Pending admissions
                        </p>
                        <p class="mt-2 text-2xl font-black">
                            {{ metrics.enrollments.pending }}
                        </p>
                    </div>
                </div>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-3">
            <article
                class="rounded-2xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border"
            >
                <BookOpenCheck class="size-8 text-powerx-cyan" />
                <h2 class="mt-4 text-2xl font-black">Attendance</h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ metrics.learning.attendance_present }} present or late
                    from {{ metrics.learning.attendance_total }} marked records.
                </p>
                <p class="mt-5 text-4xl font-black">
                    {{ metrics.learning.attendance_rate }}%
                </p>
            </article>
            <article
                class="rounded-2xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border"
            >
                <GraduationCap class="size-8 text-powerx-yellow" />
                <h2 class="mt-4 text-2xl font-black">Exam pass rate</h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ metrics.learning.passed_attempts }} passed from
                    {{ metrics.learning.exam_attempts }} submitted attempts.
                </p>
                <p class="mt-5 text-4xl font-black">
                    {{ metrics.learning.exam_pass_rate }}%
                </p>
            </article>
            <article
                class="rounded-2xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border"
            >
                <BadgeCheck class="size-8 text-powerx-success" />
                <h2 class="mt-4 text-2xl font-black">Certificates</h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    Public verification records issued by PowerX.
                </p>
                <p class="mt-5 text-4xl font-black">
                    {{ metrics.learning.certificates_issued }}
                </p>
            </article>
        </section>

        <section
            class="rounded-2xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border"
        >
            <div class="flex flex-col justify-between gap-6 lg:flex-row">
                <div class="flex items-start gap-4">
                    <CheckCircle2 class="mt-1 size-6 text-powerx-success" />
                    <div>
                        <h2 class="text-xl font-black">
                            Hardened reporting boundary
                        </h2>
                        <p
                            class="mt-2 max-w-3xl text-sm leading-6 text-muted-foreground"
                        >
                            Metrics are aggregated server-side, filtered by the
                            current team, exportable as CSV, printable as PDF,
                            and audited when staff generate report files.
                        </p>
                    </div>
                </div>
                <div>
                    <p
                        class="text-xs font-black tracking-[0.22em] text-powerx-yellow uppercase"
                    >
                        Renewal growth
                    </p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-xl border border-border p-4">
                            <p class="text-sm text-muted-foreground">
                                Renewal opportunities
                            </p>
                            <p class="mt-2 text-2xl font-black">
                                {{ metrics.growth.renewal_opportunities }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-border p-4">
                            <p class="text-sm text-muted-foreground">
                                Overdue renewals
                            </p>
                            <p class="mt-2 text-2xl font-black">
                                {{ metrics.growth.overdue_renewals }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-border p-4">
                            <p class="text-sm text-muted-foreground">
                                Campaigns tracked
                            </p>
                            <p class="mt-2 text-2xl font-black">
                                {{ metrics.growth.campaigns_tracked }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
