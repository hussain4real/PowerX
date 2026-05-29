<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Award,
    Banknote,
    BookOpen,
    BriefcaseBusiness,
    CalendarClock,
    FolderGit2,
    GraduationCap,
    LayoutGrid,
    Settings,
    ShieldCheck,
    UsersRound,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import TeamSwitcher from '@/components/TeamSwitcher.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { portal as corporatePortal } from '@/routes/corporate';
import { index as coursesIndex } from '@/routes/courses';
import { portal as instructorPortal } from '@/routes/instructor';
import { portal as studentPortal } from '@/routes/student';
import { index as teamsIndex } from '@/routes/teams';
import type { NavItem } from '@/types';

const page = usePage();

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

const corporatePortalUrl = computed(() =>
    page.props.currentTeam
        ? corporatePortal(page.props.currentTeam.slug).url
        : '/',
);

const studentPortalSectionUrl = (section: string): string =>
    `${studentPortalUrl.value}${section}`;

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

function visitDefaultWorkspaceWithPageReload(): void {
    window.location.assign(defaultWorkspaceUrl.value);
}

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
            href: studentPortalSectionUrl('#student-enrollments'),
            icon: BookOpen,
        });
        items.push({
            title: 'Schedule',
            href: studentPortalSectionUrl('#student-schedule'),
            icon: CalendarClock,
        });
        items.push({
            title: 'Exams',
            href: studentPortalSectionUrl('#student-exams'),
            icon: GraduationCap,
        });
        items.push({
            title: 'Certificates',
            href: studentPortalSectionUrl('#student-certificates'),
            icon: Award,
        });
        items.push({
            title: 'Payments',
            href: studentPortalSectionUrl('#student-payments'),
            icon: Banknote,
        });
        items.push({
            title: 'Browse courses',
            href: coursesIndex().url,
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

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <a
                            v-if="defaultWorkspaceReloadsPage"
                            :href="defaultWorkspaceUrl"
                            @click.prevent.stop="
                                visitDefaultWorkspaceWithPageReload()
                            "
                        >
                            <AppLogo />
                        </a>
                        <Link v-else :href="defaultWorkspaceUrl">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <SidebarMenu v-if="page.props.can.manageTeams">
                <SidebarMenuItem>
                    <TeamSwitcher />
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
