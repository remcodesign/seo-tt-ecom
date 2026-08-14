<?php

declare(strict_types=1);

namespace App\Livewire\Admin\HubSpot;

use App\Enums\RoleLabel;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.layouts.admin')]
class Logs extends Component
{
    private const string LOG_TIMEZONE = 'Europe/Amsterdam';

    /** @var array<int, string> */
    private const array LOG_LEVELS = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

    public string $logType = 'all';

    public string $search = '';

    public string $dateFilter = 'today';

    public string $logGroup = 'normal';

    /** @var array<int, string> */
    public array $enabledLogLevels = self::LOG_LEVELS;

    public function mount(): void
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user || $user->role_label !== RoleLabel::admin) {
            $this->redirectRoute('admin.login');
        }
    }

    public function refreshLogs(): void {}

    /** @return array<int, string> */
    public function logLevels(): array
    {
        return self::LOG_LEVELS;
    }

    public function toggleLogLevel(string $level): void
    {
        if (! collect(self::LOG_LEVELS)->containsStrict($level)) {
            return;
        }

        if (collect($this->enabledLogLevels)->containsStrict($level)) {
            $this->enabledLogLevels = collect($this->enabledLogLevels)
                ->reject(fn (string $enabledLevel): bool => $enabledLevel === $level)
                ->values()
                ->all();

            return;
        }

        $this->enabledLogLevels[] = $level;
        $this->enabledLogLevels = collect(self::LOG_LEVELS)
            ->intersect($this->enabledLogLevels)
            ->values()
            ->all();
    }

    public function clearLogLevels(): void
    {
        $this->enabledLogLevels = [];
    }

    public function resetFilters(): void
    {
        $this->logType = 'all';
        $this->search = '';
        $this->dateFilter = 'today';
        $this->logGroup = 'normal';
        $this->enabledLogLevels = self::LOG_LEVELS;
    }

    /** @return array<string, string> */
    public function dateFilters(): array
    {
        return [
            'all'          => 'All dates',
            'today'        => 'Today',
            'yesterday'    => 'Yesterday',
            'this_week'    => 'This week',
            'this_month'   => 'This month',
            'last_7_days'  => 'Last 7 days',
            'last_30_days' => 'Last 30 days',
        ];
    }

    /**
     * @return array{days: array<int, array{label: string, count: int}>, hours: array<int, array{label: string, count: int}>}
     */
    private function chartData(): array
    {
        $days = [];
        $hours = array_fill(0, 24, 0);

        foreach ($this->entries(true, false) as $entry) {
            $day = $entry['date']->format('Y-m-d');
            $days[$day] = ($days[$day] ?? 0) + 1;
            $hours[(int) $entry['date']->format('G')]++;
        }

        ksort($days);

        return [
            'days' => collect($days)->map(fn (int $count, string $day): array => [
                'label' => CarbonImmutable::parse($day)->format('M j'),
                'count' => $count,
            ])->values()->all(),
            'hours' => collect($hours)->map(fn (int $count, int $hour): array => [
                'label' => str_pad((string) $hour, 2, '0', STR_PAD_LEFT),
                'count' => $count,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<int, array{source: string, file: string, line: string, date: CarbonImmutable, level: string}>
     */
    private function entries(bool $filterByGroup = true, bool $limit = true, bool $filterByLevel = true): array
    {
        $sources = $this->logType === 'all' ? ['hubspot', 'ai'] : [$this->logType];
        $entries = [];

        foreach ($sources as $source) {
            $paths = glob(storage_path('logs/'.$source.'*.log')) ?: [];

            foreach ($paths as $path) {
                $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

                foreach ($lines as $line) {
                    $date = $this->dateFromLine($line);
                    if (! $date instanceof CarbonImmutable) {
                        continue;
                    }

                    if (! $this->matchesDateFilter($date)) {
                        continue;
                    }

                    if ($this->search !== '' && ! str_contains(mb_strtolower($line), mb_strtolower($this->search))) {
                        continue;
                    }

                    $level = $this->logLevelFromLine($line);
                    if ($level === null) {
                        continue;
                    }

                    if ($filterByLevel && ! in_array($level, $this->enabledLogLevels, true)) {
                        continue;
                    }

                    $isTestLog = $this->isTestLog($line);

                    if ($filterByGroup && ($this->logGroup === 'test') !== $isTestLog) {
                        continue;
                    }

                    $entries[] = [
                        'source' => $source,
                        'file'   => basename($path),
                        'line'   => trim($line),
                        'date'   => $date,
                        'level'  => $level,
                    ];
                }
            }
        }

        usort($entries, function (array $first, array $second): int {
            $dateComparison = $second['date']->getTimestamp() <=> $first['date']->getTimestamp();

            return $dateComparison !== 0 ? $dateComparison : $first['source'] <=> $second['source'];
        });

        return $limit ? array_slice($entries, 0, 200) : $entries;
    }

    /** @return array{normal: int, test: int} */
    private function groupCounts(): array
    {
        $counts = ['normal' => 0, 'test' => 0];

        foreach ($this->entries(false, false) as $entry) {
            $counts[$this->isTestLog($entry['line']) ? 'test' : 'normal']++;
        }

        return $counts;
    }

    /** @return array<string, int> */
    private function levelCounts(): array
    {
        $counts = array_fill_keys(self::LOG_LEVELS, 0);

        foreach ($this->entries(true, false, false) as $entry) {
            $counts[$entry['level']]++;
        }

        return $counts;
    }

    private function isTestLog(string $line): bool
    {
        return str_contains($line, '] testing.');
    }

    private function logLevelFromLine(string $line): ?string
    {
        $header = explode(' ', trim(substr($line, 21)), 2)[0];
        $level = str($header)->afterLast('.')->before(':')->lower()->toString();

        return in_array($level, self::LOG_LEVELS, true) ? $level : null;
    }

    private function dateFromLine(string $line): ?CarbonImmutable
    {
        $date = substr($line, 1, 19);

        if (! str_starts_with($line, '[') || substr($line, 20, 2) !== '] ') {
            return null;
        }

        $parsedDate = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $date, 'UTC');

        return $parsedDate?->setTimezone(self::LOG_TIMEZONE);
    }

    private function matchesDateFilter(CarbonImmutable $date): bool
    {
        $now = CarbonImmutable::now(self::LOG_TIMEZONE);

        return match ($this->dateFilter) {
            'today'        => $date->isSameDay($now),
            'yesterday'    => $date->isSameDay($now->subDay()),
            'this_week'    => $date->betweenIncluded($now->startOfWeek(), $now),
            'this_month'   => $date->betweenIncluded($now->startOfMonth(), $now),
            'last_7_days'  => $date->greaterThanOrEqualTo($now->subDays(7)),
            'last_30_days' => $date->greaterThanOrEqualTo($now->subDays(30)),
            default        => true,
        };
    }

    public function render(): View
    {
        $groupCounts = $this->groupCounts();
        $levelCounts = $this->levelCounts();

        return view('livewire.admin.hubspot.logs', [
            'entries'     => $this->entries(),
            'groupCounts' => $groupCounts,
            'levelCounts' => $levelCounts,
            'chartData'   => $this->chartData(),
        ]);
    }
}
