<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { House, Compass, LayoutDashboard } from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { index } from '@/routes/classrooms';
import type { NavItem } from '@/types';

const page = usePage();
const userRole = computed(() => page.props.auth.user.role);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];

    if (userRole.value === 'administrator') {
        items.push({
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutDashboard,
        });
    }

    items.push({
        title: userRole.value === 'educator' ? 'Manajemen Kelas' : 'Kelas Saya',
        href: index(),
        icon: House,
    });

    if (userRole.value === 'student') {
        items.push({
            title: 'Cari Kelas',
            href: '/discovery',
            icon: Compass,
        });
    }

    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="index()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
