<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="sm:flex sm:items-start sm:justify-between gap-6">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500">Filter and Search</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 sm:items-end">
            {{-- user and email search --}}
            <div>
                <label for="search" class="block text-sm font-medium text-slate-700">Search</label>
                <input id="search" wire:model.live.debounce.250ms="search"
                    class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 focus:border-slate-400 focus:ring-4 focus:ring-slate-200"
                    placeholder="Search by name or email" />
            </div>
        </div>
    </div>
</div>
