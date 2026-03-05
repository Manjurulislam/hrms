<?php

namespace App\Services\Backend;

use App\Models\Holiday;
use App\Traits\PaginateQuery;
use App\Traits\QueryParams;
use Illuminate\Http\Request;

class HolidayService
{
    use PaginateQuery, QueryParams;

    public function __construct(
        protected readonly SharedService $shared
    ) {}

    public function list(Request $request): array
    {
        $query = Holiday::query()
            ->with('company:id,name')
            ->orderBy('start_date', 'desc');

        $query = $this->holidayQuery($query, $request);

        return $this->paginateOrFetchAll($query, $request->integer('per_page', 10));
    }

    public function create(array $data): Holiday
    {
        return Holiday::create($data);
    }

    public function update(Holiday $holiday, array $data): Holiday
    {
        $holiday->update($data);

        return $holiday;
    }

    public function delete(Holiday $holiday): bool
    {
        return $holiday->delete();
    }

    public function toggle(Holiday $holiday): bool
    {
        $holiday->update(['status' => !$holiday->status]);

        return $holiday->status;
    }

    public function formData(?Holiday $holiday = null): array
    {
        $data = [
            'companies' => $this->shared->companies(),
        ];

        if ($holiday) {
            $holiday->load('company:id,name');
            $data['item'] = $holiday;
        }

        return $data;
    }
}
