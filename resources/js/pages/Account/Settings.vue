<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

import AccountShell from '@/components/Account/AccountShell.vue'
import { useTheme } from '@/composables/useTheme'
import { normalizeWorkspaceRole, resolveAccountLayout } from '@/pages/Account/accountRole'

defineOptions({
  layout: (h: any, page: any) => h(resolveAccountLayout(page), [page]),
})

const page = usePage()
const role = computed(() => normalizeWorkspaceRole((page.props as any)?.auth?.user?.role))
const { isDarkMode } = useTheme()

const quickLinks = computed(() => {
  if (role.value === 'coach') {
    return [
      { title: 'Update profile details', description: 'Keep your contact information and coaching identity current.', href: '/account/profile', cta: 'Open Profile' },
      { title: 'Review alert preferences', description: 'Control how schedule, attendance, and roster updates reach you.', href: '/account/notifications', cta: 'Manage Alerts' },
      { title: 'Get workflow help', description: 'Open role-specific guidance for attendance, roster, and support concerns.', href: '/account/help', cta: 'Open Help' },
    ]
  }

  if (role.value === 'admin') {
    return [
      { title: 'Check account security', description: 'Update password and email details used for administrative access.', href: '/account/account-settings', cta: 'Open Account' },
      { title: 'Tune notification rules', description: 'Choose which operational, academic, and approval alerts you want.', href: '/account/notifications', cta: 'Manage Alerts' },
      { title: 'Review support guidance', description: 'Open role-specific help for approvals, teams, and official records.', href: '/account/help', cta: 'Open Help' },
    ]
  }

  return [
    { title: 'Complete your profile', description: 'Keep your personal and emergency information updated and accurate.', href: '/account/profile', cta: 'Open Profile' },
    { title: 'Manage notifications', description: 'Choose how academic, schedule, and status updates reach you.', href: '/account/notifications', cta: 'Manage Alerts' },
    { title: 'Find support faster', description: 'Open practical help for approval, attendance, and document concerns.', href: '/account/help', cta: 'Open Help' },
  ]
})
</script>

<template>
  <Head title="Settings" />

  <AccountShell active="settings">
    <div class="space-y-6">
      <section
        class="settings-hero rounded-3xl p-6 text-white"
        :class="isDarkMode
          ? 'border border-[#034485]/35 bg-[#034485] shadow-[0_24px_50px_-38px_rgba(3,68,133,0.38)]'
          : 'border border-[#034485]/30 bg-[#034485] shadow-[0_24px_50px_-38px_rgba(3,68,133,0.38)]'"
      >
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/80">Settings overview</p>
        <h2 class="mt-2 text-2xl font-bold text-white">Account center</h2>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-white/85">
          Use the left sidebar to move through profile details, account access, notifications, preferences, and help without bouncing across oversized buttons.
        </p>
      </section>

      <section class="grid gap-4 lg:grid-cols-3">
        <article
          v-for="item in quickLinks"
          :key="item.href"
          class="settings-card rounded-2xl p-5 transition"
          :class="isDarkMode
            ? 'border border-[#034485]/35 bg-[#034485] shadow-[0_24px_50px_-38px_rgba(3,68,133,0.38)] hover:bg-[#023a72]'
            : 'border border-[#034485]/30 bg-[#034485] shadow-[0_18px_40px_-34px_rgba(3,68,133,0.38)] hover:bg-[#023a72]'"
        >
          <p class="settings-card__title text-sm font-semibold" :class="isDarkMode ? 'text-slate-50' : 'text-white'">{{ item.title }}</p>
          <p class="settings-card__copy mt-2 text-sm leading-6" :class="isDarkMode ? 'text-slate-300' : 'text-white/85'">{{ item.description }}</p>
          <Link
            :href="item.href"
            class="settings-card__cta mt-4 inline-flex rounded-full px-3 py-2 text-xs font-bold transition"
            :class="isDarkMode
              ? 'bg-sky-400/15 text-sky-300 hover:bg-sky-400/25'
              : 'bg-white text-[#034485] hover:bg-slate-100'"
          >
            {{ item.cta }}
          </Link>
        </article>
      </section>
    </div>
  </AccountShell>
</template>

<style scoped>
:global(html.theme-dark) .settings-hero,
:global(html[data-theme='dark']) .settings-hero {
  border-color: rgba(3, 68, 133, 0.35) !important;
  background: #034485 !important;
  box-shadow: 0 24px 50px -38px rgba(3, 68, 133, 0.38) !important;
}

:global(html.theme-dark) .settings-card,
:global(html[data-theme='dark']) .settings-card {
  border-color: rgba(3, 68, 133, 0.35) !important;
  background: #034485 !important;
  box-shadow: 0 24px 50px -38px rgba(3, 68, 133, 0.38) !important;
}

:global(html.theme-dark) .settings-card:hover,
:global(html[data-theme='dark']) .settings-card:hover {
  border-color: rgba(3, 68, 133, 0.42) !important;
  background: #023a72 !important;
}

:global(html.theme-dark) .settings-card__title,
:global(html[data-theme='dark']) .settings-card__title {
  color: #f8fafc !important;
}

:global(html.theme-dark) .settings-card__copy,
:global(html[data-theme='dark']) .settings-card__copy {
  color: #cbd5e1 !important;
}

:global(html.theme-dark) .settings-card__cta,
:global(html[data-theme='dark']) .settings-card__cta {
  background: rgba(56, 189, 248, 0.14) !important;
  color: #7dd3fc !important;
}

:global(html.theme-dark) .settings-card__cta:hover,
:global(html[data-theme='dark']) .settings-card__cta:hover {
  background: rgba(56, 189, 248, 0.22) !important;
}
</style>
