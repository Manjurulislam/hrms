<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\Backend\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct(
        protected readonly SettingService $service,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Backend/Settings/index', [
            'settings'  => $this->service->getSettingsWithMeta(),
            'timezones' => $this->service->getTimezones(),
            'logoUrl'   => $this->service->getLogoUrl(),
        ]);
    }

    public function update(Request $request, string $group): RedirectResponse
    {
        if (!isset(SettingService::defaults()[$group])) {
            return back()->withErrors(['error' => 'Invalid settings group.']);
        }

        try {
            $this->service->updateGroup($group, $request->all());
            return back()->with('success', ucfirst($group) . ' settings updated successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update settings.']);
        }
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ]);

        try {
            $logoUrl = $this->service->uploadLogo($request);
            return response()->json(['message' => 'Logo uploaded successfully.', 'logoUrl' => $logoUrl]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to upload logo.'], 500);
        }
    }

    public function removeLogo(): JsonResponse
    {
        try {
            $this->service->removeLogo();
            return response()->json(['message' => 'Logo removed successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to remove logo.'], 500);
        }
    }
}
