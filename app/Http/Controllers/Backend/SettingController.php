<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
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
        $logoSetting = Setting::where('group', 'general')->where('key', 'logo')->first();

        return Inertia::render('Backend/Settings/index', [
            'settings'  => $this->service->getSettingsWithMeta(),
            'timezones' => $this->getTimezones(),
            'logoUrl'   => $logoSetting?->getFirstMediaUrl('logo') ?: null,
        ]);
    }

    public function update(Request $request, string $group): RedirectResponse
    {
        $defaults = SettingService::defaults();

        if (!isset($defaults[$group])) {
            return back()->withErrors(['error' => 'Invalid settings group.']);
        }

        $this->service->updateGroup($group, $request->all());

        return back()->with('success', ucfirst($group) . ' settings updated successfully.');
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ]);

        $setting = Setting::firstOrCreate(
            ['group' => 'general', 'key' => 'logo'],
            ['value' => '', 'type' => 'string']
        );

        $setting->addMediaFromRequest('logo')->toMediaCollection('logo');

        return response()->json([
            'message' => 'Logo uploaded successfully.',
            'logoUrl' => $setting->getFirstMediaUrl('logo'),
        ]);
    }

    public function removeLogo(): JsonResponse
    {
        $setting = Setting::where('group', 'general')->where('key', 'logo')->first();
        $setting?->clearMediaCollection('logo');

        return response()->json(['message' => 'Logo removed successfully.']);
    }

    private function getTimezones(): array
    {
        $timezones = [];
        foreach (timezone_identifiers_list() as $tz) {
            $timezones[] = ['title' => $tz, 'value' => $tz];
        }
        return $timezones;
    }
}
