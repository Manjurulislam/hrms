<?php
return [

    [
        'title' => 'Dashboard',
        'icon'  => 'widget-add-line-duotone',
        'to'    => 'company.dashboard',
    ],

    [
        'title' => 'Employees',
        'icon'  => 'users-group-rounded-bold-duotone',
        'to'    => 'company.employees.index',
    ],

    [
        'title' => 'Attendance',
        'icon'  => 'clock-circle-bold-duotone',
        'to'    => 'company.attendance.index',
    ],

    [
        'title'    => 'Leave',
        'icon'     => 'calendar-mark-bold-duotone',
        'children' => [
            [
                'title' => 'Leave Requests',
                'icon'  => 'checklist-minimalistic-line-duotone',
                'to'    => 'company.leave-requests.index',
            ],
            [
                'title' => 'Leave Types',
                'icon'  => 'checklist-minimalistic-line-duotone',
                'to'    => 'company.leave-types.index',
            ],
        ]
    ],

    [
        'title'    => 'Organization',
        'icon'     => 'buildings-2-bold-duotone',
        'children' => [
            [
                'title' => 'Departments',
                'icon'  => 'checklist-minimalistic-line-duotone',
                'to'    => 'company.departments.index',
            ],
            [
                'title' => 'Designations',
                'icon'  => 'checklist-minimalistic-line-duotone',
                'to'    => 'company.designations.index',
            ],
        ]
    ],

    [
        'title'    => 'Settings',
        'icon'     => 'settings-bold-duotone',
        'children' => [
            [
                'title' => 'Holidays',
                'icon'  => 'checklist-minimalistic-line-duotone',
                'to'    => 'company.holidays.index',
            ],
        ]
    ],

];
