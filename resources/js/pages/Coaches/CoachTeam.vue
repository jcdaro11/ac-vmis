<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

import ConfirmDialog from '@/components/ui/dialog/ConfirmDialog.vue'
import { showAppToast } from '@/composables/useAppToast'
import { useSportColors } from '@/composables/useSportColors'
import CoachDashboard from '@/pages/Coaches/CoachDashboard.vue'
import { resolveTeamAvatarUrl as teamAvatarUrl, resolveUserAvatarUrl as userAvatarUrl } from '@/utils/media'

defineOptions({
    layout: CoachDashboard,
})

type PlayerStatus = 'active' | 'injured' | 'suspended' | 'inactive'

type PlayerRow = {
    id: number
    jersey_number: string | number | null
    athlete_position: string | null
    player_status?: PlayerStatus | null
    latest_injury_notes?: string | null
    has_unresolved_injury?: boolean
    student?: {
        first_name?: string
        last_name?: string
        student_id_number?: string
        course_or_strand?: string | null
        current_grade_level?: string | null
        academic_level_label?: string | null
        phone_number?: string | null
        gender?: string | null
        height?: string | null
        weight?: string | null
        emergency_contact_name?: string | null
        emergency_contact_relationship?: string | null
        emergency_contact_phone?: string | null
        user?: {
            email?: string | null
            avatar?: string | null
        } | null
    }
}

const props = defineProps<{
    team: any | null
    teams: Array<{ id: number; team_name: string; sport: string }>
    selectedTeamId: number | null
    currentUserId: number | null
}>()

const positionDrafts = ref<Record<number, string>>({})
const positionSaveState = ref<Record<number, 'idle' | 'saving' | 'saved' | 'error'>>({})

const requestDialogOpen = ref(false)
const requestNotes = ref('')
const requestSubmitting = ref(false)
const detailsOpen = ref(false)
const selectedPlayer = ref<PlayerRow | null>(null)
const copiedField = ref<string | null>(null)
const clearingInjuryPlayerId = ref<number | null>(null)

const selectedTeamId = ref<number | null>(props.selectedTeamId ?? null)
const { sportColor, sportTextColor } = useSportColors()

const players = computed<PlayerRow[]>(() => props.team?.players ?? [])
const selectedStudent = computed(() => selectedPlayer.value?.student ?? null)

watch(
    () => props.team?.players,
    (list: PlayerRow[] | undefined) => {
        if (!list?.length) return
        const nextPositions: Record<number, string> = {}
        for (const player of list) {
            nextPositions[player.id] = player.athlete_position ?? ''
        }
        positionDrafts.value = nextPositions
    },
    { immediate: true }
)

watch(
    () => props.selectedTeamId,
    (val) => {
        selectedTeamId.value = val ?? null
    }
)

const sportPositionMap: Record<string, string[]> = {
    basketball: ['Point Guard', 'Shooting Guard', 'Small Forward', 'Power Forward', 'Center'],
    soccer: [
        'Goalkeeper',
        'Right Back',
        'Left Back',
        'Center Back',
        'Wing Back',
        'Defensive Midfielder',
        'Central Midfielder',
        'Attacking Midfielder',
        'Winger',
        'Striker',
    ],
    volleyball: ['Setter', 'Outside Hitter', 'Opposite Hitter', 'Middle Blocker', 'Libero', 'Defensive Specialist'],
}

function positionsForSport(): string[] {
    const sportName = String(props.team?.sport?.name ?? '')
        .trim()
        .toLowerCase()

    return sportPositionMap[sportName] ?? []
}

const filteredPlayers = computed(() => {
    return players.value
})

function initialsFromParts(...parts: Array<string | null | undefined>) {
    return parts
        .flatMap((part) => String(part ?? '').trim().split(/\s+/))
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('') || 'NA'
}

function coachAvatarUrl(coach?: { user?: { avatar?: string | null } | null } | null) {
    return userAvatarUrl(coach?.user?.avatar ?? null)
}

function isCurrentCoach(coach?: { user_id?: number | null; user?: { id?: number | null } | null } | null) {
    const coachUserId = coach?.user_id ?? coach?.user?.id ?? null
    return coachUserId !== null && props.currentUserId !== null && coachUserId === props.currentUserId
}

function statusTone(status: PlayerStatus) {
    if (status === 'inactive') return 'bg-slate-200 text-slate-700'
    if (status === 'injured') return 'bg-rose-600 text-white'
    if (status === 'suspended') return 'bg-red-100 text-red-700'
    return 'bg-emerald-100 text-emerald-700'
}

function setSaveState(
    stateRef: typeof positionSaveState,
    playerId: number,
    state: 'idle' | 'saving' | 'saved' | 'error'
) {
    stateRef.value = { ...stateRef.value, [playerId]: state }
    if (state === 'saved' || state === 'error') {
        window.setTimeout(() => {
            stateRef.value = { ...stateRef.value, [playerId]: 'idle' }
        }, 2000)
    }
}

function savePosition(teamPlayerId: number) {
    setSaveState(positionSaveState, teamPlayerId, 'saving')
    router.put(
        `/coach/team-players/${teamPlayerId}/position`,
        { athlete_position: positionDrafts.value[teamPlayerId] ?? '' },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => setSaveState(positionSaveState, teamPlayerId, 'saved'),
            onError: () => setSaveState(positionSaveState, teamPlayerId, 'error'),
        },
    )
}

function openRequest() {
    requestNotes.value = ''
    requestDialogOpen.value = true
}

function openDetails(player: PlayerRow) {
    selectedPlayer.value = player
    detailsOpen.value = true
}

function closeDetails() {
    detailsOpen.value = false
}

function formatSimple(value?: string | number | null) {
    if (value === null || value === undefined) return '-'
    const text = String(value).trim()
    return text === '' ? '-' : text
}

function formatMeasure(value?: string | number | null, unit?: string) {
    const text = formatSimple(value)
    if (text === '-') return text
    if (!unit) return text
    if (/[a-zA-Z]/.test(text)) return text
    return `${text} ${unit}`
}

async function copyToClipboard(value?: string | number | null, label?: string) {
    const text = formatSimple(value)
    if (text === '-') return
    try {
        await navigator.clipboard.writeText(text)
        copiedField.value = label ?? text
        window.setTimeout(() => {
            if (copiedField.value === (label ?? text)) copiedField.value = null
        }, 1200)
    } catch {
        // silent fail
    }
}

function submitRequest() {
    router.post(
        '/coach/team/requests',
        {
            type: 'team_change',
            notes: requestNotes.value,
            team_id: selectedTeamId.value ?? undefined,
        },
        {
            preserveScroll: true,
            onStart: () => {
                requestSubmitting.value = true
            },
            onFinish: () => {
                requestSubmitting.value = false
            },
            onSuccess: () => {
                requestDialogOpen.value = false
                requestNotes.value = ''
            },
            onError: (errors) => {
                const detail = Array.isArray(errors?.team_id)
                    ? errors.team_id[0]
                    : errors?.team_id || errors?.type || 'Unable to send request.'

                showAppToast(String(detail), 'error', {
                    summary: 'Team Change Request',
                })
            },
        },
    )
}

function changeTeam() {
    if (!selectedTeamId.value) return
    router.get('/coach/team', { team_id: selectedTeamId.value }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    })
}

function printTeamRoster() {
    if (!selectedTeamId.value) return
    const params = new URLSearchParams()
    params.set('team_id', String(selectedTeamId.value))
    window.open(`/coach/team/print?${params.toString()}`, '_blank')
}

function clearInjury(player: PlayerRow) {
    clearingInjuryPlayerId.value = player.id
    router.post(
        `/coach/team-players/${player.id}/clear-injury`,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                showAppToast('Injury cleared successfully.', 'success', {
                    summary: 'Team Roster',
                })
            },
            onError: (errors) => {
                const detail = Array.isArray(errors?.injury)
                    ? errors.injury[0]
                    : errors?.injury || 'Unable to clear injury right now.'

                showAppToast(String(detail), 'error', {
                    summary: 'Team Roster',
                })
            },
            onFinish: () => {
                clearingInjuryPlayerId.value = null
            },
        },
    )
}
</script>

<template>
    <div class="space-y-5">
        <div v-if="props.teams.length" class="flex flex-wrap items-center gap-2 text-xs text-slate-600">
            <div v-if="props.teams.length > 1" class="flex items-center gap-2">
                <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Team</span>
                <select
                    v-model.number="selectedTeamId"
                    @change="changeTeam"
                    class="rounded-md border border-slate-300 px-2 py-1 text-xs text-slate-700"
                >
                    <option v-for="teamOption in props.teams" :key="teamOption.id" :value="teamOption.id">
                        {{ teamOption.team_name }}
                    </option>
                </select>
            </div>
        </div>

        <div v-if="!props.team" class="page-card rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-slate-500">You are not assigned to any team yet.</p>
        </div>

        <div v-else class="space-y-5">
            <section class="page-card rounded-2xl border border-[#034485] bg-[#034485] p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
                    <img
                        :src="teamAvatarUrl(props.team.team_avatar)"
                        class="h-24 w-24 rounded-2xl object-cover ring-4 ring-white sm:h-28 sm:w-28"
                    />

                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-white/90">
                            <span
                                class="rounded-full px-2 py-0.5 text-white"
                                :style="{
                                    backgroundColor: sportColor(props.team.sport?.name ?? props.team.sport?.id ?? props.team.sport),
                                    color: sportTextColor(props.team.sport?.name ?? props.team.sport?.id ?? props.team.sport),
                                }"
                            >
                                {{ props.team.sport?.name }}
                            </span>
                            <span class="rounded-full bg-white/15 px-2 py-0.5 text-white">{{ props.team.year ?? 'N/A' }}</span>
                        </div>
                        <h2 class="mt-2 text-2xl font-bold text-white">{{ props.team.team_name }}</h2>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button
                            class="rounded-full border border-white bg-white px-4 py-2 text-xs font-semibold text-[#034485] hover:bg-white/90"
                            @click="openRequest()"
                        >
                            Request Team Change
                        </button>
                        <button
                            class="rounded-full border border-white bg-white px-4 py-2 text-xs font-semibold text-[#034485] hover:bg-white/90"
                            @click="printTeamRoster"
                        >
                            Print Roster
                        </button>
                    </div>
                </div>

                <div class="mt-5 grid gap-3 lg:grid-cols-2">
                    <article class="page-card rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-white/75">Head Coach</p>
                            <span
                                v-if="isCurrentCoach(props.team.coach)"
                                class="inline-flex items-center rounded-full border border-white/30 bg-white/15 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-white"
                            >
                                You
                            </span>
                        </div>
                        <div class="mt-3 flex items-center gap-3">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-white/20 bg-white/15 text-sm font-bold text-white">
                                <img
                                    v-if="props.team.coach?.user?.avatar"
                                    :src="coachAvatarUrl(props.team.coach)"
                                    alt="Head coach profile photo"
                                    class="h-full w-full object-cover"
                                />
                                <span v-else>{{ initialsFromParts(props.team.coach?.first_name, props.team.coach?.last_name) }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-white">
                                    {{ props.team.coach?.first_name }} {{ props.team.coach?.last_name }}
                                </p>
                                <p class="mt-1 text-xs text-white/75">
                                    {{ props.team.coach?.user?.email || 'No email available' }}
                                </p>
                            </div>
                        </div>
                    </article>

                    <article class="page-card rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-white/75">Assistant Coach</p>
                            <span
                                v-if="isCurrentCoach(props.team.assistantCoach)"
                                class="inline-flex items-center rounded-full border border-white/30 bg-white/15 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-white"
                            >
                                You
                            </span>
                        </div>
                        <div class="mt-3 flex items-center gap-3">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-white/20 bg-white/15 text-sm font-bold text-white">
                                <img
                                    v-if="props.team.assistantCoach?.user?.avatar"
                                    :src="coachAvatarUrl(props.team.assistantCoach)"
                                    alt="Assistant coach profile photo"
                                    class="h-full w-full object-cover"
                                />
                                <span v-else>{{ initialsFromParts(props.team.assistantCoach?.first_name, props.team.assistantCoach?.last_name) }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-white">
                                    {{ props.team.assistantCoach?.first_name }} {{ props.team.assistantCoach?.last_name }}
                                </p>
                                <p class="mt-1 text-xs text-white/75">
                                    {{ props.team.assistantCoach?.user?.email || 'No assistant coach assigned' }}
                                </p>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <div v-if="filteredPlayers.length === 0" class="page-card rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500">
                No players found for this team.
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <article v-for="player in filteredPlayers" :key="player.id" class="page-card rounded-2xl border border-[#034485]/35 bg-[#f7fbff] p-4 shadow-[0_16px_34px_-28px_rgba(3,68,133,0.35)]">
                    <div class="pointer-events-none -mx-4 -mt-4 mb-4 h-12 rounded-t-2xl bg-gradient-to-r from-[#034485] via-[#0b5aa6] to-[#034485]/90"></div>
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-3">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-[#034485]/20 bg-white text-sm font-bold text-[#034485] shadow-sm">
                                <img
                                    v-if="player.student?.user?.avatar"
                                    :src="userAvatarUrl(player.student.user.avatar)"
                                    alt="Player profile photo"
                                    class="h-full w-full object-cover"
                                />
                                <span v-else>{{ initialsFromParts(player.student?.first_name, player.student?.last_name) }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-base font-semibold text-slate-900">{{ player.student?.first_name }} {{ player.student?.last_name }}</p>
                                <p class="text-xs text-slate-600">{{ player.student?.student_id_number || '-' }}</p>
                                <button
                                    type="button"
                                    class="mt-2 inline-flex rounded-full border border-[#034485] px-2.5 py-1 text-[11px] font-semibold text-[#034485] hover:bg-[#034485]/10"
                                    @click="openDetails(player)"
                                >
                                    View Details
                                </button>
                            </div>
                        </div>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="statusTone((player.player_status ?? 'active') as PlayerStatus)">
                            {{ (player.player_status ?? 'active').toString().toUpperCase() }}
                        </span>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-3 text-xs text-slate-600 sm:grid-cols-4">
                        <div class="rounded-2xl border border-[#034485]/12 bg-white px-3 py-2">
                            <span class="text-[#034485]">Jersey</span>
                            <p class="font-semibold text-slate-900">
                                <span v-if="player.jersey_number">{{ player.jersey_number }}</span>
                                <span v-else class="text-amber-600">Pending</span>
                            </p>
                        </div>
                        <div class="rounded-2xl border border-[#034485]/12 bg-white px-3 py-2">
                            <span class="text-[#034485]">Position</span>
                            <p class="font-semibold text-slate-900">
                                <span v-if="player.athlete_position">{{ player.athlete_position }}</span>
                                <span v-else class="text-red-600">Unassigned</span>
                            </p>
                        </div>
                        <div class="rounded-2xl border border-[#034485]/12 bg-white px-3 py-2">
                            <span class="text-[#034485]">Height</span>
                            <p class="font-semibold text-slate-900">{{ formatMeasure(player.student?.height, 'cm') }}</p>
                        </div>
                        <div class="rounded-2xl border border-[#034485]/12 bg-white px-3 py-2">
                            <span class="text-[#034485]">Weight</span>
                            <p class="font-semibold text-slate-900">{{ formatMeasure(player.student?.weight, 'kg') }}</p>
                        </div>
                    </div>

                    <div class="mt-3 grid gap-3">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-[#034485]">Update Position</label>
                            <select
                                v-if="positionsForSport().length > 0"
                                v-model="positionDrafts[player.id]"
                                class="mt-1 w-full rounded-md border border-[#034485]/20 bg-white px-2 py-1.5 text-sm text-slate-800"
                            >
                                <option value="">Select position</option>
                                <option v-for="position in positionsForSport()" :key="position" :value="position">
                                    {{ position }}
                                </option>
                            </select>
                            <input
                                v-else
                                v-model="positionDrafts[player.id]"
                                type="text"
                                class="mt-1 w-full rounded-md border border-[#034485]/20 bg-white px-2 py-1.5 text-sm text-slate-800"
                                placeholder="Assign position"
                            />
                            <div class="mt-2 flex items-center justify-between">
                                <button @click="savePosition(player.id)" class="rounded-md bg-[#034485] px-3 py-1.5 text-xs text-white hover:bg-[#033a70]">Save Position</button>
                                <span v-if="positionSaveState[player.id] && positionSaveState[player.id] !== 'idle'" class="text-[11px] text-slate-500">
                                    {{ positionSaveState[player.id] === 'saving' ? 'Saving...' : positionSaveState[player.id] === 'saved' ? 'Saved' : 'Error' }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-[#034485]">Status</label>
                            <div class="mt-1">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusTone((player.player_status ?? 'active') as PlayerStatus)">
                                    {{ (player.player_status ?? 'active').toString().toUpperCase() }}
                                </span>
                            </div>
                            <div
                                v-if="(player.player_status ?? 'active') === 'injured'"
                                class="mt-2 rounded-xl border border-rose-300 bg-rose-50 p-3"
                            >
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-rose-800">Injury Notes</p>
                                <p class="mt-1 text-xs leading-5 text-rose-900">
                                    {{ formatSimple(player.latest_injury_notes) }}
                                </p>
                                <button
                                    type="button"
                                    class="mt-3 rounded-md border border-rose-300 bg-white px-3 py-1.5 text-xs font-semibold text-rose-800 hover:border-rose-400 disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="clearingInjuryPlayerId === player.id"
                                    @click="clearInjury(player)"
                                >
                                    {{ clearingInjuryPlayerId === player.id ? 'Clearing...' : 'Clear Injury' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </div>

        <transition name="athlete-modal">
            <div v-if="detailsOpen" class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 px-4 py-6" @click.self="closeDetails">
                <div class="flex min-h-full items-center justify-center">
                <div class="flex max-h-[calc(100vh-3rem)] w-full max-w-2xl flex-col overflow-hidden rounded-3xl border border-[#034485]/35 bg-white shadow-[0_28px_70px_-34px_rgba(2,12,27,0.45)]">
                <div class="rounded-t-3xl bg-[#034485] px-6 py-5 text-white sm:px-8">
                    <p class="text-xs font-semibold uppercase tracking-wide text-white/75">Player Details</p>
                    <h3 class="mt-1 text-2xl font-bold text-white">
                        {{ formatSimple(selectedStudent?.first_name) }} {{ formatSimple(selectedStudent?.last_name) }}
                    </h3>
                    <p class="mt-1 text-xs text-white/80">Student ID: {{ formatSimple(selectedStudent?.student_id_number) }}</p>
                </div>
                <div class="overflow-y-auto p-6 sm:p-8">
                <div class="flex flex-wrap items-start justify-between gap-6">
                    <div class="min-w-[220px] flex-1 space-y-4">
                        <div class="grid gap-3 text-sm text-slate-700 sm:grid-cols-2">
                            <div class="rounded-2xl border border-[#034485]/15 bg-[#f7fbff] px-3 py-3 sm:col-span-2">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-[#034485]">Student ID</p>
                                <p class="mt-1 inline-flex items-center gap-2 font-semibold text-slate-900">
                                    {{ formatSimple(selectedStudent?.student_id_number) }}
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 text-[11px] font-semibold text-[#034485] hover:text-[#033a70]"
                                        @click="copyToClipboard(selectedStudent?.student_id_number, 'student-id')"
                                        title="Copy student ID"
                                        aria-label="Copy student ID"
                                    >
                                        <svg aria-hidden="true" viewBox="0 0 24 24" class="h-4 w-4">
                                            <path fill="currentColor" d="M16 1H6a2 2 0 0 0-2 2v12h2V3h10ZM19 5H10a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2m0 16H10V7h9Z" />
                                        </svg>
                                        <span v-if="copiedField === 'student-id'" class="text-[10px]">Student ID Copied</span>
                                    </button>
                                </p>
                            </div>
                            <div class="rounded-2xl border border-[#034485]/15 bg-[#f7fbff] px-3 py-3">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-[#034485]">Position</p>
                                <p class="mt-1 font-semibold text-slate-900">{{ formatSimple(selectedPlayer?.athlete_position) }}</p>
                            </div>
                            <div class="rounded-2xl border border-[#034485]/15 bg-[#f7fbff] px-3 py-3">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-[#034485]">Jersey</p>
                                <p class="mt-1 font-semibold text-slate-900">{{ formatSimple(selectedPlayer?.jersey_number) }}</p>
                            </div>
                        </div>

                        <div class="grid gap-3 text-sm text-slate-700 sm:grid-cols-2">
                            <div class="rounded-2xl border border-[#034485]/15 bg-[#f7fbff] px-3 py-3">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-[#034485]">Course/Strand</p>
                                <p class="mt-1 font-semibold text-slate-900">{{ formatSimple(selectedStudent?.course_or_strand) }}</p>
                            </div>
                            <div class="rounded-2xl border border-[#034485]/15 bg-[#f7fbff] px-3 py-3">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-[#034485]">Academic Level</p>
                                <p class="mt-1 font-semibold text-slate-900">{{ formatSimple(selectedStudent?.academic_level_label ?? selectedStudent?.current_grade_level) }}</p>
                            </div>
                            <div class="rounded-2xl border border-[#034485]/15 bg-[#f7fbff] px-3 py-3">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-[#034485]">Gender</p>
                                <p class="mt-1 font-semibold text-slate-900">{{ formatSimple(selectedStudent?.gender) }}</p>
                            </div>
                            <div class="rounded-2xl border border-[#034485]/15 bg-[#f7fbff] px-3 py-3">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-[#034485]">Height</p>
                                <p class="mt-1 font-semibold text-slate-900">{{ formatMeasure(selectedStudent?.height, 'cm') }}</p>
                            </div>
                            <div class="rounded-2xl border border-[#034485]/15 bg-[#f7fbff] px-3 py-3 sm:col-span-2">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-[#034485]">Weight</p>
                                <p class="mt-1 font-semibold text-slate-900">{{ formatMeasure(selectedStudent?.weight, 'kg') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex h-28 w-28 shrink-0 items-center justify-center overflow-hidden rounded-full border border-[#034485]/20 bg-[#f7fbff]">
                        <img :src="userAvatarUrl(selectedStudent?.user?.avatar ?? null)" alt="Student avatar" class="h-full w-full object-cover" />
                    </div>
                </div>

                <div class="mt-6 border-t border-slate-200 pt-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#034485]">Contact</p>
                    <div class="mt-2 grid gap-2 text-sm text-slate-700 sm:grid-cols-2">
                        <p>
                            <span class="font-semibold text-slate-900">Email:</span>
                            <span class="ml-1 inline-flex items-center gap-2">
                                {{ formatSimple(selectedStudent?.user?.email) }}
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-[#034485] hover:text-[#033a70]"
                                    @click="copyToClipboard(selectedStudent?.user?.email, 'email')"
                                    title="Copy email"
                                    aria-label="Copy email"
                                >
                                    <svg aria-hidden="true" viewBox="0 0 24 24" class="h-4 w-4">
                                        <path
                                            fill="currentColor"
                                            d="M16 1H6a2 2 0 0 0-2 2v12h2V3h10ZM19 5H10a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2m0 16H10V7h9Z"
                                        />
                                    </svg>
                                    <span v-if="copiedField === 'email'" class="text-[10px]">Email Copied</span>
                                </button>
                            </span>
                        </p>
                        <p>
                            <span class="font-semibold text-slate-900">Phone:</span>
                            <span class="ml-1 inline-flex items-center gap-2">
                                {{ formatSimple(selectedStudent?.phone_number) }}
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-[#034485] hover:text-[#033a70]"
                                    @click="copyToClipboard(selectedStudent?.phone_number, 'phone')"
                                    title="Copy phone number"
                                    aria-label="Copy phone number"
                                >
                                    <svg aria-hidden="true" viewBox="0 0 24 24" class="h-4 w-4">
                                        <path
                                            fill="currentColor"
                                            d="M16 1H6a2 2 0 0 0-2 2v12h2V3h10ZM19 5H10a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2m0 16H10V7h9Z"
                                        />
                                    </svg>
                                    <span v-if="copiedField === 'phone'" class="text-[10px]">Phone Number Copied</span>
                                </button>
                            </span>
                        </p>
                    </div>
                </div>

                <div class="mt-6 border-t border-slate-200 pt-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#034485]">Emergency Contact</p>
                    <div class="mt-2 grid gap-2 text-sm text-slate-700 sm:grid-cols-2">
                        <p><span class="font-semibold text-slate-900">Name:</span> {{ formatSimple(selectedStudent?.emergency_contact_name) }}</p>
                        <p><span class="font-semibold text-slate-900">Relationship:</span> {{ formatSimple(selectedStudent?.emergency_contact_relationship) }}</p>
                        <p class="sm:col-span-2">
                            <span class="font-semibold text-slate-900">Phone:</span>
                            <span class="ml-1 inline-flex items-center gap-2">
                                {{ formatSimple(selectedStudent?.emergency_contact_phone) }}
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-[#034485] hover:text-[#033a70]"
                                    @click="copyToClipboard(selectedStudent?.emergency_contact_phone, 'emergency-phone')"
                                    title="Copy emergency phone"
                                    aria-label="Copy emergency phone"
                                >
                                    <svg aria-hidden="true" viewBox="0 0 24 24" class="h-4 w-4">
                                        <path
                                            fill="currentColor"
                                            d="M16 1H6a2 2 0 0 0-2 2v12h2V3h10ZM19 5H10a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2m0 16H10V7h9Z"
                                        />
                                    </svg>
                                    <span v-if="copiedField === 'emergency-phone'" class="text-[10px]">Emergency Contact Number Copied</span>
                                </button>
                            </span>
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        type="button"
                        class="rounded-full bg-[#034485] px-4 py-2 text-sm font-semibold text-white hover:bg-[#033a70]"
                        @click="closeDetails"
                    >
                        Close
                    </button>
                </div>
                </div>
                </div>
                </div>
            </div>
        </transition>

        <ConfirmDialog
            :open="requestDialogOpen"
            title="Request Team Change"
            description="Add the details you want the admin team to review."
            confirm-text="Send Request"
            @update:open="requestDialogOpen = $event"
            @confirm="submitRequest"
        >
            <div class="space-y-3">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Details (optional)</label>
                <textarea
                    v-model="requestNotes"
                    rows="3"
                    class="w-full rounded-md border border-slate-300 px-2 py-2 text-sm"
                    placeholder="Provide context (names, schedule, reason)"
                />

                <p v-if="requestSubmitting" class="text-xs text-slate-500">Sending request...</p>
            </div>
        </ConfirmDialog>
    </div>
</template>

<style scoped>
.athlete-modal-enter-active,
.athlete-modal-leave-active {
    transition: opacity 180ms ease, transform 180ms ease;
}
.athlete-modal-enter-from,
.athlete-modal-leave-to {
    opacity: 0;
    transform: translateY(8px) scale(0.98);
}
.athlete-modal-enter-to,
.athlete-modal-leave-from {
    opacity: 1;
    transform: translateY(0) scale(1);
}
</style>
<style scoped>
.page-card {
    opacity: 0;
    animation: coach-team-card-rise 0.55s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

@keyframes coach-team-card-rise {
    from {
        opacity: 0;
        transform: translateY(16px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .page-card {
        animation: none;
        opacity: 1;
    }
}
</style>
