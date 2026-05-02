<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { computed } from 'vue'

import { useTheme } from '@/composables/useTheme'

const props = defineProps<{
  active: 'profile' | 'settings' | 'account' | 'notifications' | 'preferences' | 'help'
}>()

const { isDarkMode } = useTheme()

const items = computed(() => [
  { key: 'profile', label: 'Profile', href: '/account/profile', icon: 'user' },
  { key: 'account', label: 'Account', href: '/account/account-settings', icon: 'lock' },
  { key: 'notifications', label: 'Alerts', href: '/account/notifications', icon: 'bell' },
  { key: 'preferences', label: 'Preferences', href: '/account/preferences', icon: 'sliders' },
  { key: 'help', label: 'Help', href: '/account/help', icon: 'help' },
])

function goTo(href: string) {
  router.get(href)
}
</script>

<template>
  <nav
    aria-label="Account settings navigation"
    class="settings-nav-panel"
    :class="isDarkMode ? 'settings-nav-panel--dark' : 'settings-nav-panel--light'"
  >
    <div
      class="settings-nav-panel__hero"
      :class="isDarkMode ? 'settings-nav-panel__hero--dark' : 'settings-nav-panel__hero--light'"
    >
      <p class="settings-nav-panel__kicker">Account Center</p>
      <h2 class="settings-nav-panel__title">Settings</h2>
    </div>

    <div class="settings-nav-panel__list">
      <button
        v-for="item in items"
        :key="item.key"
        type="button"
        class="settings-nav-item"
        :class="[
          isDarkMode ? 'settings-nav-item--dark' : 'settings-nav-item--light',
          props.active === item.key ? 'settings-nav-item--active' : '',
        ]"
        @click="goTo(item.href)"
      >
        <span
          class="settings-nav-item__icon"
          :class="props.active === item.key ? 'settings-nav-item__icon--active' : ''"
        >
          <svg v-if="item.icon === 'user'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
            <path d="M20 21a8 8 0 0 0-16 0" />
            <circle cx="12" cy="8" r="4" />
          </svg>
          <svg v-else-if="item.icon === 'lock'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
            <rect x="4" y="11" width="16" height="9" rx="2" />
            <path d="M8 11V8a4 4 0 1 1 8 0v3" />
          </svg>
          <svg v-else-if="item.icon === 'bell'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
            <path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9" />
            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
          </svg>
          <svg v-else-if="item.icon === 'sliders'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
            <path d="M4 21v-7" />
            <path d="M4 10V3" />
            <path d="M12 21v-9" />
            <path d="M12 8V3" />
            <path d="M20 21v-4" />
            <path d="M20 13V3" />
            <path d="M2 14h4" />
            <path d="M10 8h4" />
            <path d="M18 17h4" />
          </svg>
          <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
            <circle cx="12" cy="12" r="10" />
            <path d="M9.09 9a3 3 0 1 1 5.82 1c-.6.84-1.83 1.31-2.38 2.1-.22.31-.32.66-.32 1.4" />
            <path d="M12 17h.01" />
          </svg>
        </span>
        <span class="settings-nav-item__label">{{ item.label }}</span>
      </button>
    </div>
  </nav>
</template>

<style scoped>
.settings-nav-panel {
  border-radius: 1.75rem;
  padding: 1.05rem;
  transition: background 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

.settings-nav-panel--light {
  border: 1px solid rgba(3, 68, 133, 0.16);
  background: #ffffff;
  box-shadow: 0 24px 50px -38px rgba(15, 23, 42, 0.45);
}

.settings-nav-panel--dark {
  border: 1px solid rgba(51, 65, 85, 0.9);
  background: #131314;
  box-shadow: 0 26px 60px -42px rgba(2, 8, 23, 0.9);
}

.settings-nav-panel__hero {
  margin-bottom: 0.95rem;
  border-radius: 1.3rem;
  padding: 1rem 1rem 0.95rem;
}

.settings-nav-panel__hero--light {
  background: #1164b6;
  box-shadow: 0 20px 36px -30px rgba(0, 8, 15, 0.48);
}

.settings-nav-panel__hero--dark {
  background: #0f172a;
  box-shadow: 0 22px 40px -30px rgba(2, 8, 23, 0.72);
}

.settings-nav-panel__kicker {
  margin: 0;
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.76);
}

.settings-nav-panel__title {
  margin: 0.3rem 0 0;
  font-size: 1.05rem;
  font-weight: 700;
  line-height: 1.3;
  color: #ffffff;
}

.settings-nav-panel__list {
  display: grid;
  gap: 0.65rem;
}

.settings-nav-item {
  display: flex;
  width: 100%;
  align-items: center;
  gap: 0.85rem;
  border-radius: 1.15rem;
  padding: 0.85rem 0.95rem;
  text-align: left;
  backdrop-filter: blur(8px);
  transition: transform 0.18s ease, background 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease, color 0.18s ease;
}

.settings-nav-item--light {
  border: 1px solid rgba(3, 68, 133, 0.14);
  background: #f8fbff;
  color: #334155;
}

.settings-nav-item--light:hover {
  transform: translateY(-1px);
  border-color: rgba(3, 68, 133, 0.2);
  background: #ffffff;
  box-shadow: 0 14px 24px -22px rgba(15, 23, 42, 0.28);
}

.settings-nav-item--dark {
  border: 1px solid rgba(51, 65, 85, 0.9);
  background: #06080c;
  color: #e5e7eb;
}

.settings-nav-item--dark:hover {
  transform: translateY(-1px);
  border-color: rgba(56, 189, 248, 0.32);
  background: #1e293b;
  box-shadow: 0 18px 32px -24px rgba(2, 8, 23, 0.88);
}

.settings-nav-item--active {
  border-color: #034485 !important;
  background: #034485 !important;
  color: #ffffff !important;
  box-shadow: 0 22px 38px -26px rgba(14, 116, 144, 0.55);
}

.settings-nav-item__icon {
  display: inline-flex;
  width: 2.5rem;
  height: 2.5rem;
  align-items: center;
  justify-content: center;
  border-radius: 0.95rem;
  flex-shrink: 0;
  background: rgba(3, 68, 133, 0.1);
  color: #034485;
}

.settings-nav-panel--dark .settings-nav-item__icon {
  background: #1f2937;
  color: #93c5fd;
}

.settings-nav-item__icon--active {
  background: rgba(255, 255, 255, 0.16) !important;
  color: #ffffff !important;
}

.settings-nav-item__icon svg {
  width: 1.1rem;
  height: 1.1rem;
}

.settings-nav-item__label {
  font-size: 0.98rem;
  font-weight: 700;
  line-height: 1.35;
}
</style>
