<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Award,
    Banknote,
    BookOpen,
    BriefcaseBusiness,
    CalendarClock,
    Folder,
    GraduationCap,
    LayoutGrid,
    Menu,
    Search,
    Settings,
    ShieldCheck,
    UsersRound,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import TeamSwitcher from '@/components/TeamSwitcher.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuList,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { getInitials } from '@/composables/useInitials';
import { toUrl } from '@/lib/utils';
import { dashboard } from '@/routes';
import { portal as corporatePortal } from '@/routes/corporate';
import { portal as instructorPortal } from '@/routes/instructor';
import { portal as studentPortal } from '@/routes/student';
import { index as studentCatalogIndex } from '@/routes/student/catalog';
import { index as studentCertificatesIndex } from '@/routes/student/certificates';
import { index as studentCoursesIndex } from '@/routes/student/courses';
import { index as studentExamsIndex } from '@/routes/student/exams';
import { index as studentPaymentsIndex } from '@/routes/student/payments';
import { index as studentScheduleIndex } from '@/routes/student/schedule';
import { index as teamsIndex } from '@/routes/teams';
import type { BreadcrumbItem, NavItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => page.props.auth);
const { isCurrentUrl, whenCurrentUrl } = useCurrentUrl();

const dashboardUrl = computed(() =>
    page.props.currentTeam ? dashboard(page.props.currentTeam.slug).url : '/',
);

const instructorPortalUrl = computed(() =>
    page.props.currentTeam
        ? instructorPortal(page.props.currentTeam.slug).url
        : '/',
);

const studentPortalUrl = computed(() =>
    page.props.currentTeam
        ? studentPortal(page.props.currentTeam.slug).url
        : '/',
);

const studentCoursesUrl = computed(() =>
    page.props.currentTeam
        ? studentCoursesIndex(page.props.currentTeam.slug).url
        : '/',
);

const studentScheduleUrl = computed(() =>
    page.props.currentTeam
        ? studentScheduleIndex(page.props.currentTeam.slug).url
        : '/',
);

const studentExamsUrl = computed(() =>
    page.props.currentTeam
        ? studentExamsIndex(page.props.currentTeam.slug).url
        : '/',
);

const studentCertificatesUrl = computed(() =>
    page.props.currentTeam
        ? studentCertificatesIndex(page.props.currentTeam.slug).url
        : '/',
);

const studentPaymentsUrl = computed(() =>
    page.props.currentTeam
        ? studentPaymentsIndex(page.props.currentTeam.slug).url
        : '/',
);

const studentCatalogUrl = computed(() =>
    page.props.currentTeam
        ? studentCatalogIndex(page.props.currentTeam.slug).url
        : '/',
);

const corporatePortalUrl = computed(() =>
    page.props.currentTeam
        ? corporatePortal(page.props.currentTeam.slug).url
        : '/',
);

const defaultWorkspaceUrl = computed(() => {
    if (page.props.can.viewOperationsDashboard) {
        return dashboardUrl.value;
    }

    if (page.props.can.viewInstructorPortal) {
        return instructorPortalUrl.value;
    }

    if (page.props.can.viewStudentPortal) {
        return studentPortalUrl.value;
    }

    if (page.props.can.viewCorporatePortal) {
        return corporatePortalUrl.value;
    }

    if (page.props.can.viewAdminPanel) {
        return '/admin';
    }

    return '/';
});

const defaultWorkspaceReloadsPage = computed(
    () =>
        !page.props.can.viewOperationsDashboard &&
        !page.props.can.viewInstructorPortal &&
        !page.props.can.viewStudentPortal &&
        !page.props.can.viewCorporatePortal &&
        page.props.can.viewAdminPanel,
);

function visitWithPageReload(href: NavItem['href']): void {
    const url = toUrl(href);

    if (!url) {
        throw new Error('Navigation item href must resolve to a URL.');
    }

    window.location.assign(url);
}

function visitDefaultWorkspaceWithPageReload(): void {
    window.location.assign(defaultWorkspaceUrl.value);
}

const activeItemStyles =
    'bg-sidebar-accent font-semibold text-sidebar-accent-foreground dark:bg-neutral-800 dark:text-neutral-100';

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];

    if (page.props.can.viewOperationsDashboard) {
        items.push({
            title: 'Dashboard',
            href: dashboardUrl.value,
            icon: LayoutGrid,
        });
    }

    if (page.props.can.viewStudentPortal) {
        items.push({
            title: 'Student overview',
            href: studentPortalUrl.value,
            icon: GraduationCap,
        });
        items.push({
            title: 'My courses',
            href: studentCoursesUrl.value,
            icon: BookOpen,
        });
        items.push({
            title: 'Schedule',
            href: studentScheduleUrl.value,
            icon: CalendarClock,
        });
        items.push({
            title: 'Exams',
            href: studentExamsUrl.value,
            icon: GraduationCap,
        });
        items.push({
            title: 'Certificates',
            href: studentCertificatesUrl.value,
            icon: Award,
        });
        items.push({
            title: 'Payments',
            href: studentPaymentsUrl.value,
            icon: Banknote,
        });
        items.push({
            title: 'Browse courses',
            href: studentCatalogUrl.value,
            icon: BookOpen,
        });
    }

    if (page.props.can.viewInstructorPortal) {
        items.push({
            title: 'Instructor portal',
            href: instructorPortalUrl.value,
            icon: UsersRound,
        });
    }

    if (page.props.can.viewCorporatePortal) {
        items.push({
            title: 'Corporate portal',
            href: corporatePortalUrl.value,
            icon: BriefcaseBusiness,
        });
    }

    if (page.props.can.viewAdminPanel) {
        items.push({
            title: 'Admin panel',
            href: '/admin',
            icon: ShieldCheck,
            fullPageReload: true,
        });
    }

    if (page.props.can.manageTeams) {
        items.push({
            title: 'Team settings',
            href: teamsIndex().url,
            icon: Settings,
        });
    }

    return items;
});

const rightNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <div>
        <div class="border-b border-sidebar-border/80">
            <div class="mx-auto flex h-16 items-center px-4 md:max-w-7xl">
                <!-- Mobile Menu -->
                <div class="lg:hidden">
                    <Sheet>
                        <SheetTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="mr-2 h-9 w-9"
                            >
                                <Menu class="h-5 w-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" class="w-[300px] p-6">
                            <SheetTitle class="sr-only"
                                >Navigation menu</SheetTitle
                            >
                            <SheetHeader class="flex justify-start text-left">
                                <AppLogoIcon
                                    class="size-6 fill-current text-black dark:text-white"
                                />
                            </SheetHeader>
                            <div
                                class="flex h-full flex-1 flex-col justify-between space-y-4 py-6"
                            >
                                <nav class="-mx-3 space-y-1">
                                    <template
                                        v-for="item in mainNavItems"
                                        :key="item.title"
                                    >
                                        <a
                                            v-if="item.fullPageReload"
                                            :href="toUrl(item.href)"
                                            class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent"
                                            :class="
                                                whenCurrentUrl(
                                                    item.href,
                                                    activeItemStyles,
                                                )
                                            "
                                            @click.prevent.stop="
                                                visitWithPageReload(item.href)
                                            "
                                        >
                                            <component
                                                v-if="item.icon"
                                                :is="item.icon"
                                                class="h-5 w-5"
                                            />
                                            {{ item.title }}
                                        </a>
                                        <Link
                                            v-else
                                            :href="item.href"
                                            class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent"
                                            :class="
                                                whenCurrentUrl(
                                                    item.href,
                                                    activeItemStyles,
                                                )
                                            "
                                        >
                                            <component
                                                v-if="item.icon"
                                                :is="item.icon"
                                                class="h-5 w-5"
                                            />
                                            {{ item.title }}
                                        </Link>
                                    </template>
                                </nav>
                                <div class="flex flex-col space-y-4">
                                    <a
                                        v-for="item in rightNavItems"
                                        :key="item.title"
                                        :href="toUrl(item.href)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex items-center space-x-2 text-sm font-medium"
                                    >
                                        <component
                                            v-if="item.icon"
                                            :is="item.icon"
                                            class="h-5 w-5"
                                        />
                                        <span>{{ item.title }}</span>
                                    </a>
                                </div>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>

                <a
                    v-if="defaultWorkspaceReloadsPage"
                    :href="defaultWorkspaceUrl"
                    class="flex items-center gap-x-2"
                    @click.prevent.stop="visitDefaultWorkspaceWithPageReload()"
                >
                    <AppLogo />
                </a>
                <Link
                    v-else
                    :href="defaultWorkspaceUrl"
                    class="flex items-center gap-x-2"
                >
                    <AppLogo />
                </Link>

                <!-- Desktop Menu -->
                <div class="hidden h-full lg:flex lg:flex-1">
                    <NavigationMenu class="ml-10 flex h-full items-stretch">
                        <NavigationMenuList
                            class="flex h-full items-stretch space-x-2"
                        >
                            <NavigationMenuItem
                                v-for="(item, index) in mainNavItems"
                                :key="index"
                                class="relative flex h-full items-center"
                            >
                                <a
                                    v-if="item.fullPageReload"
                                    :class="[
                                        navigationMenuTriggerStyle(),
                                        whenCurrentUrl(
                                            item.href,
                                            activeItemStyles,
                                        ),
                                        'h-9 cursor-pointer px-3',
                                    ]"
                                    :href="toUrl(item.href)"
                                    @click.prevent.stop="
                                        visitWithPageReload(item.href)
                                    "
                                >
                                    <component
                                        v-if="item.icon"
                                        :is="item.icon"
                                        class="mr-2 h-4 w-4"
                                    />
                                    {{ item.title }}
                                </a>
                                <Link
                                    v-else
                                    :class="[
                                        navigationMenuTriggerStyle(),
                                        whenCurrentUrl(
                                            item.href,
                                            activeItemStyles,
                                        ),
                                        'h-9 cursor-pointer px-3',
                                    ]"
                                    :href="item.href"
                                >
                                    <component
                                        v-if="item.icon"
                                        :is="item.icon"
                                        class="mr-2 h-4 w-4"
                                    />
                                    {{ item.title }}
                                </Link>
                                <div
                                    v-if="isCurrentUrl(item.href)"
                                    class="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-black dark:bg-white"
                                ></div>
                            </NavigationMenuItem>
                        </NavigationMenuList>
                    </NavigationMenu>
                </div>

                <div class="ml-auto flex items-center space-x-2">
                    <div class="relative flex items-center space-x-1">
                        <Button
                            variant="ghost"
                            size="icon"
                            class="group h-9 w-9 cursor-pointer"
                        >
                            <Search
                                class="size-5 opacity-80 group-hover:opacity-100"
                            />
                        </Button>

                        <div class="hidden space-x-1 lg:flex">
                            <template
                                v-for="item in rightNavItems"
                                :key="item.title"
                            >
                                <TooltipProvider :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                as-child
                                                class="group h-9 w-9 cursor-pointer"
                                            >
                                                <a
                                                    :href="toUrl(item.href)"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                >
                                                    <span class="sr-only">{{
                                                        item.title
                                                    }}</span>
                                                    <component
                                                        :is="item.icon"
                                                        class="size-5 opacity-80 group-hover:opacity-100"
                                                    />
                                                </a>
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{{ item.title }}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </template>
                        </div>
                    </div>

                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
                            >
                                <Avatar
                                    class="size-8 overflow-hidden rounded-full"
                                >
                                    <AvatarImage
                                        v-if="auth.user.avatar"
                                        :src="auth.user.avatar"
                                        :alt="auth.user.name"
                                    />
                                    <AvatarFallback
                                        class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ getInitials(auth.user?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <TeamSwitcher
                        v-if="page.props.can.manageTeams"
                        :in-header="true"
                    />
                </div>
            </div>
        </div>

        <div
            v-if="props.breadcrumbs.length > 1"
            class="flex w-full border-b border-sidebar-border/70"
        >
            <div
                class="mx-auto flex h-12 w-full items-center justify-start px-4 text-neutral-500 md:max-w-7xl"
            >
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>
    </div>
</template>
