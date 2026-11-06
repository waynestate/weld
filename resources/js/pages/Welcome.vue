<script setup lang="ts">
import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';
import type { SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { AppShell, Icon, SkipLink } from '@waynestate/wayne-ui-vue';
import { computed } from 'vue';

const page = usePage<SharedData>();
const signedIn = computed(() => Boolean(page.props.auth?.user));

// The same six stops as the README's journey: one request, one seam.
const stops = [
    { part: 'HELMET', does: 'sets the security headers and the CSP nonce' },
    { part: 'SESAME', does: 'signs the user in through CAS or Entra ID' },
    { part: 'WayneUI', does: "renders the page in the university's own frame" },
    { part: 'PROCTOR', does: "checks the code against CREED's policy" },
    { part: 'Preflight', does: 'runs the same checks in CI on every push' },
    { part: 'Squire', does: 'deploys it to Citadel, or anywhere Docker runs' },
];
</script>

<template>
    <Head title="Welcome" />

    <SkipLink target="main" />
    <AppShell main-id="main" :sidebar="false">
        <template #bar>
            <AppHeader />
        </template>

        <div class="weld-welcome">
            <section class="weld-welcome__hero" aria-labelledby="welcome-title">
                <p class="weld-welcome__eyebrow">The Wayne State app stack</p>
                <h1 id="welcome-title" class="weld-welcome__title">WELD</h1>
                <p class="weld-welcome__tagline">Many parts, one piece.</p>
                <span class="weld-welcome__seam" aria-hidden="true" />
                <p class="weld-welcome__lede">
                    Sign-in, security headers, checks, coding standards, CI and the WayneUI interface arrive already joined. What is left to build is
                    the part that is yours.
                </p>
                <div class="weld-welcome__actions">
                    <Link v-if="signedIn" class="wsu-btn wsu-btn--primary wsu-btn--lg" :href="route('dashboard')">Open the dashboard</Link>
                    <!-- A plain link: CAS and the gateway answer /login with a redirect to another host. -->
                    <a v-else class="wsu-btn wsu-btn--primary wsu-btn--lg" :href="route('sesame.login')">Sign in</a>
                    <a class="wsu-btn wsu-btn--outline wsu-btn--lg" href="https://weld.apps.wayne.edu/docs/">
                        Read the docs
                        <Icon name="book-open" />
                    </a>
                </div>
            </section>

            <section class="weld-welcome__journey" aria-labelledby="journey-title">
                <h2 id="journey-title" class="weld-welcome__journey-title">One request, six stops</h2>
                <ol class="weld-journey">
                    <li v-for="(stop, index) in stops" :key="stop.part" class="weld-journey__stop" :style="{ '--stop': index }">
                        <span class="weld-journey__marker" aria-hidden="true" />
                        <span class="weld-journey__part">{{ stop.part }}</span>
                        <span class="weld-journey__does">{{ stop.does }}</span>
                    </li>
                </ol>
            </section>
        </div>

        <template #footer>
            <AppFooter />
        </template>
    </AppShell>
</template>
