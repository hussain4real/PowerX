<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Award,
    Banknote,
    BriefcaseBusiness,
    Building2,
    CalendarCheck2,
    CheckCircle2,
    ClipboardList,
    Download,
    FileText,
    ReceiptText,
    ShieldCheck,
    UsersRound,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { portal as corporatePortal } from '@/routes/corporate';
import { csv as corporateReportCsv } from '@/routes/corporate/portal/report';
import type { Team } from '@/types';

interface CorporateCompany {
    id: number;
    name: string;
    contactName: string;
    email: string;
    phone: string | null;
    address: string | null;
    industry: string;
    employeeCount: number;
    enrollmentCount: number;
    invoiceCount: number;
}

interface CorporateSummary {
    companyCount: number;
    employeeCount: number;
    enrollmentCount: number;
    activeEnrollments: number;
    completedEnrollments: number;
    pendingEnrollments: number;
    quotationCount: number;
    invoiceCount: number;
    outstandingAmount: number;
    paidAmount: number;
    currency: string;
    attendanceTotal: number;
    attendancePresent: number;
    attendanceRate: number;
    certificateCount: number;
}

interface CorporateInvoice {
    id: number;
    number: string;
    type: string;
    status: string;
    currency: string;
    subtotal: number;
    discountTotal: number;
    taxTotal: number;
    total: number;
    issuedAt: string | null;
    dueAt: string | null;
    paidAt: string | null;
    companyName: string;
    courseTitle: string;
    packageName: string | null;
    seatCount: number | string | null;
}

interface CorporatePayment {
    id: number;
    companyName: string;
    invoiceNumber: string;
    method: string;
    status: string;
    currency: string;
    amount: number;
    paidAt: string | null;
    approvedAt: string | null;
}

interface CorporateEnrollment {
    id: number;
    companyName: string;
    studentName: string;
    profession: string | null;
    documentStatus: string | null;
    courseTitle: string;
    courseCategory: string | null;
    deliveryMode: string | null;
    packageName: string | null;
    packageType: string | null;
    status: string;
    paymentStatus: string;
    accessStartsAt: string | null;
    accessExpiresAt: string | null;
    approvedAt: string | null;
    attendanceSessions: number;
    attendedSessions: number;
    issuedCertificates: number;
}

interface CorporateAttendance {
    id: number;
    companyName: string;
    studentName: string;
    profession: string | null;
    courseTitle: string;
    batchName: string;
    sessionTitle: string;
    sessionType: string | null;
    venue: string | null;
    sessionStatus: string | null;
    startsAt: string | null;
    endsAt: string | null;
    status: string;
    attendedAt: string | null;
    practicalOutcome: string;
    practicalScore: number | null;
    assessedAt: string | null;
}

interface CorporateCertificate {
    id: number;
    companyName: string;
    studentName: string;
    profession: string | null;
    courseTitle: string;
    certificateNumber: string;
    status: string;
    result: string;
    issuedAt: string | null;
    expiresAt: string | null;
    verifyUrl: string;
}

interface CorporateReport {
    available: boolean;
    scopeLabel: string;
    generatedAt: string;
}

interface DataSharingGate {
    status: string;
    releaseLabel: string;
    summary: string;
    scopeRule: string;
}

const props = defineProps<{
    companies: CorporateCompany[];
    summary: CorporateSummary;
    quotations: CorporateInvoice[];
    enrollments: CorporateEnrollment[];
    finance: {
        invoices: CorporateInvoice[];
        payments: CorporatePayment[];
    };
    attendance: CorporateAttendance[];
    certificates: CorporateCertificate[];
    report: CorporateReport;
    dataSharingGate: DataSharingGate;
}>();

const page = usePage();

defineOptions({
    layout: (props: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Corporate portal',
                href: props.currentTeam
                    ? corporatePortal(props.currentTeam.slug).url
                    : '/',
            },
        ],
    }),
});

const reportLinks = computed(() => {
    const currentTeam = page.props.currentTeam;

    if (!currentTeam || !props.report.available) {
        return null;
    }

    return {
        csv: corporateReportCsv(currentTeam.slug).url,
    };
});

const summaryCards = computed(() => [
    {
        label: 'Companies',
        value: props.summary.companyCount.toLocaleString(),
        detail: props.report.scopeLabel || 'No company match yet',
        tone: 'info',
    },
    {
        label: 'Employees',
        value: props.summary.employeeCount.toLocaleString(),
        detail: `${props.summary.enrollmentCount.toLocaleString()} company enrollments`,
        tone: 'neutral',
    },
    {
        label: 'Completions',
        value: props.summary.completedEnrollments.toLocaleString(),
        detail: `${props.summary.certificateCount.toLocaleString()} issued certificates`,
        tone: 'success',
    },
    {
        label: 'Attendance rate',
        value: `${props.summary.attendanceRate}%`,
        detail: `${props.summary.attendancePresent}/${props.summary.attendanceTotal} present or late`,
        tone: 'warning',
    },
    {
        label: 'Quotations',
        value: props.summary.quotationCount.toLocaleString(),
        detail: 'Read-only quotation status',
        tone: 'info',
    },
    {
        label: 'Paid',
        value: money(props.summary.paidAmount, props.summary.currency),
        detail: 'Approved company payments',
        tone: 'success',
    },
    {
        label: 'Outstanding',
        value: money(props.summary.outstandingAmount, props.summary.currency),
        detail: 'Unpaid invoice exposure',
        tone: 'warning',
    },
    {
        label: 'Report',
        value: props.report.available ? 'Available' : 'Locked',
        detail: props.dataSharingGate.releaseLabel,
        tone: props.report.available ? 'success' : 'neutral',
    },
]);

const toneClasses: Record<string, string> = {
    warning: 'border-powerx-yellow/30 bg-powerx-yellow/10 text-powerx-yellow',
    success:
        'border-powerx-success/30 bg-powerx-success/10 text-powerx-success',
    info: 'border-powerx-blue/30 bg-powerx-blue/10 text-powerx-cyan',
    neutral: 'border-sidebar-border bg-card text-foreground',
};

const statusClasses: Record<string, string> = {
    active: 'bg-powerx-success/10 text-powerx-success',
    approved: 'bg-powerx-success/10 text-powerx-success',
    completed: 'bg-powerx-success/10 text-powerx-success',
    issued: 'bg-powerx-blue/10 text-powerx-cyan',
    paid: 'bg-powerx-success/10 text-powerx-success',
    present: 'bg-powerx-success/10 text-powerx-success',
    late: 'bg-powerx-yellow/10 text-powerx-yellow',
    pending: 'bg-powerx-yellow/10 text-powerx-yellow',
    partial: 'bg-powerx-yellow/10 text-powerx-yellow',
    draft: 'bg-muted text-muted-foreground',
    absent: 'bg-red-500/10 text-red-300',
    failed: 'bg-red-500/10 text-red-300',
};

const label = (value: string | number | null | undefined): string =>
    value ? String(value).replaceAll('_', ' ') : 'Not set';

const dateLabel = (value: string | null | undefined): string =>
    value
        ? new Intl.DateTimeFormat('en-QA', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(value))
        : 'Not set';

function money(amount: number | undefined, currency: string): string {
    return new Intl.NumberFormat('en-QA', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(amount ?? 0);
}

const statusClass = (status: string | null | undefined): string =>
    statusClasses[status ?? ''] ?? 'bg-muted text-muted-foreground';
</script>

<template>
    <Head title="Corporate portal" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
        <section
            class="relative isolate overflow-hidden rounded-2xl border border-sidebar-border/70 bg-gradient-to-br from-powerx-ink via-powerx-navy to-powerx-panel p-6 text-white shadow-sm dark:border-sidebar-border"
        >
            <div
                class="absolute inset-0 -z-10 [background-image:linear-gradient(rgba(255,255,255,0.08)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.08)_1px,transparent_1px)] [background-size:64px_64px] opacity-20"
            />
            <div class="flex flex-col justify-between gap-6 xl:flex-row">
                <div>
                    <p
                        class="text-sm font-black tracking-[0.28em] text-powerx-yellow uppercase"
                    >
                        Corporate workspace
                    </p>
                    <h1 class="mt-3 text-3xl font-black md:text-4xl">
                        Read-only company coordination
                    </h1>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-white/65">
                        Review matched company training activity, quotations,
                        enrollments, payments, attendance, completions, and
                        certificate verification links without any write
                        workflows or other-company records.
                    </p>
                </div>
                <div class="flex flex-col items-start gap-3 xl:items-end">
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-powerx-yellow/30 bg-powerx-yellow/10 px-4 py-2 text-sm font-black text-powerx-yellow"
                    >
                        <ShieldCheck class="size-4" />
                        {{ dataSharingGate.releaseLabel }}
                    </div>
                    <a
                        v-if="reportLinks"
                        :href="reportLinks.csv"
                        class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-black tracking-[0.16em] text-white uppercase transition hover:border-powerx-yellow hover:text-powerx-yellow"
                    >
                        <Download class="size-4" />
                        CSV report
                    </a>
                </div>
            </div>
        </section>

        <section
            class="grid gap-4 rounded-2xl border border-powerx-yellow/30 bg-powerx-yellow/10 p-5 text-powerx-yellow shadow-sm lg:grid-cols-[auto_1fr]"
        >
            <AlertTriangle class="size-8" />
            <div>
                <p class="text-sm font-black tracking-[0.22em] uppercase">
                    Sign-off and data-sharing gate
                </p>
                <p class="mt-2 text-sm leading-6">
                    {{ dataSharingGate.summary }}
                </p>
                <p class="mt-2 text-sm leading-6 text-foreground">
                    {{ dataSharingGate.scopeRule }}
                </p>
            </div>
        </section>

        <section
            v-if="companies.length === 0"
            class="rounded-2xl border border-sidebar-border/70 bg-card p-8 text-center shadow-sm dark:border-sidebar-border"
        >
            <BriefcaseBusiness class="mx-auto size-12 text-powerx-yellow" />
            <h2 class="mt-4 text-2xl font-black">
                No company has been matched to this login
            </h2>
            <p
                class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-muted-foreground"
            >
                PowerX must confirm the coordinator-to-company sharing rule
                before company records or downloadable reports are released to
                this account.
            </p>
        </section>

        <template v-else>
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="rounded-2xl border p-5 shadow-sm"
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

            <section class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
                <article
                    class="rounded-2xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p
                                class="text-sm font-black tracking-[0.22em] text-powerx-yellow uppercase"
                            >
                                Company profile
                            </p>
                            <h2 class="mt-2 text-2xl font-black">
                                Matched coordinator scope
                            </h2>
                        </div>
                        <Building2 class="size-8 text-powerx-yellow" />
                    </div>
                    <div class="mt-6 grid gap-4">
                        <div
                            v-for="company in companies"
                            :key="company.id"
                            class="rounded-2xl border border-border p-4"
                        >
                            <h3 class="text-xl font-black">
                                {{ company.name }}
                            </h3>
                            <dl class="mt-4 grid gap-3 text-sm">
                                <div class="flex justify-between gap-4">
                                    <dt class="text-muted-foreground">
                                        Coordinator
                                    </dt>
                                    <dd class="text-right font-bold">
                                        {{ company.contactName }}
                                    </dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-muted-foreground">
                                        Contact email
                                    </dt>
                                    <dd class="text-right font-bold">
                                        {{ company.email }}
                                    </dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-muted-foreground">Phone</dt>
                                    <dd class="text-right font-bold">
                                        {{ label(company.phone) }}
                                    </dd>
                                </div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-muted-foreground">
                                        Industry
                                    </dt>
                                    <dd class="text-right font-bold">
                                        {{ label(company.industry) }}
                                    </dd>
                                </div>
                            </dl>
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
                                Quotation requests
                            </p>
                            <h2 class="mt-2 text-2xl font-black">
                                Company quotation visibility
                            </h2>
                        </div>
                        <FileText class="size-8 text-powerx-yellow" />
                    </div>
                    <div
                        v-if="quotations.length === 0"
                        class="mt-6 rounded-2xl border border-dashed border-border p-6 text-sm text-muted-foreground"
                    >
                        No company quotations are linked yet.
                    </div>
                    <div v-else class="mt-6 grid gap-4">
                        <div
                            v-for="quotation in quotations"
                            :key="quotation.id"
                            class="rounded-2xl border border-border p-4"
                        >
                            <div class="flex flex-wrap items-start gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="font-black">
                                        {{ quotation.number }}
                                    </p>
                                    <p
                                        class="mt-1 text-sm text-muted-foreground"
                                    >
                                        {{ quotation.courseTitle }} ·
                                        {{ label(quotation.packageName) }}
                                    </p>
                                </div>
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-black uppercase"
                                    :class="statusClass(quotation.status)"
                                >
                                    {{ label(quotation.status) }}
                                </span>
                            </div>
                            <div
                                class="mt-4 grid gap-3 text-sm sm:grid-cols-3"
                            >
                                <div>
                                    <p class="text-muted-foreground">Total</p>
                                    <p class="font-black">
                                        {{
                                            money(
                                                quotation.total,
                                                quotation.currency,
                                            )
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground">Issued</p>
                                    <p class="font-black">
                                        {{ dateLabel(quotation.issuedAt) }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground">Seats</p>
                                    <p class="font-black">
                                        {{ label(quotation.seatCount) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <article
                    class="rounded-2xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p
                                class="text-sm font-black tracking-[0.22em] text-powerx-yellow uppercase"
                            >
                                Enrollments
                            </p>
                            <h2 class="mt-2 text-2xl font-black">
                                Employee training records
                            </h2>
                        </div>
                        <UsersRound class="size-8 text-powerx-yellow" />
                    </div>
                    <div class="mt-6 grid gap-4">
                        <div
                            v-for="enrollment in enrollments"
                            :key="enrollment.id"
                            class="rounded-2xl border border-border p-4"
                        >
                            <div class="flex flex-wrap items-start gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="font-black">
                                        {{ enrollment.studentName }}
                                    </p>
                                    <p
                                        class="mt-1 text-sm text-muted-foreground"
                                    >
                                        {{ enrollment.courseTitle }} ·
                                        {{ label(enrollment.packageName) }}
                                    </p>
                                </div>
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-black uppercase"
                                    :class="statusClass(enrollment.status)"
                                >
                                    {{ label(enrollment.status) }}
                                </span>
                            </div>
                            <div
                                class="mt-4 grid gap-3 text-sm sm:grid-cols-3"
                            >
                                <div>
                                    <p class="text-muted-foreground">
                                        Payment
                                    </p>
                                    <p class="font-black">
                                        {{ label(enrollment.paymentStatus) }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground">
                                        Attendance
                                    </p>
                                    <p class="font-black">
                                        {{ enrollment.attendedSessions }}/{{
                                            enrollment.attendanceSessions
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground">
                                        Certificates
                                    </p>
                                    <p class="font-black">
                                        {{ enrollment.issuedCertificates }}
                                    </p>
                                </div>
                            </div>
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
                                Invoices and payments
                            </p>
                            <h2 class="mt-2 text-2xl font-black">
                                Read-only finance summary
                            </h2>
                        </div>
                        <ReceiptText class="size-8 text-powerx-yellow" />
                    </div>
                    <div class="mt-6 grid gap-4">
                        <div
                            v-for="invoice in finance.invoices"
                            :key="invoice.id"
                            class="rounded-2xl border border-border p-4"
                        >
                            <div class="flex flex-wrap items-start gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="font-black">
                                        {{ invoice.number }}
                                    </p>
                                    <p
                                        class="mt-1 text-sm text-muted-foreground"
                                    >
                                        {{ label(invoice.type) }} ·
                                        {{ invoice.courseTitle }}
                                    </p>
                                </div>
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-black uppercase"
                                    :class="statusClass(invoice.status)"
                                >
                                    {{ label(invoice.status) }}
                                </span>
                            </div>
                            <div
                                class="mt-4 grid gap-3 text-sm sm:grid-cols-3"
                            >
                                <div>
                                    <p class="text-muted-foreground">Total</p>
                                    <p class="font-black">
                                        {{
                                            money(invoice.total, invoice.currency)
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground">Due</p>
                                    <p class="font-black">
                                        {{ dateLabel(invoice.dueAt) }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground">Paid</p>
                                    <p class="font-black">
                                        {{ dateLabel(invoice.paidAt) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div
                            v-if="finance.payments.length > 0"
                            class="rounded-2xl border border-border p-4"
                        >
                            <div class="mb-4 flex items-center gap-2">
                                <Banknote class="size-5 text-powerx-success" />
                                <h3 class="font-black">Payments</h3>
                            </div>
                            <div class="grid gap-3">
                                <div
                                    v-for="payment in finance.payments"
                                    :key="payment.id"
                                    class="flex flex-wrap justify-between gap-3 rounded-xl bg-muted/50 p-3 text-sm"
                                >
                                    <div>
                                        <p class="font-bold">
                                            {{ payment.invoiceNumber }}
                                        </p>
                                        <p class="text-muted-foreground">
                                            {{ label(payment.method) }} ·
                                            {{ dateLabel(payment.paidAt) }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-black">
                                            {{
                                                money(
                                                    payment.amount,
                                                    payment.currency,
                                                )
                                            }}
                                        </p>
                                        <p class="text-muted-foreground">
                                            {{ label(payment.status) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <article
                    class="rounded-2xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p
                                class="text-sm font-black tracking-[0.22em] text-powerx-yellow uppercase"
                            >
                                Attendance
                            </p>
                            <h2 class="mt-2 text-2xl font-black">
                                Session and practical outcomes
                            </h2>
                        </div>
                        <CalendarCheck2 class="size-8 text-powerx-yellow" />
                    </div>
                    <div
                        v-if="attendance.length === 0"
                        class="mt-6 rounded-2xl border border-dashed border-border p-6 text-sm text-muted-foreground"
                    >
                        No attendance records are linked yet.
                    </div>
                    <div v-else class="mt-6 grid gap-4">
                        <div
                            v-for="record in attendance"
                            :key="record.id"
                            class="rounded-2xl border border-border p-4"
                        >
                            <div class="flex flex-wrap items-start gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="font-black">
                                        {{ record.studentName }}
                                    </p>
                                    <p
                                        class="mt-1 text-sm text-muted-foreground"
                                    >
                                        {{ record.sessionTitle }} ·
                                        {{ record.courseTitle }}
                                    </p>
                                </div>
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-black uppercase"
                                    :class="statusClass(record.status)"
                                >
                                    {{ label(record.status) }}
                                </span>
                            </div>
                            <div
                                class="mt-4 grid gap-3 text-sm sm:grid-cols-3"
                            >
                                <div>
                                    <p class="text-muted-foreground">Session</p>
                                    <p class="font-black">
                                        {{ dateLabel(record.startsAt) }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground">
                                        Practical
                                    </p>
                                    <p class="font-black">
                                        {{ label(record.practicalOutcome) }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground">Score</p>
                                    <p class="font-black">
                                        {{ label(record.practicalScore) }}
                                    </p>
                                </div>
                            </div>
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
                                Completion and certificates
                            </p>
                            <h2 class="mt-2 text-2xl font-black">
                                PowerX completion records
                            </h2>
                        </div>
                        <Award class="size-8 text-powerx-yellow" />
                    </div>
                    <div
                        v-if="certificates.length === 0"
                        class="mt-6 rounded-2xl border border-dashed border-border p-6 text-sm text-muted-foreground"
                    >
                        No issued completion records are linked yet.
                    </div>
                    <div v-else class="mt-6 grid gap-4">
                        <div
                            v-for="certificate in certificates"
                            :key="certificate.id"
                            class="rounded-2xl border border-border p-4"
                        >
                            <div class="flex flex-wrap items-start gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="font-black">
                                        {{ certificate.certificateNumber }}
                                    </p>
                                    <p
                                        class="mt-1 text-sm text-muted-foreground"
                                    >
                                        {{ certificate.studentName }} ·
                                        {{ certificate.courseTitle }}
                                    </p>
                                </div>
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-black uppercase"
                                    :class="statusClass(certificate.status)"
                                >
                                    {{ label(certificate.status) }}
                                </span>
                            </div>
                            <div
                                class="mt-4 grid gap-3 text-sm sm:grid-cols-3"
                            >
                                <div>
                                    <p class="text-muted-foreground">Result</p>
                                    <p class="font-black">
                                        {{ label(certificate.result) }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground">Issued</p>
                                    <p class="font-black">
                                        {{ dateLabel(certificate.issuedAt) }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground">Expires</p>
                                    <p class="font-black">
                                        {{ dateLabel(certificate.expiresAt) }}
                                    </p>
                                </div>
                            </div>
                            <a
                                :href="certificate.verifyUrl"
                                target="_blank"
                                rel="noreferrer"
                                class="mt-4 inline-flex items-center gap-2 rounded-full border border-border px-4 py-2 text-xs font-black tracking-[0.16em] uppercase transition hover:border-powerx-yellow hover:text-powerx-yellow"
                            >
                                <CheckCircle2 class="size-4" />
                                Verify record
                            </a>
                        </div>
                    </div>
                </article>
            </section>

            <section
                class="grid gap-4 rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm dark:border-sidebar-border md:grid-cols-[auto_1fr]"
            >
                <ClipboardList class="size-8 text-powerx-cyan" />
                <div>
                    <p class="text-sm font-black tracking-[0.22em] uppercase">
                        Read-only release checklist
                    </p>
                    <p class="mt-2 text-sm leading-6 text-muted-foreground">
                        Corporate coordinators can download only the
                        company-scoped report shown here. Admin tools,
                        operations dashboards, report exports, student contact
                        details, internal approval notes, and edit actions stay
                        hidden until PowerX approves broader data-sharing rules.
                    </p>
                </div>
            </section>
        </template>
    </div>
</template>
