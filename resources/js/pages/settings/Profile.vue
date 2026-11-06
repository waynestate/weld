<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Button, Card, Input } from '@waynestate/wayne-ui-vue';

import DeleteUser from '@/components/DeleteUser.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type SharedData, type User } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Profile settings', href: '/settings/profile' },
];

const page = usePage<SharedData>();
const user = page.props.auth.user as User;

const form = useForm({
    name: user.name,
    email: user.email,
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Profile settings" />

        <SettingsLayout>
            <Card title="Profile information">
                <form class="weld-form" @submit.prevent="submit">
                    <p class="weld-muted">Update your name and email address.</p>

                    <!-- WayneUI fields: the label, hint, error and aria-describedby come wired together. -->
                    <Input id="name" v-model="form.name" name="name" label="Name" required autocomplete="name" :error="form.errors.name" />
                    <Input
                        id="email"
                        v-model="form.email"
                        name="email"
                        type="email"
                        label="Email address"
                        required
                        autocomplete="username"
                        :error="form.errors.email"
                    />

                    <div class="weld-form__actions">
                        <Button variant="primary" type="submit" :loading="form.processing" :disabled="form.processing">Save</Button>
                        <p class="weld-muted" role="status">
                            <Transition name="weld-fade">
                                <span v-if="form.recentlySuccessful">Saved.</span>
                            </Transition>
                        </p>
                    </div>
                </form>
            </Card>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
