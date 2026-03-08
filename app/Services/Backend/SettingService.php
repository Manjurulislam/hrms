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
            'attendance' => [
                'min_session_duration'     => ['value' => '1', 'type' => 'integer', 'label' => 'Min Session Duration (min)'],
                'min_session_gap'          => ['value' => '2', 'type' => 'integer', 'label' => 'Min Session Gap (min)'],
                'max_sessions_per_day'     => ['value' => '10', 'type' => 'integer', 'label' => 'Max Sessions Per Day'],
                'max_breaks_per_day'       => ['value' => '5', 'type' => 'integer', 'label' => 'Max Breaks Per Day'],
                'min_break_duration'       => ['value' => '1', 'type' => 'integer', 'label' => 'Min Break Duration (min)'],
                'max_break_duration'       => ['value' => '120', 'type' => 'integer', 'label' => 'Max Break Duration (min)'],
                'default_office_start'     => ['value' => '09:00', 'type' => 'string', 'label' => 'Default Office Start'],
                'default_office_end'       => ['value' => '18:00', 'type' => 'string', 'label' => 'Default Office End'],
                'late_grace_period'        => ['value' => '15', 'type' => 'integer', 'label' => 'Late Grace Period (min)'],
                'early_leave_grace_period' => ['value' => '15', 'type' => 'integer', 'label' => 'Early Leave Grace (min)'],
                'standard_working_hours'   => ['value' => '8', 'type' => 'integer', 'label' => 'Standard Working Hours'],
                'half_day_hours'           => ['value' => '4', 'type' => 'integer', 'label' => 'Half Day Hours'],
                'auto_close_enabled'       => ['value' => '1', 'type' => 'boolean', 'label' => 'Auto Close Sessions'],
                'auto_close_time'          => ['value' => '23:59', 'type' => 'string', 'label' => 'Auto Close Time'],
                'track_ip_address'         => ['value' => '1', 'type' => 'boolean', 'label' => 'Track IP Address'],
                'track_location'           => ['value' => '1', 'type' => 'boolean', 'label' => 'Track Location'],
            ],
            'leave' => [
                'max_leave_per_application'  => ['value' => '30', 'type' => 'integer', 'label' => 'Max Days Per Application'],
                'min_advance_days'           => ['value' => '1', 'type' => 'integer', 'label' => 'Min Advance Days'],
                'allow_half_day'             => ['value' => '1', 'type' => 'boolean', 'label' => 'Allow Half Day Leave'],
                'allow_backdated'            => ['value' => '0', 'type' => 'boolean', 'label' => 'Allow Backdated Leave'],
                'carry_forward'              => ['value' => '0', 'type' => 'boolean', 'label' => 'Carry Forward Balance'],
                'max_carry_forward_days'     => ['value' => '5', 'type' => 'integer', 'label' => 'Max Carry Forward Days'],
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
