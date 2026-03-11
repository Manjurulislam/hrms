<?php

namespace App\Services\Backend;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    private const CACHE_KEY = 'app_settings';
    private const CACHE_TTL = 3600;

    public static function defaults(): array
    {
        return [
            'general' => [
                'app_name'        => ['value' => 'HRMS', 'type' => 'string', 'label' => 'Application Name'],
                'timezone'        => ['value' => 'Asia/Dhaka', 'type' => 'string', 'label' => 'Timezone'],
                'date_format'     => ['value' => 'd M Y', 'type' => 'string', 'label' => 'Date Format'],
                'time_format'     => ['value' => 'h:i A', 'type' => 'string', 'label' => 'Time Format'],
                'currency'        => ['value' => 'BDT', 'type' => 'string', 'label' => 'Currency'],
                'currency_symbol' => ['value' => '৳', 'type' => 'string', 'label' => 'Currency Symbol'],
            ],
            'notification' => [
                'send_late_notification'    => ['value' => '1', 'type' => 'boolean', 'label' => 'Late Arrival Notification'],
                'send_absence_notification' => ['value' => '1', 'type' => 'boolean', 'label' => 'Absence Notification'],
                'send_leave_notification'   => ['value' => '1', 'type' => 'boolean', 'label' => 'Leave Request Notification'],
                'notify_on_approval'        => ['value' => '1', 'type' => 'boolean', 'label' => 'Approval Notification'],
                'notify_on_rejection'       => ['value' => '1', 'type' => 'boolean', 'label' => 'Rejection Notification'],
            ],
        ];
    }

    public function getAll(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $saved = Setting::all()->groupBy('group')->map(function ($items) {
                return $items->pluck('value', 'key')->toArray();
            })->toArray();

            $result = [];
            foreach (self::defaults() as $group => $keys) {
                foreach ($keys as $key => $meta) {
                    $result[$group][$key] = $saved[$group][$key] ?? $meta['value'];
                }
            }

            return $result;
        });
    }

    public function getGroup(string $group): array
    {
        $all = $this->getAll();
        return $all[$group] ?? [];
    }

    public function get(string $group, string $key, $default = null)
    {
        $all = $this->getAll();
        return $all[$group][$key] ?? $default;
    }

    public function updateGroup(string $group, array $values): void
    {
        $defaults = self::defaults()[$group] ?? [];

        foreach ($values as $key => $value) {
            if (!isset($defaults[$key])) {
                continue;
            }

            $type = $defaults[$key]['type'];

            if ($type === 'boolean') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            }

            Setting::updateOrCreate(
                ['group' => $group, 'key' => $key],
                ['value' => (string) $value, 'type' => $type]
            );
        }

        Cache::forget(self::CACHE_KEY);
    }

    public function getLogoUrl(): ?string
    {
        $setting = Setting::where('group', 'general')->where('key', 'logo')->first();

        return $setting?->getFirstMediaUrl('logo') ?: null;
    }

    public function getTimezones(): array
    {
        return array_map(fn($tz) => ['title' => $tz, 'value' => $tz], timezone_identifiers_list());
    }

    public function uploadLogo($request): string
    {
        $setting = Setting::firstOrCreate(
            ['group' => 'general', 'key' => 'logo'],
            ['value' => '', 'type' => 'string']
        );

        $setting->addMediaFromRequest('logo')->toMediaCollection('logo');

        return $setting->getFirstMediaUrl('logo');
    }

    public function removeLogo(): void
    {
        $setting = Setting::where('group', 'general')->where('key', 'logo')->first();
        $setting?->clearMediaCollection('logo');
    }

    public function getSettingsWithMeta(): array
    {
        $saved = $this->getAll();
        $defaults = self::defaults();
        $result = [];

        foreach ($defaults as $group => $keys) {
            foreach ($keys as $key => $meta) {
                $result[$group][$key] = [
                    'value' => $saved[$group][$key] ?? $meta['value'],
                    'type'  => $meta['type'],
                    'label' => $meta['label'],
                ];
            }
        }

        return $result;
    }
}
