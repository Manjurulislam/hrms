<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\NoticeRequest;
use App\Models\Notice;
use App\Services\Backend\NoticeService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class NoticeController extends Controller
{
    public function __construct(
        protected readonly NoticeService $service
    ) {}

    public function index(): Response
    {
        $companies = $this->service->formData()['companies'];

        return Inertia::render('Backend/Notice/index', [
            'companies'        => $companies,
            'defaultCompanyId' => $companies->first()?->id,
        ]);
    }

    public function get(Request $request): JsonResponse
    {
        return response()->json($this->service->list($request));
    }

    public function create(): Response
    {
        return Inertia::render('Backend/Notice/create', $this->service->formData());
    }

    public function store(NoticeRequest $request): RedirectResponse
    {
        try {
            $this->service->create($request->validated());

            return to_route('notices.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to create notice.']);
        }
    }

    public function edit(Notice $notice): Response
    {
        return Inertia::render('Backend/Notice/edit', $this->service->formData($notice));
    }

    public function update(NoticeRequest $request, Notice $notice): RedirectResponse
    {
        try {
            $this->service->update($notice, $request->validated());

            return to_route('notices.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to update notice.']);
        }
    }

    public function destroy(Notice $notice): RedirectResponse
    {
        try {
            $this->service->delete($notice);

            return to_route('notices.index');
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return back()->withErrors(['error' => 'Failed to delete notice.']);
        }
    }

    public function toggleStatus(Notice $notice): JsonResponse
    {
        try {
            $status = $this->service->toggle($notice);

            return response()->json(['success' => true, 'status' => $status]);
        } catch (Exception $e) {
            Log::error(__METHOD__, [$e->getMessage()]);

            return response()->json(['success' => false], 500);
        }
    }
}
