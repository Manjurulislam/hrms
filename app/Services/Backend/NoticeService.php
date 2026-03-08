<?php

namespace App\Services\Backend;

use App\Models\Notice;
use App\Traits\PaginateQuery;
use Illuminate\Http\Request;

class NoticeService
{
    use PaginateQuery;

    public function __construct(
        protected readonly SharedService $shared
    ) {}

    public function list(Request $request): array
    {
        $query = Notice::query()
            ->with(['company:id,name', 'department:id,name', 'creator:id,name'])
            ->orderBy('created_at', 'desc');

        $search    = $request->input('search');
        $companyId = $request->input('company_id');
        $status    = $request->input('status');

        $query->when(filled($search), fn($q) => $q->where('title', 'like', "%{$search}%"));
        $query->when(filled($companyId), fn($q) => $q->where('company_id', $companyId));
        $query->when($status !== null && $status !== '', fn($q) => $q->where('status', $status));

        return $this->paginateOrFetchAll($query, $request->integer('per_page', 10));
    }

    public function create(array $data): Notice
    {
        $data['created_by'] = auth()->id();

        return Notice::create($data);
    }

    public function update(Notice $notice, array $data): Notice
    {
        $notice->update($data);

        return $notice;
    }

    public function delete(Notice $notice): bool
    {
        return $notice->delete();
    }

    public function toggle(Notice $notice): bool
    {
        $notice->update(['status' => !$notice->status]);

        return $notice->status;
    }

    public function formData(?Notice $notice = null): array
    {
        $data = [
            'companies'   => $this->shared->companies(),
            'departments' => $this->shared->departments(),
        ];

        if ($notice) {
            $notice->load(['company:id,name', 'department:id,name']);
            $data['item'] = $notice;
        }

        return $data;
    }

    public function employeeNotices(Request $request, $employee): array
    {
        $query = Notice::query()
            ->with(['company:id,name', 'department:id,name', 'creator:id,name'])
            ->active()
            ->published()
            ->notExpired()
            ->where('company_id', $employee->company_id)
            ->where(function ($q) use ($employee) {
                $q->whereNull('department_id')
                  ->orWhere('department_id', $employee->department_id);
            })
            ->orderBy('published_at', 'desc');

        $search = $request->input('search');
        $query->when(filled($search), fn($q) => $q->where('title', 'like', "%{$search}%"));

        return $this->paginateOrFetchAll($query, $request->integer('per_page', 10));
    }
}
