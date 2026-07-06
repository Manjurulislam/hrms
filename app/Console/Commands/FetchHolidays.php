<?php

namespace App\Console\Commands;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchHolidays extends Command
{
    protected $signature = 'holidays:fetch
        {year? : Target year (defaults to the current year)}
        {--company=1 : Company ID the holidays belong to}
        {--source=nager : Data source: nager or calendarific}
        {--dry-run : Preview the fetched holidays without writing to the database}
        {--deactivate-missing : Set status=false on existing holidays for the year that are not in the fetched set}';

    protected $description = 'Fetch Bangladesh public holidays for a year and upsert them into the holidays table';

    public function handle(): int
    {
        $year    = (int) ($this->argument('year') ?: now()->year);
        $company = (int) $this->option('company');
        $source  = strtolower((string) $this->option('source'));
        $dryRun  = (bool) $this->option('dry-run');

        $this->info("Fetching Bangladesh holidays for {$year} from '{$source}' (company #{$company})...");

        try {
            $holidays = match ($source) {
                'nager'        => $this->fetchFromNager($year),
                'calendarific' => $this->fetchFromCalendarific($year),
                default        => throw new \InvalidArgumentException("Unknown source '{$source}'. Use 'nager' or 'calendarific'."),
            };
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if (empty($holidays)) {
            $this->warn('No holidays returned by the source.');

            return self::FAILURE;
        }

        // Sort by start date for a readable preview.
        usort($holidays, fn ($a, $b) => strcmp($a['start_date'], $b['start_date']));

        $this->table(
            ['Date', 'End', 'Name'],
            array_map(fn ($h) => [$h['start_date'], $h['end_date'], $h['name']], $holidays)
        );

        if ($dryRun) {
            $this->comment('Dry run — nothing was written. Re-run without --dry-run to persist.');

            return self::SUCCESS;
        }

        $created = 0;
        $updated = 0;
        $keptNames = [];

        foreach ($holidays as $h) {
            // Match on the exact start date, not just the year: multi-day holidays
            // (e.g. several "Eid ul-Fitr Holiday" days) share a name but are distinct rows.
            $existing = Holiday::where('company_id', $company)
                ->where('name', $h['name'])
                ->whereDate('start_date', $h['start_date'])
                ->first();

            if ($existing) {
                $existing->update([
                    'description' => $h['description'] ?: $existing->description,
                    'start_date'  => $h['start_date'],
                    'end_date'    => $h['end_date'],
                    'status'      => true,
                ]);
                $updated++;
            } else {
                Holiday::create([
                    'company_id'  => $company,
                    'name'        => $h['name'],
                    'description' => $h['description'],
                    'start_date'  => $h['start_date'],
                    'end_date'    => $h['end_date'],
                    'status'      => true,
                ]);
                $created++;
            }

            $keptNames[] = $h['name'];
        }

        $deactivated = 0;
        if ($this->option('deactivate-missing')) {
            $deactivated = Holiday::where('company_id', $company)
                ->whereYear('start_date', $year)
                ->whereNotIn('name', $keptNames)
                ->update(['status' => false]);
        }

        $this->info("Done. Created: {$created}, Updated: {$updated}" .
            ($this->option('deactivate-missing') ? ", Deactivated: {$deactivated}" : ''));

        if ($source === 'nager') {
            $this->warn('Note: Nager.Date only covers fixed secular holidays. Lunar/religious ' .
                'holidays (Eid, Ashura, Puja, etc.) are NOT included — confirm those against ' .
                'the official gazette (mopa.gov.bd) or use --source=calendarific with an API key.');
        }

        return self::SUCCESS;
    }

    /**
     * Nager.Date — free, no API key. Reliable for fixed secular national holidays,
     * but does not include moon-sighting-dependent religious holidays.
     */
    private function fetchFromNager(int $year): array
    {
        $response = Http::timeout(20)->retry(2, 500)
            ->get("https://date.nager.at/api/v3/PublicHolidays/{$year}/BD");

        $response->throw();

        return collect($response->json())->map(fn ($h) => [
            'name'        => $h['name'],
            'description' => $h['localName'] ?? '',
            'start_date'  => $h['date'],
            'end_date'    => $h['date'],
        ])->all();
    }

    /**
     * Calendarific — full national list including religious holidays. Requires an API
     * key in config/services.php ('calendarific.key') / env CALENDARIFIC_API_KEY.
     */
    private function fetchFromCalendarific(int $year): array
    {
        $key = config('services.core.calendarific.key');

        if (empty($key)) {
            throw new \RuntimeException(
                'Calendarific API key missing. Set CALENDARIFIC_API_KEY in .env or ' .
                "'calendarific.key' in config/services/core.php."
            );
        }

        $response = Http::timeout(20)->retry(2, 500)->get('https://calendarific.com/api/v2/holidays', [
            'api_key' => $key,
            'country' => 'BD',
            'year'    => $year,
        ]);

        $response->throw();

        $holidays = $response->json('response.holidays') ?? [];

        return collect($holidays)
            // Keep only official national holidays; drop observances/seasons.
            ->filter(fn ($h) => collect($h['type'] ?? [])->contains(fn ($t) => str_contains(strtolower($t), 'national')))
            ->map(fn ($h) => [
                'name'        => $h['name'],
                'description' => $h['description'] ?? '',
                'start_date'  => Carbon::parse($h['date']['iso'])->toDateString(),
                'end_date'    => Carbon::parse($h['date']['iso'])->toDateString(),
            ])
            ->values()
            ->all();
    }
}
