<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold tracking-[0.24em] text-slate-500 uppercase">HubSpot test tools</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">Smart Quote console</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-600">
                Run the customer check and quote pitch from the deployed Laravel app before connecting the HubSpot card.
            </p>
        </div>

        <div class="flex gap-3">
            <a
                href="{{ route('admin.hubspot.logs') }}"
                class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
            >
                View logs
            </a>
            <button
                type="button"
                wire:click="clearResults"
                class="cursor-pointer rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-700"
            >
                Clear results
            </button>
        </div>
    </div>

    <nav class="flex gap-2 overflow-x-auto border-b border-slate-200" aria-label="HubSpot test tools">
        <button
            type="button"
            wire:click="$set('activeTab', 'quote')"
            class="cursor-pointer border-b-2 px-4 py-3 text-sm font-semibold transition {{ $activeTab === 'quote' ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-800' }}"
        >
            Quote tools
        </button>
        <button
            type="button"
            wire:click="$set('activeTab', 'warehouse')"
            class="cursor-pointer border-b-2 px-4 py-3 text-sm font-semibold transition {{ $activeTab === 'warehouse' ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-800' }}"
        >
            Inventory & warehouse AI
        </button>
        <button
            type="button"
            wire:click="$set('activeTab', 'mcp')"
            class="cursor-pointer border-b-2 px-4 py-3 text-sm font-semibold transition {{ $activeTab === 'mcp' ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-800' }}"
        >
            HubSpot MCP
        </button>
    </nav>

    @if ($activeTab === 'quote')
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Test input</h2>
                        <p class="mt-2 text-sm text-slate-600">
                            Use the same email values as the HubSpot test contacts.
                        </p>
                    </div>
                    <span class="rounded-full bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">Server side</span>
                </div>

                <div class="mt-6 space-y-5">
                    <div>
                        <label for="hubspot-email" class="text-sm font-semibold text-slate-800">Customer email</label>
                        <input
                            id="hubspot-email"
                            type="email"
                            wire:model.live="email"
                            class="mt-2 block w-full rounded-2xl border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        />
                        @error('email')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="hubspot-deal-name" class="text-sm font-semibold text-slate-800">Deal name</label>
                        <input
                            id="hubspot-deal-name"
                            type="text"
                            wire:model.live="dealName"
                            class="mt-2 block w-full rounded-2xl border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                        />
                        @error('dealName')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="hubspot-deal-amount" class="text-sm font-semibold text-slate-800"
                                >Deal amount</label>
                            <input
                                id="hubspot-deal-amount"
                                type="number"
                                min="0"
                                step="0.01"
                                wire:model.live="dealAmount"
                                class="mt-2 block w-full rounded-2xl border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            />
                            @error('dealAmount')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="hubspot-allowed-discount" class="text-sm font-semibold text-slate-800"
                                >Allowed discount</label>
                            <input
                                id="hubspot-allowed-discount"
                                type="number"
                                min="0"
                                max="100"
                                wire:model.live="allowedDiscount"
                                class="mt-2 block w-full rounded-2xl border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            />
                            @error('allowedDiscount')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <button
                            type="button"
                            wire:click="checkCustomer"
                            wire:loading.attr="disabled"
                            class="cursor-pointer rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Check customer
                        </button>
                        <button
                            type="button"
                            wire:click="generatePitch"
                            wire:loading.attr="disabled"
                            class="cursor-pointer rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Generate pitch
                        </button>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-sky-200 bg-sky-50 p-6 sm:p-8">
                <h2 class="text-xl font-semibold text-sky-950">Async workflow task (has to be tested)</h2>
                <p class="mt-2 text-sm leading-6 text-sky-900">
                    Queue an accepted task to exercise CRM reads, warehouse AI, note creation, and callback completion.
                </p>
                <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="min-w-0 flex-1">
                        <label for="hubspot-workflow-task-id" class="text-sm font-semibold text-sky-950">Task ID</label>
                        <input
                            id="hubspot-workflow-task-id"
                            type="text"
                            wire:model.live="workflowTaskId"
                            class="mt-2 block w-full rounded-2xl border-sky-300 bg-white px-4 py-3 font-mono text-xs shadow-sm focus:border-sky-500 focus:ring-sky-500"
                        />
                        @error('workflowTaskId')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button
                        type="button"
                        wire:click="queueWarehouseTask"
                        wire:loading.attr="disabled"
                        class="cursor-pointer rounded-2xl bg-sky-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-800 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Queue task
                    </button>
                </div>
                @if ($workflowTaskResult)
                    <p class="mt-4 text-sm font-semibold text-sky-900">
                        Task {{ $workflowTaskResult['task_id'] }} is {{ $workflowTaskResult['status'] }}.
                    </p>
                @endif
            </section>

            <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Runtime status</h2>
                    </div>
                    <span class="rounded-full px-3 py-2 text-xs font-semibold {{ $aiConfigured ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                        {{ $aiConfigured ? 'OpenRouter ready' : 'Fallback mode' }}
                    </span>
                </div>

                <dl class="mt-6 divide-y divide-slate-100 text-sm">
                    <div class="flex items-center justify-between gap-4 py-3">
                        <dt class="text-slate-500">Model</dt>
                        <dd class="font-medium text-slate-900">{{ $aiModel ?: 'Not configured' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 py-3">
                        <dt class="text-slate-500">Customer rule</dt>
                        <dd class="font-medium text-slate-900">vip@example.test = 15%</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 py-3">
                        <dt class="text-slate-500">Unknown rule</dt>
                        <dd class="font-medium text-slate-900">Fallback = 5%</dd>
                    </div>
                </dl>

                @if ($customerResult)
                    <div class="mt-6 rounded-2xl bg-slate-50 p-5">
                        <h3 class="font-semibold text-slate-900">Customer result</h3>
                        <dl class="mt-3 space-y-2 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">VIP</dt>
                                <dd class="font-medium">{{ $customerResult['is_vip'] ? 'Yes' : 'No' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Lifetime value</dt>
                                <dd class="font-medium">{{ $customerResult['lifetime_value'] }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Allowed discount</dt>
                                <dd class="font-medium">{{ $customerResult['allowed_discount'] }}%</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Reason</dt>
                                <dd class="text-right font-medium">{{ $customerResult['reason'] }}</dd>
                            </div>
                        </dl>
                    </div>
                @endif

                @if ($pitchResult)
                    <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                        <div class="flex items-center justify-between gap-4">
                            <h3 class="font-semibold text-emerald-950">Quote pitch</h3>
                            <span class="text-xs font-semibold text-emerald-700">{{ $pitchResult['provider'] }}</span>
                        </div>
                        <p class="mt-3 text-sm leading-6 text-emerald-950">{{ $pitchResult['text'] }}</p>
                    </div>
                @endif

                @if ($errorMessage !== '')
                    <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                        {{ $errorMessage }}
                    </div>
                @endif
            </section>
        </div>
    @elseif ($activeTab === 'warehouse')
        @livewire(\App\Livewire\Admin\HubSpot\WarehouseWorkflow::class)
    @else
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
            <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
                <p class="text-sm font-semibold tracking-[0.24em] text-slate-500 uppercase">
                    Official HubSpot connection (NOT YET IMPLEMENTED)
                </p>
                <h2 class="mt-3 text-2xl font-semibold text-slate-900">Remote CRM MCP</h2>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                    This tab documents the real connection boundary. Laravel must authenticate through HubSpot OAuth
                    with PKCE before it can read Deal or product context.
                </p>

                <dl class="mt-8 divide-y divide-slate-100 text-sm">
                    <div class="flex flex-col gap-2 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <dt class="text-slate-500">Remote MCP endpoint</dt>
                        <dd class="font-mono text-xs text-slate-900">https://mcp.hubspot.com</dd>
                    </div>
                    <div class="flex flex-col gap-2 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <dt class="text-slate-500">Authentication</dt>
                        <dd class="font-medium text-amber-700">OAuth 2.1 + PKCE required</dd>
                    </div>
                    <div class="flex flex-col gap-2 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <dt class="text-slate-500">Current application state</dt>
                        <dd class="font-medium text-slate-900">Read-only setup panel</dd>
                    </div>
                </dl>

                <div class="mt-6 rounded-2xl border border-sky-200 bg-sky-50 p-5 text-sm leading-6 text-sky-950">
                    Create a HubSpot MCP auth app, register the Laravel callback URL, complete OAuth/PKCE, and store
                    tokens per HubSpot portal before enabling live CRM reads. Do not put client secrets or refresh
                    tokens in Livewire state or browser output.
                </div>
            </section>

            <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
                <h2 class="text-xl font-semibold text-slate-900">Approved read tools</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Start with CRM context only. Warehouse truth stays behind the Laravel inventory boundary.
                </p>
                <ul class="mt-6 space-y-3 text-sm text-slate-700">
                    <li class="rounded-xl bg-slate-50 px-4 py-3 font-mono">get_user_details</li>
                    <li class="rounded-xl bg-slate-50 px-4 py-3 font-mono">search_crm_objects</li>
                    <li class="rounded-xl bg-slate-50 px-4 py-3 font-mono">get_crm_objects</li>
                    <li class="rounded-xl bg-slate-50 px-4 py-3 font-mono">search_properties</li>
                    <li class="rounded-xl bg-slate-50 px-4 py-3 font-mono">get_properties</li>
                </ul>
                <a
                    href="{{ route('admin.hubspot.logs') }}"
                    class="mt-6 inline-flex rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50"
                >
                    Review MCP logs
                </a>
            </section>
        </div>
    @endif
</div>
