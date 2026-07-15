<?php

namespace App\Console\Commands;

use App\Models\AttendanceSummary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateAttendanceSummaries extends Command
{
    protected $signature = 'attendance:recalculate
        {--employee= : Only recalculate this employee id}
        {--from= : Start date (Y-m-d), inclusive}
        {--to= : End date (Y-m-d), inclusive}
        {--dry-run : Show what would change without writing anything}';

    protected $description = 'Recalculate attendance summaries (status, hours, late, overtime) from their sessions';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $query = AttendanceSummary::query()->with('employee.company');

        if ($employee = $this->option('employee')) {
            $query->where('employee_id', $employee);
        }
        if ($from = $this->option('from')) {
            $query->whereDate('attendance_date', '>=', $from);
        }
        if ($to = $this->option('to')) {
            $query->whereDate('attendance_date', '<=', $to);
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No attendance summaries matched.');

            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Recalculating {$total} attendance summar(y/ies)...");
        $this->line('Before: ' . $this->distribution(clone $query));

        if ($dryRun) {
            DB::beginTransaction();
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $count   = 0;
        $changes = [];

        $query->chunkById(200, function ($rows) use (&$count, &$changes, $bar) {
            foreach ($rows as $summary) {
                $before = $this->statusLabel($summary->status);
                $summary->recalculate();
                $after = $this->statusLabel($summary->status);

                if ($before !== $after) {
                    $changes[] = sprintf(
                        '%s emp%s  %s -> %s',
                        $summary->attendance_date->toDateString(),
                        $summary->employee_id,
                        $before,
                        $after
                    );
                }

                $count++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->line('After : ' . $this->distribution(clone $query));

        $this->newLine();
        $this->info(count($changes) . ' row(s) would change status' . (count($changes) ? ':' : '.'));
        foreach (array_slice($changes, 0, 50) as $line) {
            $this->line('  ' . $line);
        }
        if (count($changes) > 50) {
            $this->line('  ... and ' . (count($changes) - 50) . ' more');
        }

        $this->newLine();

        if ($dryRun) {
            DB::rollBack();
            $this->warn("[DRY RUN] Rolled back — no data written. {$count} row(s) inspected.");
        } else {
            $this->info("Done. Recalculated {$count} row(s).");
        }

        return self::SUCCESS;
    }

    private function statusLabel($status): string
    {
        return $status instanceof \BackedEnum ? $status->value : (string) $status;
    }

    private function distribution($query): string
    {
        return $query
            ->selectRaw('status, count(*) as c')
            ->reorder()
            ->groupBy('status')
            ->pluck('c', 'status')
            ->map(fn ($c, $status) => "{$status}={$c}")
            ->implode(', ');
    }
}
