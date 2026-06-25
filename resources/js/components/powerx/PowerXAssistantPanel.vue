<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Bot, CheckCircle2, Send, ShieldCheck } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';

interface PowerXAssistantConfig {
    enabled: boolean;
    endpoint: string | null;
    source: string;
    courseId?: number | null;
    courseTitle?: string | null;
}

interface AssistantResponse {
    answer: string;
    disclaimer: string;
    guardrailTriggered: boolean;
    handoffCreated: boolean;
    leadId: number | null;
    suggestedCourse: {
        id: number;
        title: string;
        category: string | null;
        deliveryMode: string | null;
    } | null;
}

const props = defineProps<{
    assistant: PowerXAssistantConfig;
    tone?: 'dark' | 'light';
}>();

const page = usePage();
const message = ref('');
const name = ref('');
const email = ref('');
const phone = ref('');
const companyName = ref('');
const response = ref<AssistantResponse | null>(null);
const errors = ref<Record<string, string[]>>({});
const isSubmitting = ref(false);
const hasSubmitted = ref(false);

const isDark = computed(() => props.tone === 'dark');

const trackingFields = computed(() => {
    const params = new URLSearchParams(page.url.split('?')[1] ?? '');

    return {
        source: props.assistant.source,
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

const panelClass = computed(() =>
    isDark.value
        ? 'border-white/10 bg-white/[0.05] text-white shadow-[0_24px_70px_rgba(0,0,0,0.28)]'
        : 'border-border bg-card text-card-foreground shadow-xl',
);

const mutedClass = computed(() =>
    isDark.value ? 'text-white/60' : 'text-muted-foreground',
);

const fieldClass = computed(() =>
    isDark.value
        ? 'border-white/10 bg-white/[0.07] text-white placeholder:text-white/35 focus:border-powerx-yellow'
        : 'border-input bg-background text-foreground placeholder:text-muted-foreground focus:border-powerx-yellow',
);

const csrfToken = (): string =>
    document
        .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
        ?.getAttribute('content') ?? '';

const firstError = (field: string): string | null =>
    errors.value[field]?.[0] ?? null;

const submit = async (): Promise<void> => {
    if (!props.assistant.endpoint) {
        return;
    }

    isSubmitting.value = true;
    hasSubmitted.value = true;
    errors.value = {};
    response.value = null;

    const payload = {
        ...trackingFields.value,
        course_id: props.assistant.courseId,
        course_interest: props.assistant.courseTitle,
        message: message.value,
        name: name.value,
        email: email.value,
        phone: phone.value,
        company_name: companyName.value,
    };

    const assistantResponse = await fetch(props.assistant.endpoint, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify(payload),
    });

    const body = await assistantResponse.json();

    if (!assistantResponse.ok) {
        errors.value = body.errors ?? {
            message: ['PowerX could not process the assistant request.'],
        };
        isSubmitting.value = false;

        return;
    }

    response.value = body;
    isSubmitting.value = false;
};
</script>

<template>
    <section
        v-if="assistant.enabled"
        class="mx-auto max-w-7xl px-6 py-12 lg:px-8"
    >
        <div :class="['rounded-2xl border p-5', panelClass]">
            <div class="grid gap-6 lg:grid-cols-[0.88fr_1.12fr] lg:items-start">
                <div>
                    <div class="flex items-center gap-3">
                        <span
                            class="flex size-11 items-center justify-center rounded-xl bg-powerx-yellow text-powerx-navy"
                        >
                            <Bot class="size-5" />
                        </span>
                        <div>
                            <p
                                class="text-xs font-black tracking-[0.22em] text-powerx-yellow uppercase"
                            >
                                PowerX assistant
                            </p>
                            <h2 class="text-2xl font-black">
                                Course guidance and handoff
                            </h2>
                        </div>
                    </div>

                    <p :class="['mt-4 text-sm leading-7', mutedClass]">
                        Ask about courses, schedules, registration, or company
                        training. Staff handoff starts when contact details are
                        included.
                    </p>

                    <div class="mt-5 grid gap-3 text-sm">
                        <div class="flex items-start gap-3">
                            <ShieldCheck
                                class="mt-0.5 size-5 text-powerx-yellow"
                            />
                            <span :class="mutedClass">
                                Answers use approved course and FAQ content.
                            </span>
                        </div>
                        <div class="flex items-start gap-3">
                            <CheckCircle2
                                class="mt-0.5 size-5 text-powerx-success"
                            />
                            <span :class="mutedClass">
                                Registration intent creates a CRM handoff.
                            </span>
                        </div>
                    </div>
                </div>

                <form class="grid gap-4" @submit.prevent="submit">
                    <label class="grid gap-2">
                        <span class="text-sm font-bold">Question</span>
                        <textarea
                            v-model="message"
                            rows="4"
                            :class="[
                                'rounded-xl border px-4 py-3 transition outline-none',
                                fieldClass,
                            ]"
                            placeholder="Which PowerX course should I take?"
                        />
                        <span
                            v-if="firstError('message')"
                            class="text-sm font-bold text-powerx-yellow"
                        >
                            {{ firstError('message') }}
                        </span>
                    </label>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="grid gap-2">
                            <span class="text-sm font-bold">Name</span>
                            <input
                                v-model="name"
                                :class="[
                                    'h-12 rounded-xl border px-4 transition outline-none',
                                    fieldClass,
                                ]"
                                placeholder="Full name"
                            />
                        </label>
                        <label class="grid gap-2">
                            <span class="text-sm font-bold">Company</span>
                            <input
                                v-model="companyName"
                                :class="[
                                    'h-12 rounded-xl border px-4 transition outline-none',
                                    fieldClass,
                                ]"
                                placeholder="Optional"
                            />
                        </label>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="grid gap-2">
                            <span class="text-sm font-bold">Email</span>
                            <input
                                v-model="email"
                                type="email"
                                :class="[
                                    'h-12 rounded-xl border px-4 transition outline-none',
                                    fieldClass,
                                ]"
                                placeholder="name@example.com"
                            />
                            <span
                                v-if="firstError('email')"
                                class="text-sm font-bold text-powerx-yellow"
                            >
                                {{ firstError('email') }}
                            </span>
                        </label>
                        <label class="grid gap-2">
                            <span class="text-sm font-bold">Mobile</span>
                            <input
                                v-model="phone"
                                :class="[
                                    'h-12 rounded-xl border px-4 transition outline-none',
                                    fieldClass,
                                ]"
                                placeholder="+974..."
                            />
                        </label>
                    </div>

                    <Button
                        type="submit"
                        :disabled="isSubmitting"
                        class="h-12 rounded-full bg-powerx-yellow text-sm font-black tracking-wide text-powerx-navy uppercase hover:bg-powerx-gold disabled:opacity-60"
                    >
                        {{ isSubmitting ? 'Sending...' : 'Ask assistant' }}
                        <Send class="size-4" />
                    </Button>

                    <div
                        v-if="response"
                        class="rounded-xl border border-powerx-success/30 bg-powerx-success/10 p-4"
                        aria-live="polite"
                    >
                        <p class="text-sm leading-7">{{ response.answer }}</p>
                        <p :class="['mt-3 text-xs leading-6', mutedClass]">
                            {{ response.disclaimer }}
                        </p>
                        <p
                            v-if="response.handoffCreated"
                            class="mt-3 text-sm font-black text-powerx-success"
                        >
                            CRM handoff created.
                        </p>
                    </div>

                    <p
                        v-else-if="hasSubmitted && Object.keys(errors).length"
                        class="rounded-xl border border-powerx-yellow/30 bg-powerx-yellow/10 px-4 py-3 text-sm font-bold text-powerx-yellow"
                        aria-live="polite"
                    >
                        Check the highlighted assistant fields.
                    </p>
                </form>
            </div>
        </div>
    </section>
</template>
