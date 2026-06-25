<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import {
    Banknote,
    BookOpenCheck,
    Download,
    FileText,
    ReceiptText,
    Upload,
} from 'lucide-vue-next';
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
                                <div
                                    class="flex flex-wrap items-start justify-between gap-3"
                                >
                                    <div>
                                        <p class="font-black">
                                            {{
                                                invoice.number ??
                                                `Invoice #${invoice.id}`
                                            }}
                                        </p>
                                        <p class="mt-1 text-muted-foreground">
                                            {{ label(invoice.status) }} · Due
                                            {{ dateLabel(invoice.dueAt) }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-black">
                                            {{
                                                money(
                                                    invoice.total,
                                                    invoice.currency,
                                                )
                                            }}
                                        </p>
                                        <p
                                            class="mt-1 text-xs text-muted-foreground"
                                        >
                                            Outstanding
                                            {{
                                                money(
                                                    invoice.outstandingAmount,
                                                    invoice.currency,
                                                )
                                            }}
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <a
                                        v-if="invoice.invoicePdfUrl"
                                        :href="invoice.invoicePdfUrl"
                                        class="inline-flex items-center gap-2 rounded-full border border-border bg-background px-3 py-2 text-xs font-black uppercase transition hover:border-powerx-yellow hover:text-powerx-yellow"
                                    >
                                        <FileText class="size-4" />
                                        Invoice PDF
                                    </a>
                                </div>
                                <p
                                    v-if="invoice.offlineInstructions"
                                    class="mt-3 rounded-lg border border-powerx-yellow/30 bg-powerx-yellow/10 p-3 text-xs leading-5 text-powerx-yellow"
                                >
                                    {{ invoice.offlineInstructions }}
                                </p>
                                <Form
                                    v-if="invoice.offlinePaymentProofUrl"
                                    :action="invoice.offlinePaymentProofUrl"
                                    method="post"
                                    reset-on-success
                                    class="mt-3 grid gap-3 rounded-lg border border-border bg-background p-3"
                                    #default="{
                                        errors,
                                        processing,
                                        progress,
                                        wasSuccessful,
                                    }"
                                >
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <label class="grid gap-1">
                                            <span
                                                class="text-xs font-black text-muted-foreground uppercase"
                                            >
                                                Method
                                            </span>
                                            <select
                                                name="method"
                                                class="rounded-md border border-border bg-background px-3 py-2"
                                            >
                                                <option value="bank_transfer">
                                                    Bank transfer
                                                </option>
                                                <option value="cash">
                                                    Cash
                                                </option>
                                                <option value="cheque">
                                                    Cheque
                                                </option>
                                            </select>
                                            <span
                                                v-if="errors.method"
                                                class="text-xs text-red-400"
                                            >
                                                {{ errors.method }}
                                            </span>
                                        </label>
                                        <label class="grid gap-1">
                                            <span
                                                class="text-xs font-black text-muted-foreground uppercase"
                                            >
                                                Amount
                                            </span>
                                            <input
                                                name="amount"
                                                type="number"
                                                min="1"
                                                step="0.01"
                                                :value="
                                                    invoice.outstandingAmount ??
                                                    invoice.total ??
                                                    0
                                                "
                                                class="rounded-md border border-border bg-background px-3 py-2"
                                            />
                                            <span
                                                v-if="errors.amount"
                                                class="text-xs text-red-400"
                                            >
                                                {{ errors.amount }}
                                            </span>
                                        </label>
                                        <label class="grid gap-1">
                                            <span
                                                class="text-xs font-black text-muted-foreground uppercase"
                                            >
                                                Reference
                                            </span>
                                            <input
                                                name="reference"
                                                type="text"
                                                class="rounded-md border border-border bg-background px-3 py-2"
                                            />
                                        </label>
                                        <label class="grid gap-1">
                                            <span
                                                class="text-xs font-black text-muted-foreground uppercase"
                                            >
                                                Paid date
                                            </span>
                                            <input
                                                name="paid_at"
                                                type="date"
                                                class="rounded-md border border-border bg-background px-3 py-2"
                                            />
                                        </label>
                                        <label class="grid gap-1">
                                            <span
                                                class="text-xs font-black text-muted-foreground uppercase"
                                            >
                                                Payer
                                            </span>
                                            <input
                                                name="payer_name"
                                                type="text"
                                                :value="profile?.fullName"
                                                class="rounded-md border border-border bg-background px-3 py-2"
                                            />
                                        </label>
                                        <label class="grid gap-1">
                                            <span
                                                class="text-xs font-black text-muted-foreground uppercase"
                                            >
                                                Bank/deposit
                                            </span>
                                            <input
                                                name="bank_name"
                                                type="text"
                                                class="rounded-md border border-border bg-background px-3 py-2"
                                            />
                                        </label>
                                    </div>
                                    <label class="grid gap-1">
                                        <span
                                            class="text-xs font-black text-muted-foreground uppercase"
                                        >
                                            Proof
                                        </span>
                                        <input
                                            name="proof"
                                            type="file"
                                            accept=".pdf,.jpg,.jpeg,.png,.webp"
                                            class="rounded-md border border-border bg-background px-3 py-2"
                                        />
                                        <span
                                            v-if="errors.proof"
                                            class="text-xs text-red-400"
                                        >
                                            {{ errors.proof }}
                                        </span>
                                    </label>
                                    <label class="grid gap-1">
                                        <span
                                            class="text-xs font-black text-muted-foreground uppercase"
                                        >
                                            Notes
                                        </span>
                                        <textarea
                                            name="notes"
                                            rows="2"
                                            class="rounded-md border border-border bg-background px-3 py-2"
                                        />
                                    </label>
                                    <progress
                                        v-if="progress"
                                        :value="progress.percentage"
                                        max="100"
                                        class="h-2 w-full"
                                    >
                                        {{ progress.percentage }}%
                                    </progress>
                                    <div
                                        class="flex flex-wrap items-center justify-between gap-3"
                                    >
                                        <p
                                            v-if="wasSuccessful"
                                            class="text-xs font-bold text-powerx-success"
                                        >
                                            Submitted for finance review.
                                        </p>
                                        <button
                                            type="submit"
                                            :disabled="processing"
                                            class="inline-flex items-center gap-2 rounded-full bg-powerx-yellow px-4 py-2 text-xs font-black text-powerx-ink uppercase transition hover:bg-powerx-yellow/90 disabled:cursor-not-allowed disabled:opacity-60"
                                        >
                                            <Upload class="size-4" />
                                            {{
                                                processing
                                                    ? 'Submitting'
                                                    : 'Submit proof'
                                            }}
                                        </button>
                                    </div>
                                </Form>
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
                                    v-if="payment.proofStatus"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    Proof: {{ label(payment.proofStatus) }}
                                </p>
                                <p
                                    v-if="payment.reference"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    Reference: {{ payment.reference }}
                                </p>
                                <p
                                    v-if="payment.reviewNotes"
                                    class="mt-2 rounded-lg border border-powerx-yellow/30 bg-powerx-yellow/10 p-2 text-xs leading-5 text-powerx-yellow"
                                >
                                    {{ payment.reviewNotes }}
                                </p>
                                <a
                                    v-if="payment.receiptUrl"
                                    :href="payment.receiptUrl"
                                    class="mt-3 inline-flex items-center gap-2 rounded-full border border-border bg-background px-3 py-2 text-xs font-black uppercase transition hover:border-powerx-yellow hover:text-powerx-yellow"
                                >
                                    <Download class="size-4" />
                                    Receipt
                                </a>
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
