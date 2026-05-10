<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { reactive } from 'vue'

import { useTheme } from '@/composables/useTheme'
import StudentAthleteDashboard from '@/pages/StudentAthletes/StudentAthleteDashboard.vue'

defineOptions({ layout: StudentAthleteDashboard })

const props = defineProps<{
    student: {
        id: number
        name: string
        student_id_number: string | null
        course_or_strand: string | null
        current_grade_level: string | null
        academic_level_label: string | null
        education_level: string | null
    } | null
    academicAccess: Record<string, unknown> | null
    openPeriods: Array<{ id: number; label: string; starts_on: string | null; ends_on: string | null }>
    documentTypes: Array<{ code: string; label: string }>
    filters: { search: string; type: string }
    documents: {
        data: Array<{
            id: number
            document_type: string
            document_label: string
            document_context: string | null
            period_label: string | null
            uploaded_at: string | null
            notes: string | null
            review_status: string
            file_url: string
            download_url: string
            ocr: { run_status: string | null; mean_confidence: number | null; validation_status: string | null; validation_summary: string | null; parsed_value: number | null; value_label: string } | null
            evaluation: { status: string; remarks: string | null; review_required: boolean; scale_mismatch: boolean; gpa: number | null; evaluated_at: string | null } | null
        }>
        meta: {
            current_page: number
            last_page: number
            per_page: number
            total: number
        }
    }
}>()

const { isDarkMode } = useTheme()
const filters = reactive({ search: props.filters.search ?? '', type: props.filters.type ?? 'all' })

function statusTone(status: string | null | undefined) {
    const normalized = String(status ?? '').toLowerCase()
    if (normalized === 'reviewed') return 'border-emerald-500/35 bg-emerald-500/10 text-emerald-300'
    if (normalized === 'needs_review') return 'border-amber-500/35 bg-amber-500/10 text-amber-300'
    if (normalized === 'auto_processed') return 'border-sky-500/35 bg-sky-500/10 text-sky-300'
    return 'border-slate-500/35 bg-slate-500/10 text-slate-200'
}

function labelize(value: string | null | undefined) {
    const normalized = String(value ?? '').replace(/_/g, ' ').trim()
    if (!normalized) return 'Pending'
    return normalized.replace(/\b\w/g, (char) => char.toUpperCase())
}

function formatDateTime(value: string | null) {
    if (!value) return '-'
    return new Date(value).toLocaleString('en-PH', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    })
}

function fileNameFromUrl(url: string) {
    const clean = url.split('?')[0] || url
    const parts = clean.split('/')
    return decodeURIComponent(parts[parts.length - 1] || 'document')
}

function applyFilters(page = 1) {
    router.get('/documents/my', {
        search: filters.search || undefined,
        type: filters.type === 'all' ? undefined : filters.type,
        page,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}
</script>

<template>
    <Head title="My Documents" />

    <div class="space-y-5">
        <section class="rounded-3xl border border-[#034485] bg-[#034485] p-6 text-white">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/75">My Documents</p>
            <h1 class="mt-2 text-2xl font-bold">Submitted Documents</h1>
            <p class="mt-2 text-sm text-white/85">
                Review the documents you have already submitted, including TOR, grade reports, medical documents, and supporting files.
            </p>
        </section>

        <section
            class="rounded-3xl border p-4 shadow-sm"
            :class="isDarkMode ? 'border-slate-800 bg-slate-950 text-slate-100' : 'border-[#d6e4f4] bg-white text-slate-900'"
        >
            <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_260px]">
                <input
                    v-model="filters.search"
                    class="rounded-2xl border px-4 py-3 text-sm"
                    :class="isDarkMode ? 'border-slate-700 bg-slate-900 text-slate-100 placeholder:text-slate-500' : 'border-slate-200 bg-white text-slate-900'"
                    placeholder="Search by document type, period, or notes"
                />
                <select
                    v-model="filters.type"
                    class="rounded-2xl border px-4 py-3 text-sm"
                    :class="isDarkMode ? 'border-slate-700 bg-slate-900 text-slate-100' : 'border-slate-200 bg-white text-slate-900'"
                >
                    <option value="all">All document types</option>
                    <option v-for="type in documentTypes" :key="type.code" :value="type.code">{{ type.label }}</option>
                </select>
            </div>
            <div class="mt-3 flex justify-end">
                <button class="rounded-2xl bg-[#034485] px-4 py-2 text-sm font-semibold text-white" @click="applyFilters()">Apply Filters</button>
            </div>
        </section>

        <section class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="row in documents.data"
                :key="row.id"
                class="rounded-3xl border p-5 shadow-sm"
                :class="isDarkMode ? 'border-slate-800 bg-slate-950 text-slate-100' : 'border-[#d6e4f4] bg-white text-slate-900'"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.14em]" :class="isDarkMode ? 'text-slate-400' : 'text-slate-500'">
                            {{ row.document_label }}
                        </p>
                        <h2 class="mt-2 text-lg font-semibold">{{ fileNameFromUrl(row.download_url) }}</h2>
                        <p class="mt-1 text-sm" :class="isDarkMode ? 'text-slate-400' : 'text-slate-500'">
                            {{ row.period_label || 'Registration / General' }}
                        </p>
                    </div>
                    <span class="rounded-full border px-3 py-1 text-xs font-semibold" :class="statusTone(row.review_status)">
                        {{ labelize(row.review_status) }}
                    </span>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl border px-4 py-3" :class="isDarkMode ? 'border-slate-800 bg-slate-900' : 'border-slate-200 bg-slate-50'">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em]" :class="isDarkMode ? 'text-slate-400' : 'text-slate-500'">Upload Date</p>
                        <p class="mt-2 text-sm font-medium">{{ formatDateTime(row.uploaded_at) }}</p>
                    </div>
                    <div class="rounded-2xl border px-4 py-3" :class="isDarkMode ? 'border-slate-800 bg-slate-900' : 'border-slate-200 bg-slate-50'">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em]" :class="isDarkMode ? 'text-slate-400' : 'text-slate-500'">Review Status</p>
                        <p class="mt-2 text-sm font-medium">{{ labelize(row.review_status) }}</p>
                    </div>
                </div>

                <p v-if="row.notes" class="mt-4 text-sm" :class="isDarkMode ? 'text-slate-300' : 'text-slate-600'">
                    {{ row.notes }}
                </p>

                <div class="mt-4 flex flex-wrap gap-2">
                    <a
                        :href="row.file_url"
                        target="_blank"
                        class="rounded-2xl border border-[#034485]/25 bg-white px-4 py-2 text-sm font-semibold text-[#034485]"
                    >
                        View / Preview
                    </a>
                    <a
                        :href="row.download_url"
                        class="rounded-2xl border px-4 py-2 text-sm font-semibold"
                        :class="isDarkMode ? 'border-slate-700 bg-slate-900 text-slate-100' : 'border-slate-200 bg-slate-50 text-slate-700'"
                    >
                        Download
                    </a>
                </div>
            </article>

            <div
                v-if="documents.data.length === 0"
                class="rounded-3xl border p-8 text-center text-sm shadow-sm lg:col-span-2 xl:col-span-3"
                :class="isDarkMode ? 'border-slate-800 bg-slate-950 text-slate-400' : 'border-[#d6e4f4] bg-white text-slate-500'"
            >
                No submitted documents matched the current filters.
            </div>
        </section>

        <section v-if="documents.meta.last_page > 1" class="flex items-center justify-between gap-3 rounded-3xl border p-4 shadow-sm" :class="isDarkMode ? 'border-slate-800 bg-slate-950 text-slate-100' : 'border-[#d6e4f4] bg-white text-slate-900'">
            <p class="text-sm" :class="isDarkMode ? 'text-slate-300' : 'text-slate-600'">
                Page {{ documents.meta.current_page }} of {{ documents.meta.last_page }} • {{ documents.meta.total }} documents
            </p>
            <div class="flex gap-2">
                <button
                    type="button"
                    class="rounded-2xl border px-4 py-2 text-sm font-semibold"
                    :class="isDarkMode ? 'border-slate-700 bg-slate-900 text-slate-100 disabled:opacity-40' : 'border-slate-200 bg-slate-50 text-slate-700 disabled:opacity-40'"
                    :disabled="documents.meta.current_page <= 1"
                    @click="applyFilters(documents.meta.current_page - 1)"
                >
                    Previous
                </button>
                <button
                    type="button"
                    class="rounded-2xl border px-4 py-2 text-sm font-semibold"
                    :class="isDarkMode ? 'border-slate-700 bg-slate-900 text-slate-100 disabled:opacity-40' : 'border-slate-200 bg-slate-50 text-slate-700 disabled:opacity-40'"
                    :disabled="documents.meta.current_page >= documents.meta.last_page"
                    @click="applyFilters(documents.meta.current_page + 1)"
                >
                    Next
                </button>
            </div>
        </section>
    </div>
</template>
