<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold tracking-[0.24em] text-slate-500 uppercase">HubSpot test tools</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">Integration logs</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-600">Only the dedicated HubSpot and AI channels are shown.</p>
        </div>

        <div class="flex gap-3">
            <a
                href="{{ route('admin.hubspot.console') }}"
                class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
            >
                Open console
            </a>
            <a
                href="https://openrouter.ai/activity"
                target="_blank"
                rel="noopener noreferrer"
                class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
            >
                Open OpenRouter Activity
            </a>
            <button
                type="button"
                wire:click="refreshLogs"
                class="cursor-pointer rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-700"
            >
                Refresh
            </button>
        </div>
    </div>

    <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
        <div class="mb-5 flex flex-wrap items-center gap-2">
            <button
                type="button"
                wire:click="$set('logGroup', 'normal')"
                class="cursor-pointer rounded-2xl px-4 py-3 text-sm font-semibold transition {{ $logGroup === 'normal' ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50' }}"
            >
                Normal logs ({{ $groupCounts['normal'] }})
            </button>
            <button
                type="button"
                wire:click="$set('logGroup', 'test')"
                class="cursor-pointer rounded-2xl px-4 py-3 text-sm font-semibold transition {{ $logGroup === 'test' ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50' }}"
            >
                Test logs ({{ $groupCounts['test'] }})
            </button>
            <span class="mr-1 ml-2 text-sm font-semibold text-slate-800">Log levels</span>
            @foreach ($this->logLevels() as $level)
                <button
                    type="button"
                    wire:click="toggleLogLevel('{{ $level }}')"
                    class="cursor-pointer rounded-xl px-3 py-2 text-xs font-semibold capitalize transition {{ in_array($level, $enabledLogLevels, true) ? 'bg-slate-700 text-white' : 'border border-slate-200 bg-white text-slate-400' }}"
                >
                    {{ $level }}
                    @if ($levelCounts[$level] > 0)
                        <span class="ml-2 text-xs font-semibold text-slate-400">({{ $levelCounts[$level] }})</span>
                    @endif
                </button>
            @endforeach
            <button
                type="button"
                wire:click="clearLogLevels"
                class="cursor-pointer rounded-xl bg-rose-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-rose-700"
            >
                Clear
            </button>
        </div>

        <div class="grid gap-4 md:grid-cols-[12rem_14rem_minmax(0,1fr)]">
            <div>
                <label for="log-type" class="text-sm font-semibold text-slate-800">Channel</label>
                <select
                    id="log-type"
                    wire:model.live="logType"
                    class="mt-2 block w-full rounded-2xl border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                >
                    <option value="all">All</option>
                    <option value="hubspot">HubSpot</option>
                    <option value="ai">AI</option>
                </select>
            </div>
            <div>
                <label for="log-date-filter" class="text-sm font-semibold text-slate-800">Date</label>
                <select
                    id="log-date-filter"
                    wire:model.live="dateFilter"
                    class="mt-2 block w-full rounded-2xl border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                >
                    @foreach ($this->dateFilters() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="log-search" class="text-sm font-semibold text-slate-800">Search logs</label>
                <input
                    id="log-search"
                    type="search"
                    wire:model.live="search"
                    placeholder="status, model, customer..."
                    class="mt-2 block w-full rounded-2xl border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500"
                />
            </div>
            <div class="flex items-end">
                <button
                    type="button"
                    wire:click="resetFilters"
                    class="w-full cursor-pointer rounded-2xl border border-slate-900 bg-slate-900 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:border-slate-700 hover:bg-slate-700 focus-visible:ring-2 focus-visible:ring-slate-500 focus-visible:ring-offset-2"
                >
                    Reset filters
                </button>
            </div>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            @foreach (['days' => 'Logs per day', 'hours' => 'Logs per hour'] as $chartKey => $chartTitle)
                @php
                    $points = $chartData[$chartKey];
                    $maxCount = max([1, ...array_column($points, 'count')]);
                    $barWidth = 240 / max(1, count($points));
                @endphp
                <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-sm font-semibold text-slate-800">{{ $chartTitle }}</h2>
                        <span class="text-xs text-slate-500">{{ array_sum(array_column($points, 'count')) }} total</span>
                    </div>
                    <svg viewBox="0 0 256 112" class="mt-4 h-40 w-full" role="img" aria-label="{{ $chartTitle }}">
                        <line x1="8" y1="88" x2="248" y2="88" stroke="currentColor" class="text-slate-300" />
                        @foreach ($points as $index => $point)
                            @php
                                $height = $point['count'] > 0 ? max(4, 64 * $point['count'] / $maxCount) : 0;
                                $x = 8 + ($index * $barWidth) + 1;
                                $y = 88 - $height;
                            @endphp
                            <rect
                                x="{{ $x }}"
                                y="{{ $y }}"
                                width="{{ max(1, $barWidth - 2) }}"
                                height="{{ $height }}"
                                rx="2"
                                class="fill-slate-700"
                            >
                                <title>{{ $point['label'] }}: {{ $point['count'] }}</title>
                            </rect>
                            @if ($chartKey === 'days' || $index % 4 === 0)
                                <text
                                    x="{{ $x + max(1, $barWidth - 2) / 2 }}"
                                    y="103"
                                    text-anchor="middle"
                                    class="fill-slate-500 text-[7px]"
                                >{{ $point['label'] }}</text>
                            @endif
                        @endforeach
                    </svg>
                </div>
            @endforeach
        </div>
    </section>

    <section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold tracking-[0.2em] text-slate-500 uppercase">
                    <tr>
                        <th class="px-6 py-4">Level</th>
                        <th class="px-6 py-4">Channel</th>
                        <th class="px-6 py-4">Date</th>
                        <th class="px-6 py-4">File</th>
                        <th class="px-6 py-4">Entry</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($entries as $entry)
                        <tr wire:key="{{ $entry['source'].'-'.$loop->index }}" class="align-top">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-bold tracking-wide uppercase {{
                                        match ($entry['level']) {
                                            'emergency', 'alert', 'critical' => 'bg-red-100 text-red-800',
                                            'error'                          => 'bg-rose-100 text-rose-800',
                                            'warning'                        => 'bg-amber-100 text-amber-800',
                                            'notice'                         => 'bg-sky-100 text-sky-800',
                                            'info'                           => 'bg-emerald-100 text-emerald-800',
                                            'debug'                          => 'bg-slate-100 text-slate-700',
                                            default                          => 'bg-slate-100 text-slate-700',
                                        }
                                    }}"
                                >
                                    {{ $entry['level'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-semibold text-slate-900">{{ $entry['source'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-slate-500">
                                {{ $entry['date']->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-slate-500">{{ $entry['file'] }}</td>
                            <td class="max-w-3xl px-6 py-4 text-left font-mono text-xs wrap-break-word text-slate-700">
                                {{ $entry['line'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">
                                No HubSpot or AI log entries found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
</div>
