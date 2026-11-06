<script setup lang="ts">
import { type SharedData, type User } from '@/types';
import { useForm, usePage } from '@inertiajs/vue3';
import { Alert, Button, Card, Dialog, Input } from '@waynestate/wayne-ui-vue';
import { nextTick, ref } from 'vue';

const page = usePage<SharedData>();
const user = page.props.auth.user as User;

const open = ref(false);

const form = useForm({
    confirmation: '',
});

const focusConfirmation = () => document.getElementById('confirmation')?.focus();

// WayneUI's dialog is a native <dialog> opened with showModal(): the browser
// traps focus inside it, Escape closes it, and focus goes back to the button
// that opened it. Focus starts on the field, the one thing to do in there.
const openDialog = async () => {
    open.value = true;
    await nextTick();
    focusConfirmation();
};

const reset = () => {
    form.clearErrors();
    form.reset();
};

const closeDialog = () => {
    open.value = false;
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeDialog(),
        onError: () => focusConfirmation(),
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <Card title="Delete account">
        <div class="weld-form">
            <Alert variant="warning" title="This cannot be undone">
                <p>Deleting your account removes it and everything attached to it, then signs you out.</p>
            </Alert>
            <div>
                <Button variant="danger" @click="openDialog">Delete account</Button>
            </div>
        </div>

        <Dialog v-model:open="open" title="Delete your account?" close-label="Cancel and close" @close="reset">
            <form id="delete-user-form" class="weld-form" @submit.prevent="deleteUser">
                <p>
                    Your account and all of its data will be permanently deleted. To confirm, type the email address on the account:
                    <strong>{{ user.email }}</strong>
                </p>
                <Input
                    id="confirmation"
                    v-model="form.confirmation"
                    name="confirmation"
                    label="Email address"
                    autocomplete="off"
                    required
                    :error="form.errors.confirmation"
                />
            </form>

            <template #footer>
                <Button variant="ghost" @click="closeDialog">Cancel</Button>
                <Button variant="danger" type="submit" form="delete-user-form" :loading="form.processing" :disabled="form.processing">
                    Delete account
                </Button>
            </template>
        </Dialog>
    </Card>
</template>
