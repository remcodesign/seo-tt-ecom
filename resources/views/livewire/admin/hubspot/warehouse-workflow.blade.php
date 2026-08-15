<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold tracking-[0.24em] text-slate-500 uppercase">Full pipeline test</p>
            <h2 class="mt-3 text-2xl font-semibold tracking-tight text-slate-900">Inventory &amp; warehouse AI</h2>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                Read one Deal, recommend warehouses for its Line Items, write one idempotent note, and inspect the
                bounded result.
            </p>
        </div>
        <button
            type="button"
            wire:click="clearResults"
            class="cursor-pointer rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
        >
            Clear results
        </button>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-xl font-semibold text-slate-900">Run context</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        These fields replace the signed HubSpot execution envelope for an admin test.
                    </p>
                </div>
                <span class="rounded-full bg-sky-50 px-3 py-2 text-xs font-semibold text-sky-700">Admin only</span>
            </div>

            <div class="mt-6 space-y-5">
                <div>
                    <label for="warehouse-workflow-portal" class="text-sm font-semibold text-slate-800"
                        >Tenant / portal</label>
                    <select
                        id="warehouse-workflow-portal"
                        wire:model="form.portal_id"
                        class="mt-2 block w-full rounded-2xl border-slate-300 px-4 py-3 text-sm shadow-sm"
                    >
                        <option value="">Select an enabled portal</option>
                        @foreach ($portalOptions as $portalId => $portal)
                            <option value="{{ $portalId }}">{{ $portal['label'] }}</option>
                        @endforeach
                    </select>
                    @error('form.portal_id')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="warehouse-workflow-deal" class="text-sm font-semibold text-slate-800"
                        >HubSpot Deal ID</label>
                    <input
                        id="warehouse-workflow-deal"
                        wire:model="form.deal_id"
                        type="text"
                        class="mt-2 block w-full rounded-2xl border-slate-300 px-4 py-3 font-mono text-sm shadow-sm"
                    />
                    @error('form.deal_id')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="warehouse-workflow-note-deal" class="text-sm font-semibold text-slate-800"
                        >Note target Deal ID</label>
                    <input
                        id="warehouse-workflow-note-deal"
                        wire:model="form.note_deal_id"
                        type="text"
                        placeholder="Defaults to source Deal"
                        class="mt-2 block w-full rounded-2xl border-slate-300 px-4 py-3 font-mono text-sm shadow-sm"
                    />
                    @error('form.note_deal_id')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="warehouse-workflow-callback" class="text-sm font-semibold text-slate-800"
                        >Callback identity</label>
                    <input
                        id="warehouse-workflow-callback"
                        wire:model="form.callback_id"
                        type="text"
                        placeholder="Blank creates a synthetic admin identity"
                        class="mt-2 block w-full rounded-2xl border-slate-300 px-4 py-3 font-mono text-sm shadow-sm"
                    />
                    @error('form.callback_id')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-xs leading-5 text-slate-500">
                        Synthetic identities test Laravel processing but do not prove HubSpot workflow resumption.
                    </p>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="warehouse-workflow-version" class="text-sm font-semibold text-slate-800"
                            >Action version</label>
                        <input
                            id="warehouse-workflow-version"
                            wire:model="form.action_definition_version"
                            type="text"
                            class="mt-2 block w-full rounded-2xl border-slate-300 px-4 py-3 font-mono text-sm shadow-sm"
                        />
                        @error('form.action_definition_version')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="warehouse-workflow-id" class="text-sm font-semibold text-slate-800"
                            >Workflow ID</label>
                        <input
                            id="warehouse-workflow-id"
                            wire:model="form.workflow_id"
                            type="text"
                            placeholder="Optional"
                            class="mt-2 block w-full rounded-2xl border-slate-300 px-4 py-3 font-mono text-sm shadow-sm"
                        />
                        @error('form.workflow_id')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <button
                        type="button"
                        wire:click="runSynchronously"
                        wire:loading.attr="disabled"
                        class="cursor-pointer rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Run synchronously
                    </button>
                    <button
                        type="button"
                        wire:click="queueAsynchronously"
                        wire:loading.attr="disabled"
                        class="cursor-pointer rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Queue async test
                    </button>
                </div>
            </div>
        </section>

        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-xl font-semibold text-slate-900">Current task</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        The task row is the primary audit surface for this run.
                    </p>
                </div>
                @if ($taskResult)
                    <span class="rounded-full bg-slate-100 px-3 py-2 text-xs font-bold uppercase">{{ $taskResult['status'] }}</span>
                @endif
            </div>

            @if ($taskResult)
                <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-slate-500">Task ID</dt>
                        <dd class="mt-1 font-mono text-xs font-semibold break-all text-slate-900">
                            {{ $taskResult['id'] }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Deal</dt>
                        <dd class="mt-1 font-mono text-xs font-semibold text-slate-900">
                            {{ $taskResult['deal_id'] }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Tenant</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $taskResult['tenant_id'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Source</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $taskResult['source'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Started</dt>
                        <dd class="mt-1 text-slate-700">{{ $taskResult['started_at'] ?? 'Not started' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Completed</dt>
                        <dd class="mt-1 text-slate-700">{{ $taskResult['completed_at'] ?? 'Not completed' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Note ID</dt>
                        <dd class="mt-1 font-mono text-xs text-slate-700">
                            {{ $taskResult['note_id'] ?? 'Not created' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Callback</dt>
                        <dd class="mt-1 text-slate-700">
                            {{ $taskResult['callback_sent_at'] ? 'Completed' : ($taskResult['source'] === 'ADMIN_CONSOLE_TEST' ? 'Synthetic / skipped' : 'Pending') }}
                        </dd>
                    </div>
                </dl>

                <button
                    type="button"
                    wire:click="refreshStatus"
                    wire:loading.attr="disabled"
                    class="mt-6 cursor-pointer rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                >
                    Refresh status
                </button>
            @else
                <div class="mt-6 rounded-2xl bg-slate-50 p-5 text-sm leading-6 text-slate-600">
                    Run a synchronous or async test to create a durable task.
                </div>
            @endif

            @if ($errorMessage !== '')
                <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                    {{ $errorMessage }}
                </div>
            @endif
        </section>
    </div>

    @if ($taskResult && ($taskResult['result'] || $taskResult['failure_code']))
        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
            <h3 class="text-xl font-semibold text-slate-900">Bounded result</h3>
            @if ($taskResult['failure_code'])
                <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
                    Failure code: <strong>{{ $taskResult['failure_code'] }}</strong>
                </div>
            @endif
            @if (is_array($taskResult['result']))
                <p class="mt-4 text-sm text-slate-600">
                    {{ $taskResult['result']['summary'] ?? 'Recommendation result' }}
                </p>
                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b border-slate-200 text-xs tracking-wide text-slate-500 uppercase">
                            <tr>
                                <th class="px-2 py-3">SKU</th>
                                <th class="px-2 py-3">Qty</th>
                                <th class="px-2 py-3">Warehouse</th>
                                <th class="px-2 py-3">Available</th>
                                <th class="px-2 py-3">Delivery</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach (($taskResult['result']['items'] ?? []) as $item)
                                <tr wire:key="workflow-result-{{ $item['line_item_id'] ?? $loop->index }}">
                                    <td class="px-2 py-3 font-mono text-xs">{{ $item['sku'] ?? '-' }}</td>
                                    <td class="px-2 py-3">{{ $item['quantity'] ?? '-' }}</td>
                                    <td class="px-2 py-3 font-medium">{{ $item['warehouse_name'] ?? '-' }}</td>
                                    <td class="px-2 py-3">{{ $item['available_quantity'] ?? '-' }}</td>
                                    <td class="px-2 py-3">{{ $item['delivery_days'] ?? '-' }} days</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif

    @if ($taskResult && ! empty($taskResult['debug_trace']))
        @php
            $aiTraces = collect($taskResult['debug_trace'])->where('step', 'ai_recommendation');
        @endphp

        @if ($aiTraces->isNotEmpty())
            <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
                <div>
                    <h3 class="text-xl font-semibold text-slate-900">AI decision context</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        The complete returned AI message and every warehouse considered for each Line Item.
                    </p>
                </div>

                <div class="mt-6 space-y-6">
                    @foreach ($aiTraces as $trace)
                        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <div class="flex flex-wrap items-baseline justify-between gap-3">
                                <h4 class="font-semibold text-slate-900">
                                    SKU {{ $trace['data']['sku'] ?? '-' }}
                                    <span class="font-normal text-slate-500">/ requested {{ $trace['data']['requested_qty'] ?? '-' }}</span>
                                </h4>
                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">Selected: {{ $trace['data']['warehouse_name'] ?? $trace['data']['warehouse_id'] ?? '-' }}</span>
                            </div>

                            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                                <div>
                                    <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">
                                        AI message
                                    </p>
                                    <pre class="mt-2 min-h-24 rounded-xl bg-slate-900 p-4 text-sm leading-6 whitespace-pre-wrap text-slate-100">{{ $trace['data']['ai_message'] ?? '-' }}</pre>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">
                                        Validated reason
                                    </p>
                                    <p class="mt-2 min-h-24 rounded-xl bg-white p-4 text-sm leading-6 text-slate-700 ring-1 ring-slate-200">
                                        {{ $trace['data']['reason'] ?? '-' }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5 overflow-x-auto">
                                <table class="min-w-full text-left text-sm">
                                    <thead class="border-b border-slate-200 text-xs tracking-wide text-slate-500 uppercase">
                                        <tr>
                                            <th class="px-2 py-3">Warehouse</th>
                                            <th class="px-2 py-3">Available</th>
                                            <th class="px-2 py-3">Distance</th>
                                            <th class="px-2 py-3">Delivery</th>
                                            <th class="px-2 py-3">Review</th>
                                            <th class="px-2 py-3">Eligible</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach (($trace['data']['warehouses'] ?? []) as $warehouse)
                                            <tr class="{{ ($warehouse['selected'] ?? false) ? 'bg-emerald-50 font-semibold' : '' }}">
                                                <td class="px-2 py-3">
                                                    {{ $warehouse['name'] ?? $warehouse['id'] ?? '-' }}
                                                </td>
                                                <td class="px-2 py-3">{{ $warehouse['available_quantity'] ?? '-' }}</td>
                                                <td class="px-2 py-3">{{ $warehouse['distance_km'] ?? '-' }} km</td>
                                                <td class="px-2 py-3">{{ $warehouse['delivery_days'] ?? '-' }} days</td>
                                                <td class="px-2 py-3">{{ $warehouse['review_score'] ?? '-' }}</td>
                                                <td class="px-2 py-3">
                                                    {{ ($warehouse['fulfils_quantity'] ?? false) ? 'Yes' : 'No' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h3 class="text-xl font-semibold text-slate-900">Redacted execution trace</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Credentials, tokens, and raw request bodies are excluded. Operational CRM and AI decision
                        details are shown above.
                    </p>
                </div>
                <span class="text-xs font-semibold tracking-wide text-slate-500 uppercase">{{ count($taskResult['debug_trace']) }} steps</span>
            </div>

            <div class="mt-6 space-y-3">
                @foreach ($taskResult['debug_trace'] as $trace)
                    <details class="rounded-2xl border border-slate-200 bg-slate-50 p-4" @if ($loop->last) open @endif>
                        <summary class="cursor-pointer list-none text-sm font-semibold text-slate-900">
                            <span class="mr-2 inline-block rounded-full bg-slate-200 px-2 py-1 font-mono text-[10px] text-slate-600 uppercase">{{ $trace['step'] ?? 'step' }}</span>
                            {{ $trace['message'] ?? 'Trace event' }}
                            <span class="float-right font-mono text-[10px] font-normal text-slate-500">{{ $trace['at'] ?? '' }}</span>
                        </summary>
                        @if (! empty($trace['data']))
                            <pre class="mt-4 overflow-x-auto rounded-xl bg-slate-900 p-4 text-xs leading-5 text-slate-100">{{ json_encode($trace['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        @endif
                    </details>
                @endforeach
            </div>

            <details class="mt-6">
                <summary class="cursor-pointer text-sm font-semibold text-slate-700">Show complete JSON dump</summary>
                <pre class="mt-3 max-h-[32rem] overflow-auto rounded-2xl bg-slate-950 p-5 text-xs leading-5 text-slate-100">{{ json_encode($taskResult['debug_trace'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </details>
        </section>
    @endif
</div>
