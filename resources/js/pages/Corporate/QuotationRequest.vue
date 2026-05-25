<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import type { FormDataKeys } from '@inertiajs/core';
import {
    ArrowLeft,
    Building2,
    Plus,
    Send,
    Trash2,
    UsersRound,
} from 'lucide-vue-next';
import { computed, watch } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';
import { index as coursesIndex } from '@/routes/courses';
import { store as storeQuotation } from '@/routes/corporate/quotations';

interface PackageOption {
    id: number;
    name: string;
    currency: string;
    price: number;
}

interface CourseOption {
    id: number;
    title: string;
    category: string | null;
    currency: string;
    basePrice: number;
    packages: PackageOption[];
}

interface EmployeeForm {
    full_name: string;
    email: string;
    mobile: string;
    profession: string;
    preferred_schedule: string;
    notes: string;
}

interface CorporateQuotationForm {
    company_name: string;
    contact_name: string;
    email: string;
    phone: string;
    address: string;
    course_id: string;
    course_package_id: string;
    notes: string;
    employees: EmployeeForm[];
}

const props = defineProps<{
    courseOptions: CourseOption[];
}>();

const blankEmployee = (): EmployeeForm => ({
    full_name: '',
    email: '',
    mobile: '',
    profession: '',
    preferred_schedule: '',
    notes: '',
});

const form = useForm<CorporateQuotationForm>({
    company_name: '',
    contact_name: '',
    email: '',
    phone: '',
    address: '',
    course_id: '',
    course_package_id: '',
    notes: '',
    employees: [blankEmployee()],
});

const selectedCourse = computed(() =>
    props.courseOptions.find((course) => course.id === Number(form.course_id)),
);

const selectedPackages = computed(() => selectedCourse.value?.packages ?? []);

watch(
    () => form.course_id,
    () => {
        form.course_package_id = '';
    },
);

const addEmployee = (): void => {
    form.employees.push(blankEmployee());
};

const removeEmployee = (index: number): void => {
    if (form.employees.length === 1) {
        form.employees = [blankEmployee()];

        return;
    }

    form.employees.splice(index, 1);
};

const submit = (): void => {
    form.post(storeQuotation().url, {
        preserveScroll: true,
    });
};

const employeeError = (
    index: number,
    field: keyof EmployeeForm,
): string | undefined => {
    const key =
        `employees.${index}.${field}` as FormDataKeys<CorporateQuotationForm>;

    return form.errors[key];
};
</script>

<template>
    <Head title="Corporate quotation request" />

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
                        class="flex size-12 items-center justify-center rounded-2xl bg-powerx-yellow text-powerx-navy shadow-[0_0_34px_rgba(255,193,7,0.45)]"
                    >
                        <AppLogoIcon class="size-8 text-current" />
                    </span>
                    <span class="grid leading-none">
                        <span class="text-xl font-black tracking-wide">
                            POWER<span class="text-powerx-yellow">X</span>
                        </span>
                        <span
                            class="text-[11px] font-bold tracking-[0.28em] text-white/60 uppercase"
                        >
                            Corporate training
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
                class="mx-auto grid max-w-7xl gap-10 px-6 py-16 lg:grid-cols-[0.9fr_1.1fr] lg:px-8 lg:py-24"
            >
                <div>
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-powerx-yellow/30 bg-powerx-yellow/10 px-4 py-2 text-sm font-black text-powerx-yellow"
                    >
                        <Building2 class="size-4" />
                        Quotation and bulk enrollment
                    </div>
                    <h1
                        class="mt-8 max-w-4xl text-5xl leading-[0.95] font-black tracking-tight uppercase sm:text-6xl"
                    >
                        Train your team with PowerX
                    </h1>
                    <p class="mt-6 max-w-2xl text-lg leading-8 text-white/70">
                        Submit a company profile, requested course, and employee
                        list. PowerX will generate a quotation, link the
                        requested enrollments, and follow up on payment and
                        batch assignment.
                    </p>
                    <div class="mt-10 grid gap-4 sm:grid-cols-3">
                        <div
                            class="rounded-2xl border border-white/10 bg-white/[0.05] p-5"
                        >
                            <UsersRound class="size-7 text-powerx-yellow" />
                            <p class="mt-4 text-sm text-white/70">
                                Bulk employee enrollment
                            </p>
                        </div>
                        <div
                            class="rounded-2xl border border-white/10 bg-white/[0.05] p-5"
                        >
                            <Building2 class="size-7 text-powerx-cyan" />
                            <p class="mt-4 text-sm text-white/70">
                                Company quotation record
                            </p>
                        </div>
                        <div
                            class="rounded-2xl border border-white/10 bg-white/[0.05] p-5"
                        >
                            <Send class="size-7 text-powerx-success" />
                            <p class="mt-4 text-sm text-white/70">
                                Finance follow-up ready
                            </p>
                        </div>
                    </div>
                </div>

                <form
                    class="rounded-[2rem] border border-white/10 bg-white/[0.06] p-5 shadow-[0_36px_100px_rgba(0,0,0,0.45)] backdrop-blur"
                    @submit.prevent="submit"
                >
                    <div
                        class="grid gap-5 rounded-[1.5rem] border border-white/10 bg-powerx-navy p-6"
                    >
                        <div>
                            <p
                                class="text-xs font-black tracking-[0.26em] text-powerx-yellow uppercase"
                            >
                                Company details
                            </p>
                            <h2 class="mt-3 text-2xl font-black">
                                Request a quotation
                            </h2>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="grid gap-2">
                                <span class="text-sm font-bold text-white/75">
                                    Company name
                                </span>
                                <input
                                    v-model="form.company_name"
                                    class="h-12 rounded-2xl border border-white/10 bg-white/[0.07] px-4 text-white transition outline-none placeholder:text-white/35 focus:border-powerx-yellow"
                                    placeholder="Company legal name"
                                />
                                <span
                                    v-if="form.errors.company_name"
                                    class="text-sm text-powerx-yellow"
                                >
                                    {{ form.errors.company_name }}
                                </span>
                            </label>
                            <label class="grid gap-2">
                                <span class="text-sm font-bold text-white/75">
                                    Contact person
                                </span>
                                <input
                                    v-model="form.contact_name"
                                    class="h-12 rounded-2xl border border-white/10 bg-white/[0.07] px-4 text-white transition outline-none placeholder:text-white/35 focus:border-powerx-yellow"
                                    placeholder="Training coordinator"
                                />
                                <span
                                    v-if="form.errors.contact_name"
                                    class="text-sm text-powerx-yellow"
                                >
                                    {{ form.errors.contact_name }}
                                </span>
                            </label>
                            <label class="grid gap-2">
                                <span class="text-sm font-bold text-white/75">
                                    Email
                                </span>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    class="h-12 rounded-2xl border border-white/10 bg-white/[0.07] px-4 text-white transition outline-none placeholder:text-white/35 focus:border-powerx-yellow"
                                    placeholder="training@example.com"
                                />
                                <span
                                    v-if="form.errors.email"
                                    class="text-sm text-powerx-yellow"
                                >
                                    {{ form.errors.email }}
                                </span>
                            </label>
                            <label class="grid gap-2">
                                <span class="text-sm font-bold text-white/75">
                                    Phone / WhatsApp
                                </span>
                                <input
                                    v-model="form.phone"
                                    class="h-12 rounded-2xl border border-white/10 bg-white/[0.07] px-4 text-white transition outline-none placeholder:text-white/35 focus:border-powerx-yellow"
                                    placeholder="+974..."
                                />
                                <span
                                    v-if="form.errors.phone"
                                    class="text-sm text-powerx-yellow"
                                >
                                    {{ form.errors.phone }}
                                </span>
                            </label>
                        </div>

                        <label class="grid gap-2">
                            <span class="text-sm font-bold text-white/75">
                                Address
                            </span>
                            <input
                                v-model="form.address"
                                class="h-12 rounded-2xl border border-white/10 bg-white/[0.07] px-4 text-white transition outline-none placeholder:text-white/35 focus:border-powerx-yellow"
                                placeholder="Company address or site location"
                            />
                        </label>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="grid gap-2">
                                <span class="text-sm font-bold text-white/75">
                                    Course
                                </span>
                                <select
                                    v-model="form.course_id"
                                    class="h-12 rounded-2xl border border-white/10 bg-powerx-panel px-4 text-white transition outline-none focus:border-powerx-yellow"
                                >
                                    <option value="">Select a course</option>
                                    <option
                                        v-for="course in courseOptions"
                                        :key="course.id"
                                        :value="String(course.id)"
                                    >
                                        {{ course.title }}
                                    </option>
                                </select>
                                <span
                                    v-if="form.errors.course_id"
                                    class="text-sm text-powerx-yellow"
                                >
                                    {{ form.errors.course_id }}
                                </span>
                            </label>
                            <label class="grid gap-2">
                                <span class="text-sm font-bold text-white/75">
                                    Package
                                </span>
                                <select
                                    v-model="form.course_package_id"
                                    class="h-12 rounded-2xl border border-white/10 bg-powerx-panel px-4 text-white transition outline-none focus:border-powerx-yellow"
                                    :disabled="selectedPackages.length === 0"
                                >
                                    <option value="">
                                        Standard course pricing
                                    </option>
                                    <option
                                        v-for="coursePackage in selectedPackages"
                                        :key="coursePackage.id"
                                        :value="String(coursePackage.id)"
                                    >
                                        {{ coursePackage.name }} -
                                        {{ coursePackage.currency }}
                                        {{ coursePackage.price }}
                                    </option>
                                </select>
                            </label>
                        </div>

                        <div class="grid gap-4">
                            <div
                                class="flex items-center justify-between gap-4"
                            >
                                <div>
                                    <p
                                        class="text-xs font-black tracking-[0.24em] text-powerx-yellow uppercase"
                                    >
                                        Employee list
                                    </p>
                                    <p class="mt-1 text-sm text-white/55">
                                        Add each employee who needs enrollment.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-full border border-white/15 px-4 py-2 text-sm font-black text-white/80 transition hover:border-powerx-yellow hover:text-powerx-yellow"
                                    @click="addEmployee"
                                >
                                    <Plus class="size-4" />
                                    Add
                                </button>
                            </div>

                            <div
                                v-for="(employee, index) in form.employees"
                                :key="index"
                                class="grid gap-3 rounded-2xl border border-white/10 bg-white/[0.04] p-4"
                            >
                                <div
                                    class="flex items-center justify-between gap-4"
                                >
                                    <p class="font-black">
                                        Employee {{ index + 1 }}
                                    </p>
                                    <button
                                        type="button"
                                        class="text-white/55 transition hover:text-powerx-yellow"
                                        @click="removeEmployee(index)"
                                    >
                                        <Trash2 class="size-4" />
                                    </button>
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <label class="grid gap-2">
                                        <span
                                            class="text-sm font-bold text-white/75"
                                        >
                                            Full name
                                        </span>
                                        <input
                                            v-model="employee.full_name"
                                            class="h-11 rounded-2xl border border-white/10 bg-white/[0.07] px-4 text-white outline-none focus:border-powerx-yellow"
                                            placeholder="Employee full name"
                                        />
                                        <span
                                            v-if="
                                                employeeError(
                                                    index,
                                                    'full_name',
                                                )
                                            "
                                            class="text-sm text-powerx-yellow"
                                        >
                                            {{
                                                employeeError(
                                                    index,
                                                    'full_name',
                                                )
                                            }}
                                        </span>
                                    </label>
                                    <label class="grid gap-2">
                                        <span
                                            class="text-sm font-bold text-white/75"
                                        >
                                            Email
                                        </span>
                                        <input
                                            v-model="employee.email"
                                            type="email"
                                            class="h-11 rounded-2xl border border-white/10 bg-white/[0.07] px-4 text-white outline-none focus:border-powerx-yellow"
                                            placeholder="employee@example.com"
                                        />
                                    </label>
                                    <label class="grid gap-2">
                                        <span
                                            class="text-sm font-bold text-white/75"
                                        >
                                            Mobile
                                        </span>
                                        <input
                                            v-model="employee.mobile"
                                            class="h-11 rounded-2xl border border-white/10 bg-white/[0.07] px-4 text-white outline-none focus:border-powerx-yellow"
                                            placeholder="+974..."
                                        />
                                    </label>
                                    <label class="grid gap-2">
                                        <span
                                            class="text-sm font-bold text-white/75"
                                        >
                                            Profession
                                        </span>
                                        <input
                                            v-model="employee.profession"
                                            class="h-11 rounded-2xl border border-white/10 bg-white/[0.07] px-4 text-white outline-none focus:border-powerx-yellow"
                                            placeholder="Technician / Engineer"
                                        />
                                    </label>
                                </div>
                            </div>
                            <span
                                v-if="form.errors.employees"
                                class="text-sm text-powerx-yellow"
                            >
                                {{ form.errors.employees }}
                            </span>
                        </div>

                        <label class="grid gap-2">
                            <span class="text-sm font-bold text-white/75">
                                Notes
                            </span>
                            <textarea
                                v-model="form.notes"
                                rows="4"
                                class="rounded-2xl border border-white/10 bg-white/[0.07] px-4 py-3 text-white transition outline-none placeholder:text-white/35 focus:border-powerx-yellow"
                                placeholder="Preferred timing, site constraints, target dates, or quotation notes."
                            />
                        </label>

                        <Button
                            type="submit"
                            class="h-12 rounded-2xl bg-powerx-yellow text-base font-black text-powerx-navy hover:bg-powerx-yellow/90"
                            :disabled="form.processing"
                        >
                            <Send class="mr-2 size-4" />
                            {{
                                form.processing
                                    ? 'Submitting request...'
                                    : 'Request quotation'
                            }}
                        </Button>
                    </div>
                </form>
            </div>
        </section>
    </main>
</template>
