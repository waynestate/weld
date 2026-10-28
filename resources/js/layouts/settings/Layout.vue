<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: '/settings/profile',
    },
    {
        title: 'Appearance',
        href: '/settings/appearance',
    },
];

const currentPath = window.location.pathname;
</script>

<template>
    <div class="px-4 py-6">
        <Heading title="Settings" description="Manage your profile and account settings" />

        <div class="flex flex-col space-y-8 md:space-y-0 lg:flex-row lg:space-x-12 lg:space-y-0">
            <aside class="w-full max-w-xl lg:w-48">
                <nav class="flex flex-col space-y-1" aria-label="Settings">
                    <!-- TODO(wayneui): .wsu-btn--ghost is WayneUI's ghost button; WELD does not load its stylesheet yet, so a stand-in keeps the link full-width, left-aligned, and marked when current. -->
                    <Link
                        v-for="item in sidebarNavItems"
                        :key="item.href"
                        :href="item.href"
                        class="wsu-btn wsu-btn--ghost settings-nav__link"
                        :class="{ 'settings-nav__link--active': currentPath === item.href }"
                        :aria-current="currentPath === item.href ? 'page' : undefined"
                    >
                        {{ item.title }}
                    </Link>
                </nav>
            </aside>

            <!-- TODO(wayneui): .wsu-divider is WayneUI's separator; a stand-in keeps the line visible until its stylesheet loads. -->
            <div class="wsu-divider settings-divider my-6 md:hidden" role="separator"></div>

            <div class="flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Stand-in for WayneUI's ghost nav link until its stylesheet is loaded: a full-width,
   left-aligned link that marks the current page. */
.settings-nav__link {
    width: 100%;
    justify-content: flex-start;
    text-decoration: none;
}

.settings-nav__link--active {
    background-color: hsl(var(--muted));
}

/* Stand-in for WayneUI's .wsu-divider line until its stylesheet is loaded. */
.settings-divider {
    display: flex;
    align-items: center;
}

.settings-divider::before,
.settings-divider::after {
    content: '';
    flex: 1 1 0%;
    block-size: 1px;
    background-color: hsl(var(--border));
}
</style>
