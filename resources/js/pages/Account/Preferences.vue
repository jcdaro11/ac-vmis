<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

import AccountShell from '@/components/Account/AccountShell.vue'
import { useTheme } from '@/composables/useTheme'
import { normalizeWorkspaceRole, resolveAccountLayout } from '@/pages/Account/accountRole'

defineOptions({
  layout: (h: any, page: any) => h(resolveAccountLayout(page), [page]),
})

const page = usePage()
const role = computed(() => normalizeWorkspaceRole((page.props as any)?.auth?.user?.role))
const { themeMode, setTheme } = useTheme()

function cardMotion(order: number) {
  return { '--card-order': String(order) }
}
</script>

<template>
  <Head title="Preferences" />

  <AccountShell active="preferences">
      <div class="space-y-4">
      <section
        v-if="role === 'student'"
        class="account-card rounded-2xl border border-[#034485]/35 bg-[#034485] p-5 text-white"
        :style="cardMotion(1)"
      >
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/80">Student preferences</p>
        <h1 class="mt-2 text-2xl font-bold text-white">Preferences</h1>
        <p class="mt-2 text-sm leading-6 text-white/85">Customize how your student workspace looks during daily use.</p>
      </section>

      <section class="account-card rounded-2xl border border-[#034485]/40 bg-white p-5" :style="cardMotion(2)">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 class="section-title">Appearance</h2>
            <p class="settings-muted mt-1 text-sm">Choose how AC-VMIS looks while you use the system.</p>
          </div>
          <div class="grid w-full grid-cols-2 rounded-2xl border border-[#034485]/20 bg-[#034485]/5 p-1 sm:inline-flex sm:w-auto sm:rounded-full">
            <button
              type="button"
              class="w-full rounded-xl px-4 py-2 text-center text-sm font-semibold transition sm:rounded-full"
              :class="themeMode === 'light' ? 'bg-[#034485] text-white' : 'text-[#034485] hover:bg-[#034485]/10'"
              @click="setTheme('light')"
            >
              Light
            </button>
            <button
              type="button"
              class="w-full rounded-xl px-4 py-2 text-center text-sm font-semibold transition sm:rounded-full"
              :class="themeMode === 'dark' ? 'bg-[#034485] text-white' : 'text-[#034485] hover:bg-[#034485]/10'"
              @click="setTheme('dark')"
            >
              Dark
            </button>
          </div>
        </div>
      </section>
      </div>
  </AccountShell>
</template>

<style scoped>
.section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #0f172a;
  font-weight: 600;
}

.settings-muted,
.settings-label {
  color: #64748b;
}

.account-card {
  opacity: 0;
  transform: translateY(18px) scale(0.985);
  animation: account-card-rise 0.55s cubic-bezier(0.22, 1, 0.36, 1) forwards;
  animation-delay: calc(var(--card-order, 0) * 70ms);
  will-change: transform, opacity;
}

@keyframes account-card-rise {
  from {
    opacity: 0;
    transform: translateY(18px) scale(0.985);
  }

  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@media (prefers-reduced-motion: reduce) {
  .account-card {
    animation: none;
    opacity: 1;
    transform: none;
  }
}

</style>
