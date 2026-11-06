<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Card, RadioGroup } from '@waynestate/wayne-ui-vue';
import { computed } from 'vue';

import { useAppearance, type Appearance } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Appearance settings', href: '/settings/appearance' },
];

const { appearance, updateAppearance } = useAppearance();

const options = [
    { value: 'light', label: 'Light' },
    { value: 'dark', label: 'Dark' },
    { value: 'system', label: 'Match my device' },
];

const theme = computed({
    get: () => appearance.value,
    set: (value: Appearance) => updateAppearance(value),
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Appearance settings" />

        <SettingsLayout>
            <Card title="Appearance">
                <RadioGroup
                    id="theme"
                    v-model="theme"
                    name="theme"
                    label="Theme"
                    hint="Saved in this browser. The sun and moon button in the bar switches it too."
                    :options="options"
                />
            </Card>
        </SettingsLayout>
    </AppLayout>
</template>
