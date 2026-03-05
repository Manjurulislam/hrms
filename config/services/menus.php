<?php
return [

    [
        'title' => 'Dashboard',
        'icon'  => 'widget-add-line-duotone',
        'to'    => 'dashboard',
    ],

    [
        'title' => 'Employees',
        'icon'  => 'users-group-rounded-bold-duotone',
        'to'    => 'employees.index',
    ],

    [
        'title' => 'Attendance',
        'icon'  => 'clock-circle-bold-duotone',
        'to'    => 'attendance.index',
    ],

    [
        'title'    => 'Leave',
        'icon'     => 'calendar-mark-bold-duotone',
        'children' => [
            [
                'title' => 'Leave Requests',
                'icon'  => 'checklist-minimalistic-line-duotone',
                'to'    => 'leave-requests.index',
            ],
            [
                'title' => 'Leave Types',
                'icon'  => 'checklist-minimalistic-line-duotone',
                'to'    => 'leave-types.index',
            ],
        ]
    ],

    [
        'title'    => 'Company',
        'icon'     => 'buildings-2-bold-duotone',
        'children' => [
            [
                'title' => 'Companies',
                'icon'  => 'checklist-minimalistic-line-duotone',
                'to'    => 'companies.index',
            ],
            [
                'title' => 'Departments',
                'icon'  => 'checklist-minimalistic-line-duotone',
                'to'    => 'departments.index',
            ],
            [
                'title' => 'Designations',
                'icon'  => 'checklist-minimalistic-line-duotone',
                'to'    => 'designations.index',
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
                'to'    => 'holidays.index',
            ],
        ]
    ],

    [
        'title'    => 'Secure',
        'icon'     => 'shield-keyhole-bold-duotone',
        'children' => [
            [
                'title' => 'Users',
                'icon'  => 'checklist-minimalistic-line-duotone',
                'to'    => 'users.index',
            ],
            [
                'title' => 'Roles',
                'icon'  => 'checklist-minimalistic-line-duotone',
                'to'    => 'roles.index',
            ]
        ]
    ],

];
