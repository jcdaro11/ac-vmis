<script setup lang="ts">
import { computed, ref, watch } from 'vue'

import { DEFAULT_AVATAR_URL, DEFAULT_TEAM_AVATAR_URL, resolvePublicMediaUrl } from '@/utils/media'

const props = withDefaults(defineProps<{
    src?: string | null
    srcUrl?: string | null
    name?: string | null
    firstName?: string | null
    lastName?: string | null
    alt?: string
    kind?: 'user' | 'team'
    sizeClass?: string
    roundedClass?: string
}>(), {
    src: null,
    srcUrl: null,
    name: null,
    firstName: null,
    lastName: null,
    alt: '',
    kind: 'user',
    sizeClass: 'h-10 w-10',
    roundedClass: 'rounded-full',
})

const imageFailed = ref(false)

const rawSource = computed(() => {
    const url = String(props.srcUrl ?? '').trim()
    const path = String(props.src ?? '').trim()

    if (url && !url.endsWith('/images/default-avatar.svg')) return url
    return path || url
})

const fallbackUrl = computed(() => (props.kind === 'team' ? DEFAULT_TEAM_AVATAR_URL : DEFAULT_AVATAR_URL))
const imageUrl = computed(() => resolvePublicMediaUrl(rawSource.value, fallbackUrl.value))
const hasUploadedImage = computed(() => rawSource.value !== '' && imageUrl.value !== fallbackUrl.value && !imageFailed.value)

const initials = computed(() => {
    const explicitName = String(props.name ?? '').trim()
    const parts = explicitName
        ? explicitName.split(/\s+/)
        : [props.firstName, props.lastName].map((part) => String(part ?? '').trim()).filter(Boolean)

    return parts
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('')
})

watch(rawSource, () => {
    imageFailed.value = false
})

function handleImageError() {
    imageFailed.value = true
}
</script>

<template>
    <span
        class="app-avatar inline-flex shrink-0 items-center justify-center overflow-hidden border border-[#cfe0f4] bg-[#edf4ff] font-bold text-[#034485]"
        :class="[sizeClass, roundedClass]"
    >
        <img
            v-if="hasUploadedImage"
            :src="imageUrl"
            :alt="alt"
            loading="lazy"
            decoding="async"
            class="h-full w-full object-cover"
            @error="handleImageError"
        />
        <span v-else-if="initials" class="leading-none">{{ initials }}</span>
        <img
            v-else
            :src="fallbackUrl"
            :alt="alt"
            loading="lazy"
            decoding="async"
            class="h-full w-full object-cover"
            @error="handleImageError"
        />
    </span>
</template>
