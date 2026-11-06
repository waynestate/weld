<script setup lang="ts">
import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';
import { mainNavItems, toCrumbs, toWayneUi } from '@/navigation';
import type { BreadcrumbItemType } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { AppShell, Breadcrumbs, Icon, Sidebar, SkipLink } from '@waynestate/wayne-ui-vue';
import { computed } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const crumbs = computed(() => toCrumbs(props.breadcrumbs));
const primary = computed(() => toWayneUi(mainNavItems, page.url));
</script>

<template>
    <SkipLink target="main" />
    <!-- The header-only shell: the links live in the app bar, centered content, no docked sidebar. -->
    <AppShell main-id="main" :sidebar="false" class="weld-header-layout">
        <template #bar>
            <AppHeader :nav="mainNavItems" drawer="app-header-nav" />
        </template>

        <!--
            WayneUI gap: the bar hides its links below 48rem and only a sidebar
            drawer brings them back, and there is no header-only variant. So this
            layout carries a drawer with just the primary links, and app.css hides
            it once the bar shows them again.
        -->
        <template #sidebar>
            <Sidebar id="app-header-nav" label="Menu">
                <template #primary-nav>
                    <li v-for="item in primary" :key="item.href">
                        <Link class="wsu-sidebar__link" :href="item.href" :aria-current="item.current ? 'page' : undefined">
                            <Icon v-if="item.icon" :name="item.icon" />
                            {{ item.label }}
                        </Link>
                    </li>
                </template>
            </Sidebar>
        </template>

        <Breadcrumbs v-if="crumbs.length > 1" :items="crumbs" :link-as="Link" />
        <slot />

        <template #footer>
            <AppFooter />
        </template>
    </AppShell>
</template>
