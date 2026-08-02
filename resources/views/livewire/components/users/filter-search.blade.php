<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="gap-6 sm:flex sm:items-start sm:justify-between">
        <div>
            <p class="text-sm font-semibold tracking-[0.24em] text-slate-500 uppercase">Filter and Search</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 sm:items-end">
            {{-- user and email search --}}
            <div>
                <label for="search" class="block text-sm font-medium text-slate-700">Search</label>
                <input
                    id="search"
                    wire:model.live.debounce.250ms="search"
                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-400 focus:ring-4 focus:ring-slate-200"
                    placeholder="Search by name or email"
                />
            </div>

            {{-- roleLabel filter --}}
            <div>
                <label for="roleLabelFilter" class="block text-sm font-medium text-slate-700">Role</label>

                <select
                    id="roleLabelFilter"
                    wire:model.live.debounce.100ms="roleLabelFilter"
                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-400 focus:ring-4 focus:ring-slate-200"
                >
                    <option value="all">All roles</option>
                    @foreach ($roleLabels as $roleLabel)
                        <option wire:key="role-label-{{ $roleLabel['value'] }}" value="{{ $roleLabel['value'] }}">
                            {{ $roleLabel['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>
