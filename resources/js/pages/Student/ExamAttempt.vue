<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    CheckCircle2,
    Clock3,
    ListChecks,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { portal as studentPortal } from '@/routes/student';
import { index as studentExamsIndex } from '@/routes/student/exams';
import type {
    ExamAttemptQuestion,
    StudentExamAttemptProps,
    Team,
} from '@/types';

type AnswerPayload = {
    question_id: number;
    answer: string[];
};

const props = defineProps<StudentExamAttemptProps>();
const currentQuestionIndex = ref(0);
const now = ref(Date.now());
let timer: number | undefined;

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
                title: 'Exams',
                href: props.currentTeam
                    ? studentExamsIndex(props.currentTeam.slug).url
                    : '/',
            },
            {
                title: 'Attempt',
                href: '#',
            },
        ],
    }),
});

const form = useForm<{ answers: AnswerPayload[] }>({
    answers: props.questions.map((question) => ({
        question_id: question.id,
        answer: [...question.answer],
    })),
});

const currentQuestion = computed<ExamAttemptQuestion>(
    () => props.questions[currentQuestionIndex.value],
);

const answeredCount = computed(
    () => form.answers.filter((answer) => answer.answer.length > 0).length,
);

const remainingSeconds = computed(() => {
    if (!props.attempt.expiresAt || props.attempt.isSubmitted) {
        return 0;
    }

    return Math.max(
        0,
        Math.floor((Date.parse(props.attempt.expiresAt) - now.value) / 1000),
    );
});

const remainingLabel = computed(() => {
    const minutes = Math.floor(remainingSeconds.value / 60)
        .toString()
        .padStart(2, '0');
    const seconds = (remainingSeconds.value % 60).toString().padStart(2, '0');

    return `${minutes}:${seconds}`;
});

const scoreLabel = computed(() =>
    props.attempt.score === null ? 'Not scored' : `${props.attempt.score}%`,
);

const answerFor = (question: ExamAttemptQuestion): AnswerPayload => {
    const existing = form.answers.find(
        (answer) => answer.question_id === question.id,
    );

    if (existing) {
        return existing;
    }

    const answer = { question_id: question.id, answer: [] };
    form.answers.push(answer);

    return answer;
};

const isMultipleChoice = (question: ExamAttemptQuestion): boolean =>
    question.type.includes('multiple');

const isOptionSelected = (
    question: ExamAttemptQuestion,
    optionKey: string,
): boolean => answerFor(question).answer.includes(optionKey);

const toggleOption = (
    question: ExamAttemptQuestion,
    optionKey: string,
): void => {
    if (props.attempt.isSubmitted) {
        return;
    }

    const answer = answerFor(question);

    if (isMultipleChoice(question)) {
        answer.answer = answer.answer.includes(optionKey)
            ? answer.answer.filter((key) => key !== optionKey)
            : [...answer.answer, optionKey];
    } else {
        answer.answer = [optionKey];
    }
};

const autosave = (): void => {
    if (props.attempt.isSubmitted) {
        return;
    }

    form.patch(props.attempt.autosaveUrl, {
        only: ['questions'],
        preserveScroll: true,
    });
};

const submitAttempt = (): void => {
    if (!window.confirm('Submit this exam attempt now?')) {
        return;
    }

    form.post(props.attempt.submitUrl, {
        preserveScroll: true,
    });
};

onMounted(() => {
    if (!props.attempt.isSubmitted) {
        timer = window.setInterval(() => {
            now.value = Date.now();
        }, 1000);
    }
});

onBeforeUnmount(() => {
    if (timer) {
        window.clearInterval(timer);
    }
});
</script>

<template>
    <Head :title="exam.title" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
        <section
            class="rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm dark:border-sidebar-border"
        >
            <div class="flex flex-col justify-between gap-4 lg:flex-row">
                <div>
                    <Link
                        :href="
                            studentExamsIndex(
                                $page.props.currentTeam?.slug ?? '',
                            ).url
                        "
                        class="inline-flex items-center gap-2 text-sm font-black text-powerx-yellow"
                    >
                        <ArrowLeft class="size-4" />
                        Exams
                    </Link>
                    <p
                        class="mt-4 text-xs font-black tracking-[0.2em] text-powerx-yellow uppercase"
                    >
                        {{ enrollment.courseTitle }}
                    </p>
                    <h1 class="mt-2 text-3xl font-black">
                        {{ exam.title }}
                    </h1>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Attempt {{ attempt.attemptNumber }} · pass mark
                        {{ exam.passMark }}%
                    </p>
                </div>
                <div class="grid min-w-56 gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl bg-muted/50 p-4">
                        <p
                            class="text-xs font-black tracking-[0.18em] text-muted-foreground uppercase"
                        >
                            Time
                        </p>
                        <p
                            class="mt-2 flex items-center gap-2 text-2xl font-black"
                        >
                            <Clock3 class="size-5 text-powerx-yellow" />
                            {{
                                attempt.isSubmitted ? 'Locked' : remainingLabel
                            }}
                        </p>
                    </div>
                    <div class="rounded-2xl bg-muted/50 p-4">
                        <p
                            class="text-xs font-black tracking-[0.18em] text-muted-foreground uppercase"
                        >
                            Answered
                        </p>
                        <p class="mt-2 text-2xl font-black">
                            {{ answeredCount }} / {{ questions.length }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section
            v-if="attempt.isSubmitted"
            class="rounded-2xl border border-powerx-success/30 bg-powerx-success/10 p-5"
        >
            <div class="flex flex-col justify-between gap-4 lg:flex-row">
                <div>
                    <p
                        class="text-xs font-black tracking-[0.2em] text-powerx-success uppercase"
                    >
                        Submitted
                    </p>
                    <h2 class="mt-2 text-2xl font-black">
                        {{ attempt.result }} · {{ scoreLabel }}
                    </h2>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Duration:
                        {{
                            attempt.durationSeconds === null
                                ? 'Not recorded'
                                : `${attempt.durationSeconds}s`
                        }}
                    </p>
                </div>
                <Link
                    :href="
                        studentExamsIndex($page.props.currentTeam?.slug ?? '')
                            .url
                    "
                    class="inline-flex h-fit items-center gap-2 rounded-full bg-powerx-yellow px-4 py-2 text-sm font-black text-powerx-navy transition hover:bg-powerx-yellow/90"
                >
                    Exam list
                    <ArrowRight class="size-4" />
                </Link>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[18rem_1fr]">
            <aside
                class="rounded-2xl border border-sidebar-border/70 bg-card p-4 shadow-sm dark:border-sidebar-border"
            >
                <p
                    class="text-xs font-black tracking-[0.18em] text-powerx-yellow uppercase"
                >
                    Questions
                </p>
                <div class="mt-4 grid grid-cols-5 gap-2 xl:grid-cols-4">
                    <button
                        v-for="(question, index) in questions"
                        :key="question.id"
                        type="button"
                        class="flex aspect-square items-center justify-center rounded-xl border text-sm font-black transition"
                        :class="
                            index === currentQuestionIndex
                                ? 'border-powerx-yellow bg-powerx-yellow text-powerx-navy'
                                : answerFor(question).answer.length > 0
                                  ? 'border-powerx-success/40 bg-powerx-success/10 text-powerx-success'
                                  : 'border-border hover:border-powerx-yellow'
                        "
                        @click="currentQuestionIndex = index"
                    >
                        {{ index + 1 }}
                    </button>
                </div>
            </aside>

            <article
                class="rounded-2xl border border-sidebar-border/70 bg-card p-5 shadow-sm dark:border-sidebar-border"
            >
                <div class="flex flex-col justify-between gap-4 lg:flex-row">
                    <div>
                        <p
                            class="text-xs font-black tracking-[0.18em] text-powerx-yellow uppercase"
                        >
                            {{ currentQuestion.topic ?? 'Question' }}
                        </p>
                        <h2 class="mt-2 text-xl font-black">
                            {{ currentQuestion.questionText }}
                        </h2>
                    </div>
                    <span
                        class="h-fit rounded-full border border-border px-3 py-1 text-xs font-black tracking-[0.18em] text-muted-foreground uppercase"
                    >
                        {{ currentQuestion.difficulty ?? 'Standard' }}
                    </span>
                </div>

                <div class="mt-5 grid gap-3">
                    <label
                        v-for="option in currentQuestion.options"
                        :key="option.key"
                        class="flex cursor-pointer items-start gap-3 rounded-2xl border border-border p-4 transition hover:border-powerx-yellow"
                        :class="
                            isOptionSelected(currentQuestion, option.key)
                                ? 'border-powerx-yellow bg-powerx-yellow/10'
                                : ''
                        "
                    >
                        <input
                            class="mt-1"
                            :type="
                                isMultipleChoice(currentQuestion)
                                    ? 'checkbox'
                                    : 'radio'
                            "
                            :name="`question-${currentQuestion.id}`"
                            :checked="
                                isOptionSelected(currentQuestion, option.key)
                            "
                            :disabled="attempt.isSubmitted"
                            @change="toggleOption(currentQuestion, option.key)"
                        />
                        <span>
                            <span class="font-black">{{ option.key }}.</span>
                            {{ option.label }}
                        </span>
                    </label>
                </div>

                <div
                    v-if="attempt.isSubmitted && currentQuestion.explanation"
                    class="mt-5 rounded-2xl border border-border bg-muted/50 p-4 text-sm"
                >
                    <p class="font-black">Explanation</p>
                    <p class="mt-2 text-muted-foreground">
                        {{ currentQuestion.explanation }}
                    </p>
                </div>

                <div class="mt-6 flex flex-wrap justify-between gap-2">
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-full border border-border px-4 py-2 text-sm font-black transition hover:border-powerx-yellow hover:text-powerx-yellow disabled:pointer-events-none disabled:opacity-60"
                            :disabled="currentQuestionIndex === 0"
                            @click="currentQuestionIndex -= 1"
                        >
                            <ArrowLeft class="size-4" />
                            Previous
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-full border border-border px-4 py-2 text-sm font-black transition hover:border-powerx-yellow hover:text-powerx-yellow disabled:pointer-events-none disabled:opacity-60"
                            :disabled="
                                currentQuestionIndex === questions.length - 1
                            "
                            @click="currentQuestionIndex += 1"
                        >
                            Next
                            <ArrowRight class="size-4" />
                        </button>
                    </div>
                    <div
                        v-if="!attempt.isSubmitted"
                        class="flex flex-wrap gap-2"
                    >
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-full border border-border px-4 py-2 text-sm font-black transition hover:border-powerx-yellow hover:text-powerx-yellow disabled:pointer-events-none disabled:opacity-60"
                            :disabled="form.processing"
                            @click="autosave"
                        >
                            <ListChecks class="size-4" />
                            Save answers
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-full bg-powerx-yellow px-4 py-2 text-sm font-black text-powerx-navy transition hover:bg-powerx-yellow/90 disabled:pointer-events-none disabled:opacity-60"
                            :disabled="form.processing || answeredCount === 0"
                            @click="submitAttempt"
                        >
                            <CheckCircle2 class="size-4" />
                            Submit attempt
                        </button>
                    </div>
                </div>
            </article>
        </section>
    </div>
</template>
