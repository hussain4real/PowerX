<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Banknote, BookOpenCheck, ReceiptText } from 'lucide-vue-next';
import { computed } from 'vue';
import { portal as studentPortal } from '@/routes/student';
import { index as studentPaymentsIndex } from '@/routes/student/payments';
import type { StudentPortalProps, Team } from '@/types';

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
                title: 'Payments',
                href: props.currentTeam
                    ? studentPaymentsIndex(props.currentTeam.slug).url
                    : '/',
            },
        ],
    }),
});

const enrollmentsWithFinance = computed(() =>
    props.enrollments.filter(
        (enrollment) =>
            enrollment.accessStatus !== 'open' ||
            enrollment.paymentStatus !== 'paid' ||
            enrollment.finance.invoices.length > 0 ||
            enrollment.finance.payments.length > 0,
    ),
);

const totalInvoices = computed(() =>
    props.enrollments.reduce(
        (total, enrollment) => total + enrollment.finance.invoices.length,
        0,
    ),
);

const totalPayments = computed(() =>
    props.enrollments.reduce(
        (total, enrollment) => total + enrollment.finance.payments.length,
        0,
    ),
);

const label = (value: string | null | undefined): string =>
    value ? value.replaceAll('_', ' ') : 'Not set';

const money = (amount: number | undefined, currency: string): string =>
    new Intl.NumberFormat('en-QA', {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(amount ?? 0);

const dateLabel = (value: string | null | undefined): string =>
    value
        ? new Intl.DateTimeFormat('en-QA', {
              dateStyle: 'medium',
          }).format(new Date(value))
        : 'Not set';
</script>

<template>
    <Head title="Student payments" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
        <section
            class="rounded-2xl border border-sidebar-border/70 bg-card p-6 shadow-sm dark:border-sidebar-border"
        >
            <p
                class="text-sm font-black tracking-[0.22em] text-powerx-yellow uppercase"
            >
                Payments
            </p>
            <div class="mt-3 flex flex-col justify-between gap-4 lg:flex-row">
                <div>
                    <h1 class="text-3xl font-black">
                        {{
                            profile
                                ? `${profile.fullName}'s payment status`
                                : 'Payment status'
                        }}
                    </h1>
                    <p
                        class="mt-2 max-w-3xl text-sm leading-6 text-muted-foreground"
                    >
                        Review pending payments, invoices, and approved
                        transactions by course.
                    </p>
                </div>
                <div class="grid min-w-64 gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl bg-muted/50 p-4">
                        <p
                            class="text-xs font-black tracking-[0.18em] text-muted-foreground uppercase"
                        >
                            Pending
                        </p>
                        <p class="mt-2 text-2xl font-black">
                            {{ summary.pendingPayments }}
                        </p>
                    </div>
                    <div class="rounded-2xl bg-muted/50 p-4">
                        <p
                            class="text-xs font-black tracking-[0.18em] text-muted-foreground uppercase"
                        >
                            Invoices
                        </p>
                        <p class="mt-2 text-2xl font-black">
                            {{ totalInvoices }}
                        </p>
                    </div>
                    <div class="rounded-2xl bg-muted/50 p-4">
                        <p
                            class="text-xs font-black tracking-[0.18em] text-muted-foreground uppercase"
                        >
                            Payments
                        </p>
                        <p class="mt-2 text-2xl font-black">
                            {{ totalPayments }}
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
                Payment details will appear here after admissions links your
                student profile.
            </p>
        </section>

        <section
            v-else-if="enrollmentsWithFinance.length === 0"
            class="rounded-2xl border border-sidebar-border/70 bg-card p-8 text-center shadow-sm dark:border-sidebar-border"
        >
            <Banknote class="mx-auto size-12 text-powerx-yellow" />
            <h2 class="mt-4 text-2xl font-black">No payment activity yet</h2>
            <p
                class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-muted-foreground"
            >
                Invoices and payment transactions will appear here when they are
                attached to your enrollments.
            </p>
        </section>

        <section v-else class="grid gap-4">
            <article
                v-for="enrollment in enrollmentsWithFinance"
                :id="`enrollment-${enrollment.id}-finance`"
                :key="enrollment.id"
                class="scroll-mt-6 rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm dark:border-sidebar-border"
            >
                <div class="flex flex-col justify-between gap-4 lg:flex-row">
                    <div>
                        <p
                            class="text-xs font-black tracking-[0.18em] text-powerx-yellow uppercase"
                        >
                            {{ label(enrollment.paymentStatus) }}
                        </p>
                        <h2 class="mt-2 text-xl font-black">
                            {{ enrollment.course.title }}
                        </h2>
                        <p class="mt-2 text-sm text-muted-foreground">
                            Access status: {{ label(enrollment.accessStatus) }}
                        </p>
                    </div>
                    <span
                        class="inline-flex h-fit rounded-full border px-3 py-1 text-xs font-black tracking-[0.18em] uppercase"
                        :class="
                            enrollment.paymentStatus === 'paid'
                                ? 'border-powerx-success/30 bg-powerx-success/10 text-powerx-success'
                                : 'border-powerx-yellow/30 bg-powerx-yellow/10 text-powerx-yellow'
                        "
                    >
                        {{ label(enrollment.paymentStatus) }}
                    </span>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <div class="rounded-2xl border border-border p-4">
                        <div class="flex items-center gap-2">
                            <ReceiptText class="size-4 text-powerx-yellow" />
                            <h3 class="font-black">Invoices</h3>
                        </div>
                        <div
                            v-if="enrollment.finance.invoices.length > 0"
                            class="mt-4 grid gap-3"
                        >
                            <div
                                v-for="invoice in enrollment.finance.invoices"
                                :key="invoice.id"
                                class="rounded-xl bg-muted/50 p-3 text-sm"
                            >
                                <div class="flex justify-between gap-3">
                                    <p class="font-black">
                                        {{
                                            invoice.number ??
                                            `Invoice #${invoice.id}`
                                        }}
                                    </p>
                                    <p class="font-black">
                                        {{
                                            money(
                                                invoice.total,
                                                invoice.currency,
                                            )
                                        }}
                                    </p>
                                </div>
                                <p class="mt-1 text-muted-foreground">
                                    {{ label(invoice.status) }} · Due
                                    {{ dateLabel(invoice.dueAt) }}
                                </p>
                            </div>
                        </div>
                        <p v-else class="mt-4 text-sm text-muted-foreground">
                            No invoices are attached to this enrollment yet.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-border p-4">
                        <div class="flex items-center gap-2">
                            <Banknote class="size-4 text-powerx-yellow" />
                            <h3 class="font-black">Payments</h3>
                        </div>
                        <div
                            v-if="enrollment.finance.payments.length > 0"
                            class="mt-4 grid gap-3"
                        >
                            <div
                                v-for="payment in enrollment.finance.payments"
                                :key="payment.id"
                                class="rounded-xl bg-muted/50 p-3 text-sm"
                            >
                                <div class="flex justify-between gap-3">
                                    <p class="font-black">
                                        {{ label(payment.method) }}
                                    </p>
                                    <p class="font-black">
                                        {{
                                            money(
                                                payment.amount,
                                                payment.currency,
                                            )
                                        }}
                                    </p>
                                </div>
                                <p class="mt-1 text-muted-foreground">
                                    {{ label(payment.status) }} · Paid
                                    {{ dateLabel(payment.paidAt) }}
                                </p>
                                <p
                                    v-if="payment.reference"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    Reference: {{ payment.reference }}
                                </p>
                            </div>
                        </div>
                        <p v-else class="mt-4 text-sm text-muted-foreground">
                            No payments have been recorded for this enrollment
                            yet.
                        </p>
                    </div>
                </div>
            </article>
        </section>
    </div>
</template>
