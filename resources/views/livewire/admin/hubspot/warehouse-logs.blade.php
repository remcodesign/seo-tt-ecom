<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold tracking-[0.24em] text-slate-500 uppercase">HubSpot test tools</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">Warehouse workflow logs</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                Browse durable workflow tasks, compare outcomes, and inspect the redacted execution trace saved for each run.
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a
                href="{{ route('admin.hubspot.console') }}"
                class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
            >
                Open console
            </a>
            <a
                href="{{ route('admin.hubspot.logs') }}"
                class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
            >
                Integration logs
            </a>
        </div>
    </div>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
        @foreach (['total' => 'Total tasks', 'accepted' => 'Accepted', 'processing' => 'Processing', 'succeeded' => 'Succeeded', 'failed' => 'Failed', 'expired' => 'Expired'] as $key => $label)
            <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">{{ $label }}</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $summary[$key] }}</p>
            </div>
        @endforeach
    </section>

    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Task filters</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Search IDs and outcomes across the durable task history.</p>
            </div>
            <button
                type="button"
                wire:click="resetFilters"
                class="cursor-pointer rounded-2xl border border-slate-900 bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-700"
            >
                Reset filters
            </button>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div>
                <label for="warehouse-log-search" class="text-sm font-semibold text-slate-800">Search tasks</label>
                <input
                    id="warehouse-log-search"
                    type="search"
                    wire:model.live="form.search"
                    placeholder="deal, task, callback, failure..."
                    class="mt-2 block w-full rounded-2xl border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                />
            </div>
            <div>
                <label for="warehouse-log-status" class="text-sm font-semibold text-slate-800">Status</label>
                <select
                    id="warehouse-log-status"
                    wire:model.live="form.status"
                    class="mt-2 block w-full rounded-2xl border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                >
                    @foreach ($this->statuses() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="warehouse-log-source" class="text-sm font-semibold text-slate-800">Source</label>
                <select
                    id="warehouse-log-source"
                    wire:model.live="form.source"
                    class="mt-2 block w-full rounded-2xl border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                >
                    @foreach ($this->sources() as $value => $label)
                        <option value="{{ $value }}">{{ str($label)->headline() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="warehouse-log-date" class="text-sm font-semibold text-slate-800">Created</label>
                <select
                    id="warehouse-log-date"
                    wire:model.live="form.date_filter"
                    class="mt-2 block w-full rounded-2xl border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                >
                    @foreach ($this->dateFilters() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
        <section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-col gap-2 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-end">
                <span class="text-sm text-slate-500">{{ $tasks->total() }} matching tasks</span>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($tasks as $task)
                    @php
                        $statusClass = match ($task->status->value) {
                            'succeeded' => 'bg-emerald-100 text-emerald-800',
                            'failed', 'expired' => 'bg-rose-100 text-rose-800',
                            'processing' => 'bg-amber-100 text-amber-800',
                            default => 'bg-sky-100 text-sky-800',
                        };
                    @endphp
                    <div wire:key="warehouse-task-{{ $task->id }}" class="grid grid-cols-[minmax(0,1fr)_auto] gap-4 px-6 py-3.5 {{ $selectedTaskId === $task->id ? 'bg-slate-50' : '' }}">
                        <div class="min-w-0 space-y-2 text-xs">
                            <div class="flex min-w-0 flex-wrap items-center gap-x-4 gap-y-1">
                                <span class="text-slate-500">{{ $task->created_at?->format('Y-m-d H:i:s') }}</span>
                                <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold tracking-wide uppercase {{ $statusClass }}">{{ $task->status->value }}</span>
                                <span class="truncate font-mono font-semibold text-slate-900" title="{{ $task->deal_id }}">Deal {{ $task->deal_id }}</span>
                            </div>
                            <div class="flex min-w-0 flex-wrap items-center gap-x-4 gap-y-1 text-slate-500">
                                <span class="font-semibold text-slate-700">{{ str($task->source)->headline() }}</span>
                                <span>Tenant {{ $task->tenant_id }}</span>
                                <span class="max-w-64 truncate font-mono" title="{{ $task->callback_id }}">Callback {{ $task->callback_id }}</span>
                            </div>
                        </div>
                        <button
                            type="button"
                            wire:click="selectTask('{{ $task->id }}')"
                            class="self-center cursor-pointer rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-500 hover:bg-slate-50"
                        >
                            {{ $selectedTaskId === $task->id ? 'Selected' : 'Details' }}
                        </button>
                    </div>
                @empty
                    <div class="px-6 py-12 text-center text-sm text-slate-500">No warehouse workflow tasks match these filters.</div>
                @endforelse
            </div>

            <div class="border-t border-slate-200 px-6 py-4">{{ $tasks->links() }}</div>
        </section>

        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 lg:sticky lg:top-6 sm:p-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase">Selected task</p>
                    @if ($selectedTask)
                        <h2 class="mt-2 break-all font-mono text-xl font-semibold text-slate-900">{{ $selectedTask->id }}</h2>
                        <p class="mt-2 text-sm text-slate-600">Full persisted task details and redacted execution trace.</p>
                    @else
                        <h2 class="mt-2 text-xl font-semibold text-slate-900">Task history</h2>
                        <p class="mt-2 text-sm text-slate-600">Select a row to open the complete stored task record.</p>
                    @endif
                </div>
                @if ($selectedTask)
                    <button
                        type="button"
                        wire:click="clearSelection"
                        class="cursor-pointer rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Close details
                    </button>
                @endif
            </div>

            @if ($selectedTask)
                <dl class="mt-6 grid gap-x-4 gap-y-3 text-sm sm:grid-cols-2">
                @foreach ([
                    'Created' => $selectedTask->created_at?->toIso8601String() ?? 'Unknown',
                    'Status' => $selectedTask->status->value,
                    'Tenant' => $selectedTask->tenant_id,
                    'Deal' => $selectedTask->deal_id,
                    'Source' => $selectedTask->source,
                    'Callback' => $selectedTask->callback_id,
                ] as $label => $value)
                    <div>
                        <dt class="text-slate-500">{{ $label }}</dt>
                        <dd class="mt-1 break-all font-mono text-xs font-semibold text-slate-900">{{ $value }}</dd>
                    </div>
                @endforeach
                </dl>

                @if (is_array($selectedTask->result))
                <div class="mt-8">
                    <h3 class="text-lg font-semibold text-slate-900">Result</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ $selectedTask->result['summary'] ?? 'Recommendation result' }}</p>
                    <pre class="mt-4 max-h-112 overflow-auto rounded-2xl bg-slate-950 p-5 text-xs leading-5 whitespace-pre-wrap text-slate-100">{{ json_encode($selectedTask->result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
                @endif

                @if (is_array($selectedTask->debug_trace) && $selectedTask->debug_trace !== [])
                <div class="mt-8">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-lg font-semibold text-slate-900">Debug trace</h3>
                        <span class="text-xs font-semibold tracking-wide text-slate-500 uppercase">{{ count($selectedTask->debug_trace) }} steps</span>
                    </div>
                    <div class="mt-4 space-y-3">
                        @foreach ($selectedTask->debug_trace as $trace)
                            <details wire:key="warehouse-trace-{{ $selectedTask->id }}-{{ $loop->index }}" class="rounded-2xl border border-slate-200 bg-slate-50 p-4" @if ($loop->last) open @endif>
                                <summary class="cursor-pointer list-none text-sm font-semibold text-slate-900">
                                    <span class="mr-2 inline-block rounded-full bg-slate-200 px-2 py-1 font-mono text-[10px] text-slate-600 uppercase">{{ $trace['step'] ?? 'step' }}</span>
                                    {{ $trace['message'] ?? 'Trace event' }}
                                    <span class="float-right font-mono text-[10px] font-normal text-slate-500">{{ $trace['at'] ?? '' }}</span>
                                </summary>
                                @if (! empty($trace['data']))
                                    <pre class="mt-4 max-h-112 overflow-auto rounded-xl bg-slate-900 p-4 text-xs leading-5 whitespace-pre-wrap text-slate-100">{{ json_encode($trace['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                @endif
                            </details>
                        @endforeach
                    </div>
                    <details class="mt-5">
                        <summary class="cursor-pointer text-sm font-semibold text-slate-700">Show complete trace JSON</summary>
                        <pre class="mt-3 max-h-144 overflow-auto rounded-2xl bg-slate-950 p-5 text-xs leading-5 text-slate-100">{{ json_encode($selectedTask->debug_trace, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </details>
                </div>
                @else
                    <div class="mt-8 rounded-2xl bg-slate-50 p-5 text-sm text-slate-600">No debug trace has been recorded for this task.</div>
                @endif
            @else
                <div class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5 text-sm leading-6 text-slate-600">
                    Choose a task from the list to keep its metadata, result, and debug trace visible here while you browse.
                </div>
            @endif
        </section>
    </div>
</div>
