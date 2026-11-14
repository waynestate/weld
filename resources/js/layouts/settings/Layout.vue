<script setup lang="ts">
import { pathOf, settingsNavItems } from '@/navigation';
// Imported as InertiaLink, not Link: the accessibility rules read a bare <Link>
// as HTML's <link> element, which may not carry aria-current. It renders an <a>.
import { Link as InertiaLink, usePage } from '@inertiajs/vue3';
import { Icon, PageHeader } from '@waynestate/wayne-ui-vue';
import { computed } from 'vue';

// The pages already import it as SettingsLayout; now the component says so too,
// so its name is two words like every other component's.
defineOptions({ name: 'SettingsLayout' });

const page = usePage();
const currentPath = computed(() => pathOf(page.url));
</script>

<template>
    <PageHeader title="Settings" subtitle="Manage your profile and how WELD looks for you." />

    <div class="weld-settings">
        <nav class="weld-settings__nav" aria-label="Settings">
            <!-- WayneUI's sidebar link: the same filled pill marks the current page here as in the sidebar. -->
            <ul class="wsu-sidebar__list">
                <li v-for="item in settingsNavItems" :key="item.href">
                    <InertiaLink
                        :href="item.href"
                        class="wsu-sidebar__link"
                        :aria-current="currentPath === item.href ? 'page' : undefined"
                    >
                        <Icon v-if="item.icon" :name="item.icon" />
                        {{ item.title }}
                    </InertiaLink>
                </li>
            </ul>
        </nav>

        <div class="weld-settings__content">
            <slot />
        </div>
    </div>
</template>
