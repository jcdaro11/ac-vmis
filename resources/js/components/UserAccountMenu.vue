<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

import { useTheme } from '@/composables/useTheme'
import { DEFAULT_AVATAR_URL, resolveUserAvatarUrl } from '@/utils/media'

const props = withDefaults(defineProps<{
  inverse?: boolean
  menuPlacement?: 'bottom' | 'top' | 'right'
  compact?: boolean
}>(), {
  inverse: false,
  menuPlacement: 'bottom',
  compact: false,
})

const page = usePage()
const menuOpen = ref(false)
const closeTimer = ref<number | null>(null)
const { isDarkMode } = useTheme()
const avatarFailed = ref(false)

const user = computed(() => page.props.auth?.user ?? null)
const identity = computed(() => page.props.auth?.identity ?? null)
const userRole = computed(() => String(user.value?.role ?? ''))
const isStudentUser = computed(() => ['student', 'student-athlete'].includes(userRole.value))

const avatarUrl = computed(() => {
  if (avatarFailed.value) {
    return DEFAULT_AVATAR_URL
  }

  return resolveUserAvatarUrl(String(user.value?.avatar_url ?? user.value?.avatar ?? ''))
})

watch(
  () => String(user.value?.avatar_url ?? user.value?.avatar ?? ''),
  () => {
    avatarFailed.value = false
  },
)

const fullName = computed(() => String(user.value?.name ?? 'User'))
const studentStatusLabel = computed(() => {
  const subtitle = String(identity.value?.subtitle ?? '').trim()
  if (subtitle) return subtitle
  return 'Status unavailable'
})
const buttonTitle = computed(() => (isStudentUser.value ? `${fullName.value} · ${studentStatusLabel.value}` : fullName.value))
const menuPanelClass = computed(() => {
  const base = isDarkMode.value
    ? 'rounded-xl border border-slate-700 bg-[#171616] shadow-xl z-40 overflow-hidden p-2'
    : 'rounded-xl border border-[#d6e4f4] bg-[#f7fbff] shadow-xl z-40 overflow-hidden p-2'

  if (props.menuPlacement === 'top') {
    return `absolute right-0 bottom-full mb-2 w-56 ${base}`
  }
  if (props.menuPlacement === 'right') {
    return `absolute left-full -top-2 ml-2 w-56 ${base}`
  }
  return `absolute right-0 mt-2 w-56 ${base}`
})

function goProfile() {
  menuOpen.value = false
  router.get('/account/profile')
}

function goSettings() {
  menuOpen.value = false
  router.get('/account/settings')
}

function logout() {
  menuOpen.value = false
  router.post('/logout')
}

function openMenu() {
  if (closeTimer.value) {
    window.clearTimeout(closeTimer.value)
    closeTimer.value = null
  }
  menuOpen.value = true
}

function scheduleClose() {
  if (closeTimer.value) {
    window.clearTimeout(closeTimer.value)
  }
  closeTimer.value = window.setTimeout(() => {
    menuOpen.value = false
    closeTimer.value = null
  }, 180)
}

function handleAvatarError() {
  avatarFailed.value = true
}
</script>

<template>
  <div
    class="relative account-menu"
    @mouseenter="openMenu"
    @mouseleave="scheduleClose"
    @focusin="openMenu"
    @focusout="scheduleClose"
  >
    <button
      type="button"
      :class="[
        compact
          ? 'account-trigger-plain'
          : [
              'account-card',
              inverse
                ? (isDarkMode ? 'account-card-forced-dark' : 'account-card-inverse')
                : (isDarkMode ? 'account-card-forced-dark' : 'account-card-light'),
            ],
      ]"
      @click="menuOpen = !menuOpen"
      :title="compact ? buttonTitle : ''"
    >
      <img
        :src="avatarUrl"
        alt=""
        class="account-avatar h-9 w-9 rounded-full object-cover"
        @error="handleAvatarError"
      />
      <div class="account-copy min-w-0 text-left">
        <p class="account-name text-sm font-semibold truncate leading-tight">{{ fullName }}</p>
        <p v-if="isStudentUser" class="account-meta text-xs leading-tight">{{ studentStatusLabel }}</p>
      </div>
    </button>

    <div
      v-if="menuOpen"
      :class="menuPanelClass"
    >
      <button @click="goProfile" class="menu-item" :class="isDarkMode ? 'menu-item-dark' : ''">Profile</button>
      <button @click="goSettings" class="menu-item" :class="isDarkMode ? 'menu-item-dark' : ''">Settings</button>
      <button @click="logout" class="menu-item menu-item-danger" :class="isDarkMode ? 'menu-item-danger-dark' : ''">Logout</button>
    </div>
  </div>
</template>

<style scoped>
.account-trigger-plain {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  min-width: 0;
  max-width: 240px;
  padding: 0;
  border: none;
  background: transparent;
  box-shadow: none;
  color: #ffffff;
}

.account-card {
  display: flex;
  align-items: center;
  flex: 0 0 auto;
  gap: 10px;
  border-radius: 10px;
  padding: 6px 10px;
  min-width: 180px;
  max-width: 240px;
}

.account-card-light {
  border: 1px solid #cbd5e1;
  background: #ffffff;
  color: #0f172a;
}

.account-card-inverse {
  border: 1px solid #475569;
  background: #1e293b;
  color: #e2e8f0;
}

.account-card-forced-dark {
  border: 1px solid rgba(125, 211, 252, 0.18);
  background: transparent;
  color: #f8fafc;
  box-shadow: none;
}

.account-copy {
  display: flex;
  flex: 1 1 auto;
  min-width: 0;
  flex-direction: column;
  justify-content: center;
  gap: 2px;
  overflow: hidden;
}

.account-name,
.account-meta {
  margin: 0;
}

.account-meta {
  color: rgb(100 116 139);
}

.account-card-inverse .account-meta {
  color: rgb(148 163 184);
}

.account-card-forced-dark .account-name {
  color: #f8fafc;
}

.account-card-forced-dark .account-meta {
  color: #cbd5e1;
}

.menu-item {
  display: flex;
  width: 100%;
  align-items: center;
  text-align: left;
  border: 1px solid transparent;
  border-radius: 0.5rem;
  padding: 10px 12px;
  font-size: 14px;
  font-weight: 500;
  transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
}

.menu-item {
  color: #334155;
}

.menu-item:hover {
  border-color: rgba(3, 68, 133, 0.18);
  background: #ffffff;
  color: #034485;
}

.menu-item-dark {
  color: #e2e8f0;
}

.menu-item-dark:hover {
  border-color: #334155;
  background: #0f172a;
  color: #ffffff;
}

.menu-item-danger {
  border-color: #fecdd3;
  color: #e11d48;
  font-weight: 600;
}

.menu-item-danger:hover {
  background: #fff1f2;
  border-color: #fda4af;
}

.menu-item-danger-dark {
  background: rgba(127, 29, 29, 0.22);
  border-color: rgba(251, 113, 133, 0.24);
  color: #fda4af;
}

.menu-item-danger-dark:hover {
  background: rgba(127, 29, 29, 0.42);
  border-color: rgba(251, 113, 133, 0.52);
  color: #fecdd3;
}

:global(html.theme-dark) .account-card-light,
:global(html[data-theme='dark']) .account-card-light {
  border-color: rgba(71, 85, 105, 0.44) !important;
  background: linear-gradient(180deg, rgba(15, 23, 42, 0.82) 0%, rgba(17, 24, 39, 0.96) 100%) !important;
  color: #e2e8f0 !important;
  box-shadow: 0 14px 28px -22px rgba(2, 8, 23, 0.72);
}

:global(html.theme-dark) .account-card-inverse,
:global(html[data-theme='dark']) .account-card-inverse {
  border-color: rgba(71, 85, 105, 0.42) !important;
  background: linear-gradient(180deg, rgba(15, 23, 42, 0.74) 0%, rgba(17, 24, 39, 0.88) 100%) !important;
  color: #f8fafc !important;
  box-shadow: 0 14px 28px -22px rgba(20, 23, 31, 0.72);
}

:global(html.theme-dark) .account-name,
:global(html[data-theme='dark']) .account-name {
  color: #f8fafc !important;
}

:global(html.theme-dark) .account-meta,
:global(html[data-theme='dark']) .account-meta {
  color: #cbd5e1 !important;
}

:global(html.theme-dark) .menu-item,
:global(html[data-theme='dark']) .menu-item {
  color: #e2e8f0;
}

:global(html.theme-dark) .menu-item:hover,
:global(html[data-theme='dark']) .menu-item:hover {
  border-color: #334155;
  background: #0f172a;
  color: #ffffff;
}

:global(html.theme-dark) .menu-item-danger,
:global(html[data-theme='dark']) .menu-item-danger {
  border-color: rgba(251, 113, 133, 0.24);
  color: #fda4af;
}

:global(html.theme-dark) .menu-item-danger:hover,
:global(html[data-theme='dark']) .menu-item-danger:hover {
  background: rgba(127, 29, 29, 0.42);
  border-color: rgba(251, 113, 133, 0.52);
  color: #fecdd3;
}
</style>
