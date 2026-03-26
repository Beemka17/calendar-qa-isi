@extends('layouts.app')

@section('content')
    <div x-data="calendarPage()" x-init="init()" class="px-4 py-4 min-h-screen flex flex-col">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div class="flex items-center gap-2">
                <h1 class="text-lg font-semibold">QUALITY DEPT. SCHEDULE ACTIVITY</h1>
                <template x-if="loadingGroups">
                    <span class="text-sm opacity-70">Loading groups...</span>
                </template>
            </div>

            <!-- Filters -->
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-end">
                <input type="text" class="w-full md:w-64 rounded border px-3 py-2 bg-white dark:bg-slate-900"
                    placeholder="Search title/desc..." x-model.debounce.400ms="filters.q" @input="refetch()" />

                <input type="text" class="w-full md:w-48 rounded border px-3 py-2 bg-white dark:bg-slate-900"
                    placeholder="PIC..." x-model.debounce.400ms="filters.pic" @input="refetch()" />

                <input type="text" class="w-full md:w-56 rounded border px-3 py-2 bg-white dark:bg-slate-900"
                    placeholder="Location..." x-model.debounce.400ms="filters.location" @input="refetch()" />
            </div>
        </div>

        <!-- Legend -->
        <div class="mt-3 flex flex-wrap gap-2">
            <template x-for="g in groups" :key="g.id">
                <label
                    class="inline-flex items-center gap-2 rounded border px-2 py-1 cursor-pointer select-none bg-white dark:bg-slate-900">
                    <input type="checkbox" class="rounded" :value="g.id" :checked="activeGroupIds.includes(g.id)"
                        @change="toggleGroup(g.id)" />
                    <span class="inline-block w-3 h-3 rounded" :style="`background:${g.color_hex}`"></span>
                    <span class="text-sm" x-text="g.name"></span>
                </label>
            </template>

            <button type="button" class="ml-1 text-sm rounded border px-2 py-1 bg-white dark:bg-slate-900"
                @click="selectAllGroups()">All</button>

            <button type="button" class="text-sm rounded border px-2 py-1 bg-white dark:bg-slate-900"
                @click="clearGroups()">None</button>
            @can('admin')
                <button type="button" class="text-sm rounded border px-2 py-1 bg-white dark:bg-slate-900"
                    @click="openGroupManager()">
                    Manage Groups
                </button>
            @endcan
        </div>

        <!-- Calendar container -->
        <div class="mt-4 rounded-lg border bg-white dark:bg-slate-900 p-3 flex-1 flex flex-col">
            <div id="calendar" class="flex-1"></div>
        </div>

        <!-- Modal -->
        <div x-show="modal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40" @click="closeModal()"></div>

            <div class="relative w-full max-w-xl rounded-lg bg-white dark:bg-slate-900 border p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold" x-text="modal.mode === 'create' ? 'Create Event' : 'Edit Event'">
                        </h2>
                        <p class="text-sm opacity-70"
                            x-text="modal.mode === 'create' ? 'Tambah schedule baru' : `Edit #${form.id}`"></p>
                    </div>

                    <button type="button" class="rounded border px-2 py-1" @click="closeModal()">✕</button>
                </div>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-3">
                        <label class="text-sm">Title *</label>
                        <input type="text" class="w-full rounded border px-3 py-2 bg-white dark:bg-slate-900"
                            x-model="form.title" />
                        <template x-if="errors.title">
                            <div class="text-sm text-red-600 mt-1" x-text="errors.title"></div>
                        </template>
                    </div>

                    <!-- ROW: Event Group + PIC + Team -->
                    <div>
                        <label class="text-sm">Event Group *</label>
                        <select class="w-full rounded border px-3 py-2 bg-white dark:bg-slate-900"
                            x-model.number="form.event_group_id">
                            <option value="">-- Select --</option>
                            <template x-for="g in groups" :key="g.id">
                                <option :value="g.id" x-text="g.name"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="text-sm">PIC</label>
                        <input type="text" class="w-full rounded border px-3 py-2 bg-white dark:bg-slate-900"
                            x-model="form.pic" />
                    </div>

                    <!-- TEAM MULTISELECT (STYLE SELECT) -->
                    <div class="relative" x-data="{ open: false }">
                        <label class="text-sm">Team</label>

                        <div @click="open = !open"
                            class="w-full rounded border border-gray-500 px-3 py-2 bg-white dark:bg-slate-900 cursor-pointer flex justify-between items-center h-[42px] focus-within:ring-2 focus-within:ring-blue-500">

                            <span class="text-base truncate leading-normal">
                                <template x-if="form.team_ids.length === 0">
                                    <span class="text-gray-400">-- Select Team --</span>
                                </template>

                                <template x-if="form.team_ids.length > 0">
                                    <span x-text="getSelectedTeamNames().join(', ')"></span>
                                </template>
                            </span>

                            <span class="text-[10px] text-gray-500 ml-2 pointer-events-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </span>
                        </div>

                        <div x-show="open" @click.outside="open = false"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 transform scale-95"
                            x-transition:enter-end="opacity-100 transform scale-100"
                            class="absolute z-50 mt-1 w-full rounded-md border border-gray-200 bg-white dark:bg-slate-900 shadow-lg max-h-60 overflow-auto">

                            <template x-for="team in teams" :key="team.id">
                                <label
                                    class="flex items-center gap-2 px-3 py-2.5 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                    <input type="checkbox" :value="Number(team.id)" @change="toggleTeam(team.id)"
                                        :checked="form.team_ids.includes(Number(team.id))"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700 dark:text-gray-200" x-text="team.name"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <div x-show="form.event_group_id == CUTI_ID">
                        <label class="text-sm">Leave Type</label>

                        <select x-model="form.leave_type"
                            class="w-full rounded border px-3 py-2 bg-white dark:bg-slate-900">
                            <option value="">-- pilih --</option>
                            <option value="full">Full Day</option>
                            <option value="half">Half Day</option>
                        </select>
                    </div>

                    <div class="md:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="text-sm">Start *</label>
                            <input type="datetime-local"
                                class="w-full rounded border px-3 py-2 bg-white dark:bg-slate-900"
                                x-model="form.start_at" />
                            <template x-if="errors.start_at">
                                <div class="text-sm text-red-600 mt-1" x-text="errors.start_at"></div>
                            </template>
                        </div>

                        <div>
                            <label class="text-sm">End *</label>
                            <input type="datetime-local"
                                class="w-full rounded border px-3 py-2 bg-white dark:bg-slate-900"
                                x-model="form.end_at" />
                            <template x-if="errors.end_at">
                                <div class="text-sm text-red-600 mt-1" x-text="errors.end_at"></div>
                            </template>
                        </div>
                    </div>

                    <div class="md:col-span-3">
                        <label class="text-sm">Location</label>
                        <input type="text" class="w-full rounded border px-3 py-2 bg-white dark:bg-slate-900"
                            x-model="form.location" />
                        <template x-if="errors.location">
                            <div class="text-sm text-red-600 mt-1" x-text="errors.location"></div>
                        </template>
                    </div>

                    <div class="md:col-span-3">
                        <label class="text-sm">Attendance</label>

                        <textarea x-model="form.attendance" class="w-full rounded border px-3 py-2 bg-white dark:bg-slate-900"
                            placeholder="PIC atau Dept. yang akan hadir">
                        </textarea>
                    </div>

                    <div class="md:col-span-3">
                        <label class="text-sm">Description</label>
                        <textarea rows="3" class="w-full rounded border px-3 py-2 bg-white dark:bg-slate-900"
                            x-model="form.description"></textarea>
                        <template x-if="errors.description">
                            <div class="text-sm text-red-600 mt-1" x-text="errors.description"></div>
                        </template>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <template x-if="modal.mode === 'edit'">
                            <button type="button"
                                class="rounded border px-3 py-2 bg-red-600 text-white disabled:opacity-50"
                                :disabled="saving" @click="doDelete()">Delete</button>
                        </template>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" class="rounded border px-3 py-2" @click="closeModal()">Cancel</button>
                        <button type="button"
                            class="rounded border px-3 py-2 bg-slate-900 text-white dark:bg-white dark:text-slate-900 disabled:opacity-50"
                            :disabled="saving" @click="submit()">
                            <span x-text="saving ? 'Saving...' : 'Save'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Cuti -->
        <div x-show="cutiListModal.open" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">

            <div class="relative w-full max-w-xl rounded-lg bg-white dark:bg-slate-900 border p-4">

                <h2 class="text-lg font-bold mb-3">Daftar Cuti</h2>

                <template x-for="item in cutiListModal.list" :key="item.id">
                    <div class="flex justify-between items-center mb-2">
                        <span x-text="item.name"></span>

                        <button class="text-blue-600 text-sm" @click="editFromList(item.id)">
                            Edit
                        </button>
                    </div>
                </template>

                <button
                    class="mt-4 w-full rounded border px-3 py-2 bg-slate-900 text-white dark:bg-white dark:text-slate-900"
                    @click="cutiListModal.open = false">
                    Close
                </button>

            </div>
        </div>

        <!-- Group Manager Modal (Admin only) -->
        @can('admin')
            <div x-show="groupModal.open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/40" @click="closeGroupManager()"></div>

                <div class="relative w-full max-w-2xl rounded-lg bg-white dark:bg-slate-900 border p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold">Manage Event Groups</h2>
                            <p class="text-sm opacity-70">Create / edit / activate / deactivate group</p>
                        </div>
                        <button type="button" class="rounded border px-2 py-1" @click="closeGroupManager()">✕</button>
                    </div>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- LEFT: list -->
                        <div class="rounded border p-3 bg-white dark:bg-slate-900">
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-semibold">Groups</div>
                                <button type="button" class="text-sm rounded border px-2 py-1" @click="startCreateGroup()">+
                                    Add</button>
                            </div>

                            <div class="mt-3 space-y-2 max-h-80 overflow-auto">
                                <template x-for="g in groups" :key="g.id">
                                    <div class="flex items-center justify-between gap-2 rounded border px-2 py-2">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-block w-3 h-3 rounded"
                                                :style="`background:${g.color_hex}`"></span>
                                            <div>
                                                <div class="text-sm font-medium" x-text="g.name"></div>
                                                <div class="text-xs opacity-70">
                                                    <span x-text="g.is_active ? 'Active' : 'Inactive'"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <button type="button" class="text-xs rounded border px-2 py-1"
                                                @click="startEditGroup(g)">Edit</button>

                                            <button type="button" class="text-xs rounded border px-2 py-1"
                                                @click="toggleGroupActive(g)"
                                                x-text="g.is_active ? 'Deactivate' : 'Activate'"></button>

                                            <button type="button" class="text-xs rounded border px-2 py-1 text-red-600"
                                                @click="deleteGroup(g)">Delete</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- RIGHT: form -->
                        <div class="rounded border p-3 bg-white dark:bg-slate-900">
                            <div class="text-sm font-semibold" x-text="groupForm.id ? 'Edit Group' : 'Create Group'"></div>

                            <div class="mt-3 space-y-3">
                                <div>
                                    <label class="text-sm">Name *</label>
                                    <input type="text" class="w-full rounded border px-3 py-2 bg-white dark:bg-slate-900"
                                        x-model="groupForm.name" placeholder="e.g. Audit" />
                                    <template x-if="groupErrors.name">
                                        <div class="text-sm text-red-600 mt-1" x-text="groupErrors.name"></div>
                                    </template>
                                </div>

                                <div>
                                    <label class="text-sm">Color *</label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" class="h-10 w-12 rounded border bg-white dark:bg-slate-900"
                                            x-model="groupForm.color_hex" />
                                        <input type="text"
                                            class="flex-1 rounded border px-3 py-2 bg-white dark:bg-slate-900"
                                            x-model="groupForm.color_hex" placeholder="#3b82f6" />
                                    </div>
                                    <template x-if="groupErrors.color_hex">
                                        <div class="text-sm text-red-600 mt-1" x-text="groupErrors.color_hex"></div>
                                    </template>
                                </div>

                                <div class="flex items-center gap-2">
                                    <input type="checkbox" class="rounded" x-model="groupForm.is_active" />
                                    <span class="text-sm">Active</span>
                                </div>

                                <template x-if="groupErrors._general">
                                    <div class="text-sm text-red-600" x-text="groupErrors._general"></div>
                                </template>

                                <div class="flex items-center justify-end gap-2 pt-2">
                                    <button type="button" class="rounded border px-3 py-2"
                                        @click="resetGroupForm()">Clear</button>
                                    <button type="button"
                                        class="rounded border px-3 py-2 bg-slate-900 text-white dark:bg-white dark:text-slate-900 disabled:opacity-50"
                                        :disabled="groupSaving" @click="saveGroup()">
                                        <span x-text="groupSaving ? 'Saving...' : 'Save'"></span>
                                    </button>
                                </div>

                                <p class="text-xs opacity-70">
                                    Catatan: Delete hanya bisa jika group belum dipakai oleh event.
                                    Jika sudah dipakai, gunakan Deactivate.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

        <!-- Toast -->
        <div class="fixed bottom-4 right-4 z-50 space-y-2">
            <template x-for="t in toasts" :key="t.id">
                <div class="rounded border bg-white dark:bg-slate-900 px-3 py-2 shadow">
                    <div class="text-sm font-semibold" x-text="t.title"></div>
                    <div class="text-sm opacity-80" x-text="t.message"></div>
                </div>
            </template>
        </div>
    </div>
@endsection
