<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import type { NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, X } from 'lucide-vue-next';
import { inject, ref, watch } from 'vue';
import AppLogo from './AppLogo.vue';

interface WsSidebar {
    isDrawerOpen: boolean;
    openDrawer(): void;
    closeDrawer(): void;
    toggleDrawer(): void;
}

const sidebar = inject<WsSidebar | undefined>('wsu-sidebar');

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits',
        icon: BookOpen,
    },
];

const navRef = ref<HTMLElement | null>(null);

// Keep focus inside the drawer while it is open, and hand it back to the
// toggle that opened it when it closes.
watch(
    () => sidebar?.isDrawerOpen,
    (open) => {
        if (open) {
            navRef.value?.focus();
        } else {
            const active = document.activeElement;
            if (navRef.value && active && navRef.value.contains(active)) {
                document.querySelector<HTMLElement>('[aria-controls="app-sidebar"]')?.focus();
            }
        }
    },
);
</script>

<template>
    <div class="wsu-drawer-backdrop" :hidden="!sidebar?.isDrawerOpen" @click="sidebar?.closeDrawer()" />
    <nav
        ref="navRef"
        id="app-sidebar"
        class="wsu-sidebar"
        aria-label="Sections"
        tabindex="-1"
        :hidden="!sidebar?.isDrawerOpen"
    >
        <div class="wsu-sidebar__inner">
            <button
                type="button"
                class="wsu-btn wsu-btn--icon wsu-btn--ghost wsu-sidebar__close"
                @click="sidebar?.closeDrawer()"
            >
                <span class="wsu-sr-only">Close navigation menu</span>
                <X class="wsu-icon" aria-hidden="true" />
            </button>

            <div class="app-sidebar__brand">
                <Link :href="route('dashboard')" class="app-sidebar__brand-link">
                    <AppLogo />
                </Link>
            </div>

            <NavMain :items="mainNavItems" />
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </div>
    </nav>
</template>

<style scoped>
.app-sidebar__brand-link {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    color: inherit;
    text-decoration: none;
}
</style>
