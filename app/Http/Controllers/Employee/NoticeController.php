<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Services\Backend\NoticeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NoticeController extends Controller
{
    public function __construct(
        protected readonly NoticeService $service
    ) {}

    public function index(): Response
    {
        return Inertia::render('Employee/Notice/index');
    }

    public function get(Request $request): JsonResponse
    {
        $employee = auth()->user()->employee;

        return response()->json($this->service->employeeNotices($request, $employee));
    }

    public function show(Notice $notice): Response
    {
        $notice->load(['company:id,name', 'department:id,name', 'creator:id,name']);

        return Inertia::render('Employee/Notice/show', [
            'notice' => $notice,
        ]);
    }
}
