<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import PublicLayout from '@/components/Public/PublicLayout.vue';

const props = defineProps<{
    email: string;
    role: string;
    settingsHref: string;
    dashboardHref: string;
}>();

const page = usePage();
const isSignedIn = computed(() => Boolean(page.props.auth?.user));
</script>

<template>
    <Head title="Email Verified Successfully" />

    <PublicLayout
        title="Email Verified Successfully"
        page-title="Email Verified Successfully"
        page-description="Your email address has been verified. You may now continue using your AC-VMIS account."
    >
        <section class="mx-auto max-w-3xl py-6">
            <div class="rounded-[28px] border border-[#034485]/18 bg-white p-6 shadow-[0_24px_60px_-40px_rgba(15,23,42,0.35)] sm:p-8">
                <div class="mx-auto flex h-18 w-18 items-center justify-center rounded-full bg-[#034485]/10 text-[#034485]">
                    <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                        <path d="M20 6 9 17l-5-5" />
                    </svg>
                </div>

                <div class="mt-5 text-center">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#034485]/80">Verification complete</p>
                    <h2 class="mt-2 text-3xl font-extrabold text-slate-900">Email Verified Successfully</h2>
                    <p class="mt-3 text-sm leading-7 text-slate-600">
                        <span class="font-semibold text-slate-900">{{ props.email }}</span> has been verified. You may now continue using your AC-VMIS account.
                    </p>
                </div>

                <div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <Link
                        :href="isSignedIn ? props.dashboardHref : '/Login'"
                        class="inline-flex min-w-44 items-center justify-center rounded-full bg-[#034485] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#02356a]"
                    >
                        {{ isSignedIn ? 'Go to Dashboard' : 'Go to Sign In' }}
                    </Link>
                    <Link
                        v-if="isSignedIn"
                        :href="props.settingsHref"
                        class="inline-flex min-w-44 items-center justify-center rounded-full border border-[#034485]/20 bg-white px-5 py-2.5 text-sm font-semibold text-[#034485] transition hover:bg-[#034485]/5"
                    >
                        Return to Account Settings
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
