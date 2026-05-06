<script setup lang="ts">
import { router } from '@inertiajs/vue3'

type AdminBottomNavItem = {
  key: string
  label: string
  mobileLabel?: string
  route: string
  icon: 'users' | 'shield-users' | 'bar-chart-3' | 'graduation-cap'
}

defineProps<{
  items: AdminBottomNavItem[]
  isActive: (route: string) => boolean
}>()

function go(route: string) {
  router.get(route)
}

function iconPath(icon: AdminBottomNavItem['icon']) {
  if (icon === 'users') return 'M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M8.5 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8M20 8v6M23 11h-6'
  if (icon === 'shield-users') return 'M12 3l7 3v5c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6l7-3M9 12l2 2 4-4'
  if (icon === 'bar-chart-3') return 'M3 3v18h18M8 15v-4M12 15V8M16 15v-7'
  if (icon === 'graduation-cap') return 'M2 9l10-5 10 5-10 5-10-5zM6 11v5c0 1.6 2.7 3 6 3s6-1.4 6-3v-5'
  return 'M12 2v20M2 12h20'
}
</script>

<template>
  <nav class="admin-bottom-nav" aria-label="Primary">
    <button
      v-for="item in items"
      :key="item.key"
      type="button"
      class="admin-bottom-nav__item"
      :class="isActive(item.route) ? 'admin-bottom-nav__item--active' : ''"
      @click="go(item.route)"
    >
      <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path :d="iconPath(item.icon)" />
      </svg>
      <span>{{ item.mobileLabel || item.label }}</span>
    </button>
  </nav>
</template>

<style scoped>
.admin-bottom-nav {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 45;
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  border-top: 1px solid #cbd5e1;
  background: rgba(255, 255, 255, 0.97);
  backdrop-filter: blur(8px);
  padding: 0.35rem 0.5rem calc(env(safe-area-inset-bottom, 0px) + 0.35rem);
}

.admin-bottom-nav__item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.12rem;
  border-radius: 0.6rem;
  color: #64748b;
  padding: 0.35rem 0.2rem;
  font-size: 0.68rem;
  font-weight: 600;
  transition: background-color 180ms ease, color 180ms ease, transform 180ms ease;
}

.admin-bottom-nav__item--active {
  color: #ffffff;
  background: #034485;
  transform: translateY(-1px);
}

@media (min-width: 768px) {
  .admin-bottom-nav {
    display: none;
  }
}
</style>
