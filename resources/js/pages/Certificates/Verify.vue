<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    BadgeCheck,
    CalendarCheck,
    CheckCircle2,
    GraduationCap,
    ShieldCheck,
} from 'lucide-vue-next';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import BrandStatusBadge from '@/components/powerx/BrandStatusBadge.vue';
import PublicThemeSwitcher from '@/components/powerx/PublicThemeSwitcher.vue';
import { home } from '@/routes';

defineProps<{
    certificate: {
        number: string;
        status: string;
        result: string;
        issuedAt: string | null;
        expiresAt: string | null;
        course: { title: string; category: string | null };
        student: { name: string };
    };
}>();
</script>
<template>
    <Head title="Verify certificate" />
    <main
        class="min-h-screen bg-background text-foreground dark:bg-powerx-ink dark:text-white"
    >
        <section class="relative isolate overflow-hidden">
            <div
                class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_20%_18%,rgba(45,185,33,0.18),transparent_24%),radial-gradient(circle_at_82%_10%,rgba(255,193,7,0.24),transparent_26%),linear-gradient(135deg,#f8fafc_0%,#edf3f8_48%,#dce9f5_100%)] dark:hidden"
            />
            <div
                class="absolute inset-0 -z-10 hidden bg-[radial-gradient(circle_at_20%_18%,rgba(45,185,33,0.20),transparent_24%),radial-gradient(circle_at_82%_10%,rgba(255,193,7,0.20),transparent_26%),linear-gradient(135deg,#020101_0%,#071523_48%,#0b2034_100%)] dark:block"
            />
            <div
                class="absolute inset-0 -z-10 [background-image:linear-gradient(rgba(7,21,35,0.08)_1px,transparent_1px),linear-gradient(90deg,rgba(7,21,35,0.08)_1px,transparent_1px)] [background-size:72px_72px] opacity-25 dark:hidden"
            />
            <div
                class="absolute inset-0 -z-10 hidden [background-image:linear-gradient(rgba(255,255,255,0.08)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.08)_1px,transparent_1px)] [background-size:72px_72px] opacity-25 dark:block"
            />
            <header
                class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-6 sm:px-6 lg:px-8"
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
                            Certificate verification
                        </span>
                    </span>
                </Link>
                <div class="flex items-center gap-2 sm:gap-3">
                    <PublicThemeSwitcher />
                    <BrandStatusBadge label="Verified record" tone="success" />
                </div>
            </header>
            <div
                class="mx-auto grid max-w-7xl gap-10 px-6 py-16 lg:grid-cols-[0.9fr_1.1fr] lg:px-8 lg:py-24"
            >
                <div class="flex flex-col justify-center">
                    <div
                        class="flex size-20 items-center justify-center rounded-[1.75rem] bg-powerx-success text-powerx-ink shadow-[0_0_48px_rgba(45,185,33,0.38)]"
                    >
                        <CheckCircle2 class="size-11" />
                    </div>
                    <h1
                        class="mt-8 max-w-3xl text-5xl leading-[0.95] font-black uppercase sm:text-6xl"
                    >
                        This PowerX certificate is valid.
                    </h1>
                    <p
                        class="mt-6 max-w-2xl text-lg leading-8 text-muted-foreground dark:text-white/70"
                    >
                        The verification page confirms the training record
                        issued by PowerX. It does not expose private contact,
                        payment, document, or exam-answer details.
                    </p>
                </div>
                <article
                    class="rounded-[2rem] border border-border bg-card/90 p-5 shadow-[0_36px_100px_rgba(0,0,0,0.45)] backdrop-blur dark:border-white/10 dark:bg-white/[0.06]"
                >
                    <div
                        class="rounded-[1.5rem] border border-border bg-card p-6 dark:border-white/10 dark:bg-powerx-navy"
                    >
                        <div class="flex items-start justify-between gap-5">
                            <div>
                                <p
                                    class="text-xs font-black tracking-[0.28em] text-powerx-yellow uppercase"
                                >
                                    Certificate number
                                </p>
                                <h2 class="mt-3 text-3xl font-black">
                                    {{ certificate.number }}
                                </h2>
                            </div>
                            <BadgeCheck class="size-10 text-powerx-success" />
                        </div>
                        <dl class="mt-8 grid gap-4">
                            <div
                                class="rounded-2xl border border-border bg-muted/70 p-4 dark:border-white/10 dark:bg-white/[0.05]"
                            >
                                <dt
                                    class="text-xs font-black tracking-[0.22em] text-muted-foreground uppercase dark:text-white/45"
                                >
                                    Student
                                </dt>
                                <dd class="mt-2 text-xl font-black">
                                    {{ certificate.student.name }}
                                </dd>
                            </div>
                            <div
                                class="rounded-2xl border border-border bg-muted/70 p-4 dark:border-white/10 dark:bg-white/[0.05]"
                            >
                                <dt
                                    class="text-xs font-black tracking-[0.22em] text-muted-foreground uppercase dark:text-white/45"
                                >
                                    Course
                                </dt>
                                <dd class="mt-2 text-xl font-black">
                                    {{ certificate.course.title }}
                                </dd>
                                <p
                                    v-if="certificate.course.category"
                                    class="mt-1 text-sm text-powerx-yellow"
                                >
                                    {{ certificate.course.category }}
                                </p>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div
                                    class="rounded-2xl border border-border bg-muted/70 p-4 dark:border-white/10 dark:bg-white/[0.05]"
                                >
                                    <dt
                                        class="flex items-center gap-2 text-xs font-black tracking-[0.22em] text-muted-foreground uppercase dark:text-white/45"
                                    >
                                        <CalendarCheck class="size-4" /> Issued
                                    </dt>
                                    <dd class="mt-2 font-black">
                                        {{
                                            certificate.issuedAt ?? 'Confirmed'
                                        }}
                                    </dd>
                                </div>
                                <div
                                    class="rounded-2xl border border-border bg-muted/70 p-4 dark:border-white/10 dark:bg-white/[0.05]"
                                >
                                    <dt
                                        class="flex items-center gap-2 text-xs font-black tracking-[0.22em] text-muted-foreground uppercase dark:text-white/45"
                                    >
                                        <ShieldCheck class="size-4" /> Expires
                                    </dt>
                                    <dd class="mt-2 font-black">
                                        {{
                                            certificate.expiresAt ?? 'No expiry'
                                        }}
                                    </dd>
                                </div>
                            </div>
                            <div
                                class="flex items-center justify-between gap-4 rounded-2xl border border-powerx-success/30 bg-powerx-success/10 p-4"
                            >
                                <div>
                                    <dt
                                        class="text-xs font-black tracking-[0.22em] text-powerx-success uppercase"
                                    >
                                        Status
                                    </dt>
                                    <dd class="mt-2 font-black capitalize">
                                        {{ certificate.status }} /
                                        {{ certificate.result }}
                                    </dd>
                                </div>
                                <GraduationCap
                                    class="size-8 text-powerx-success"
                                />
                            </div>
                        </dl>
                    </div>
                </article>
            </div>
        </section>
    </main>
</template>
