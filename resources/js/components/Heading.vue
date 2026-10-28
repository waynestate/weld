<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="mb-8 space-y-0.5">
        <h2 class="text-xl font-semibold tracking-tight">{{ title }}</h2>
        <p v-if="description" class="text-sm text-muted-foreground">
            {{ description }}
        </p>
    </div>
    <!-- TODO(wayneui): .wsu-divider is WayneUI's separator; its rule is drawn by ::before/::after in its stylesheet, which WELD does not load yet, so a stand-in keeps the line visible. -->
    <div class="wsu-divider heading-divider my-6" role="separator"></div>
</template>

<style scoped>
/* Stand-in for WayneUI's .wsu-divider line until its stylesheet is loaded. */
.heading-divider {
    display: flex;
    align-items: center;
}

.heading-divider::before,
.heading-divider::after {
    content: '';
    flex: 1 1 0%;
    block-size: 1px;
    background-color: hsl(var(--border));
}
</style>
