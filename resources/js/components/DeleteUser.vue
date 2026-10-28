<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { X } from 'lucide-vue-next';

// Components
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';

const dialogRef = ref<HTMLDialogElement | null>(null);
const passwordInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    password: '',
});

const openDialog = () => {
    dialogRef.value?.showModal();
};

const deleteUser = (e: Event) => {
    e.preventDefault();

    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    form.clearErrors();
    form.reset();
    dialogRef.value?.close();
};
</script>

<template>
    <div class="space-y-6">
        <HeadingSmall title="Delete account" description="Delete your account and all of its resources" />
        <div class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">Warning</p>
                <p class="text-sm">Please proceed with caution, this cannot be undone.</p>
            </div>
            <button type="button" class="wsu-btn wsu-btn--danger" @click="openDialog">Delete account</button>

            <!-- TODO(wayneui): .wsu-dialog is WayneUI's dialog (a native <dialog>); WELD does not load its stylesheet yet, so stand-ins keep it centred and themed for light and dark mode. -->
            <dialog ref="dialogRef" class="wsu-dialog" @click.self="closeModal">
                <form class="space-y-6" @submit="deleteUser">
                    <div class="wsu-dialog__header">
                        <h2>Are you sure you want to delete your account?</h2>
                        <button type="button" class="wsu-btn wsu-btn--icon" aria-label="Close" @click="closeModal">
                            <X class="wsu-icon" />
                        </button>
                    </div>

                    <p class="wsu-prose">
                        Once your account is deleted, all of its resources and data will also be permanently deleted. Please enter your
                        password to confirm you would like to permanently delete your account.
                    </p>

                    <div class="wsu-field">
                        <label class="sr-only" for="password">Password</label>
                        <input
                            class="wsu-input"
                            id="password"
                            type="password"
                            name="password"
                            ref="passwordInput"
                            v-model="form.password"
                            placeholder="Password"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="wsu-dialog__footer">
                        <button type="button" class="wsu-btn wsu-btn--ghost" @click="closeModal">Cancel</button>
                        <button type="submit" class="wsu-btn wsu-btn--danger" :disabled="form.processing">Delete account</button>
                    </div>
                </form>
            </dialog>
        </div>
    </div>
</template>

<style scoped>
/* Stand-in for WayneUI's .wsu-dialog card until its stylesheet is loaded: a centred,
   themed box that works in light and dark mode. */
.wsu-dialog {
    position: fixed;
    top: 50%;
    left: 50%;
    right: auto;
    bottom: auto;
    transform: translate(-50%, -50%);
    width: min(32rem, calc(100vw - 2rem));
    max-height: calc(100vh - 4rem);
    overflow-y: auto;
    padding: 1.5rem;
    color: hsl(var(--foreground));
    background-color: hsl(var(--background));
    border: 1px solid hsl(var(--border));
    border-radius: 0.5rem;
    box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);
}

/* Stand-in for WayneUI's .wsu-dialog__header layout until its stylesheet is loaded. */
.wsu-dialog__header {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 1rem;
}

/* Stand-in for WayneUI's .wsu-dialog__footer layout until its stylesheet is loaded. */
.wsu-dialog__footer {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: flex-end;
    margin-top: 1.5rem;
}

/* Stand-in for WayneUI's .wsu-field layout until its stylesheet is loaded: a vertical
   stack of label, control, and message. */
.wsu-field {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

/* Stand-in for WayneUI's .wsu-input width until its stylesheet is loaded. */
.wsu-input {
    width: 100%;
}
</style>
