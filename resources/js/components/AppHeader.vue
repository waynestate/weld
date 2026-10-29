<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import UserInfo from '@/components/UserInfo.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';
import type { BreadcrumbItem, NavItem, SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, ChevronRight, Folder, LayoutGrid, Menu, Search } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItem[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage<SharedData>();
const auth = computed(() => page.props.auth);

const isCurrentRoute = (url: string) => page.url === url;

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
];

const rightNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits',
        icon: BookOpen,
    },
];

// Mobile navigation panel.
const mobileNavOpen = ref(false);
const toggleMobileNav = () => {
    mobileNavOpen.value = !mobileNavOpen.value;
};

// User menu.
const userMenuOpen = ref(false);
const userRoot = ref<HTMLElement | null>(null);
const toggleUserMenu = () => {
    userMenuOpen.value = !userMenuOpen.value;
};
const closeUserMenu = () => {
    userMenuOpen.value = false;
};

const onDocumentClick = (event: MouseEvent) => {
    if (userRoot.value && !userRoot.value.contains(event.target as Node)) {
        closeUserMenu();
    }
};

const onKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        closeUserMenu();
    }
};

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <header class="wsu-app-bar wsu-app-bar--compact">
        <div class="wsu-app-bar__inner">
            <button
                type="button"
                class="wsu-btn wsu-btn--icon app-header__nav-toggle"
                aria-label="Open navigation menu"
                aria-controls="app-header-nav"
                :aria-expanded="mobileNavOpen"
                @click="toggleMobileNav"
            >
                <Menu class="wsu-icon" aria-hidden="true" />
            </button>

            <Link :href="route('dashboard')" class="wsu-app-bar__brand">
                <AppLogo />
            </Link>

            <nav class="wsu-app-bar__nav" aria-label="Primary">
                <Link
                    v-for="item in mainNavItems"
                    :key="item.title"
                    :href="item.href"
                    class="wsu-app-bar__item"
                    :aria-current="isCurrentRoute(item.href) ? 'page' : undefined"
                >
                    <component v-if="item.icon" :is="item.icon" class="wsu-app-bar__item-icon" aria-hidden="true" />
                    {{ item.title }}
                </Link>
            </nav>

            <div class="wsu-app-bar__end">
                <button type="button" class="wsu-btn wsu-btn--icon app-header__search" aria-label="Search">
                    <Search class="wsu-icon" aria-hidden="true" />
                </button>

                <a
                    v-for="item in rightNavItems"
                    :key="item.title"
                    :href="item.href"
                    class="wsu-btn wsu-btn--icon app-header__link"
                    target="_blank"
                    rel="noopener noreferrer"
                    :aria-label="item.title"
                    :title="item.title"
                >
                    <component :is="item.icon" class="wsu-icon" aria-hidden="true" />
                </a>

                <!-- TODO(wayneui): no WayneUI menu/dropdown component (only .wsu-app-bar__user, a static label); local disclosure menu until one exists. -->
                <div ref="userRoot" class="app-header__user">
                    <button
                        type="button"
                        class="app-header__user-trigger"
                        aria-label="Account"
                        aria-controls="app-header-user-menu"
                        :aria-expanded="userMenuOpen"
                        @click="toggleUserMenu"
                    >
                        <UserInfo :user="auth.user" :show-name="false" />
                    </button>
                    <div v-if="userMenuOpen" id="app-header-user-menu" class="app-header__user-menu">
                        <UserMenuContent :user="auth.user" />
                    </div>
                </div>
            </div>
        </div>

        <!-- TODO(wayneui): WayneUI repeats primary links in the sidebar drawer (.wsu-sidebar__primary-nav); this header-only layout has no drawer, so it keeps a local panel. -->
        <div v-if="mobileNavOpen" id="app-header-nav" class="app-header__mobile-nav">
            <nav aria-label="Primary, mobile">
                <Link
                    v-for="item in mainNavItems"
                    :key="item.title"
                    :href="item.href"
                    class="app-header__mobile-link"
                    :aria-current="isCurrentRoute(item.href) ? 'page' : undefined"
                >
                    <component v-if="item.icon" :is="item.icon" class="wsu-icon" aria-hidden="true" />
                    {{ item.title }}
                </Link>
            </nav>
            <div class="app-header__mobile-footer">
                <a
                    v-for="item in rightNavItems"
                    :key="item.title"
                    :href="item.href"
                    class="app-header__mobile-link"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <component :is="item.icon" class="wsu-icon" aria-hidden="true" />
                    {{ item.title }}
                </a>
            </div>
        </div>

        <!-- TODO(wayneui): WayneUI places breadcrumbs above the page header, not inside the app bar; confirm where this trail should live. -->
        <nav v-if="props.breadcrumbs.length > 1" class="wsu-breadcrumbs app-header__breadcrumbs" aria-label="Breadcrumb">
            <ol class="wsu-breadcrumbs__list">
                <li v-for="(item, index) in props.breadcrumbs" :key="index" class="wsu-breadcrumbs__item">
                    <template v-if="index === props.breadcrumbs.length - 1">
                        <span aria-current="page">{{ item.title }}</span>
                    </template>
                    <template v-else>
                        <a :href="item.href" class="wsu-breadcrumbs__link">{{ item.title }}</a>
                        <ChevronRight class="wsu-breadcrumbs__separator wsu-icon" aria-hidden="true" />
                    </template>
                </li>
            </ol>
        </nav>
    </header>
</template>

<style scoped>
/* The nav toggle and the mobile panel only matter below the docked breakpoint,
   where the bar's own nav is hidden. */
@media (min-width: 48rem) {
    .app-header__nav-toggle,
    .app-header__mobile-nav {
        display: none;
    }
}

.app-header__search,
.app-header__link {
    color: inherit;
}

.app-header__user {
    position: relative;
}

.app-header__user-trigger {
    display: flex;
    align-items: center;
    padding: 0.25rem;
    border: none;
    border-radius: 9999px;
    background: none;
    cursor: pointer;
}

/* The user sits at the top of the bar, so the menu opens downward. */
.app-header__user-menu {
    position: absolute;
    top: calc(100% + 0.25rem);
    inset-inline-end: 0;
    z-index: 50;
    min-width: 14rem;
    background-color: hsl(var(--popover));
    border: 1px solid hsl(var(--border));
    border-radius: 0.5rem;
    box-shadow:
        0 10px 15px -3px rgb(0 0 0 / 0.1),
        0 4px 6px -4px rgb(0 0 0 / 0.1);
}

.app-header__mobile-nav {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 1rem;
    border-block-end: 1px solid hsl(var(--border));
}

.app-header__mobile-nav nav,
.app-header__mobile-footer {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.app-header__mobile-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: inherit;
    text-decoration: none;
}

.app-header__mobile-link:hover {
    background-color: hsl(var(--accent));
}

.app-header__mobile-link[aria-current='page'] {
    background-color: hsl(var(--accent));
    font-weight: 600;
}

.app-header__mobile-link .wsu-icon {
    inline-size: 1.25rem;
    block-size: 1.25rem;
}

.app-header__breadcrumbs {
    margin-block-end: 0;
    padding-inline: 1rem;
    padding-block: 0.5rem;
    border-block-end: 1px solid hsl(var(--border));
}
</style>
