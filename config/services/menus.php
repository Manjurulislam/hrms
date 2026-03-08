<?php
return [

    // ─── Common ─────────────────────────────────────────
    [
        'title'  => 'Dashboard',
        'icon'   => 'widget-add-line-duotone',
        'to'     => 'dashboard',
        'access' => 'all',
    ],

    // ─── Admin ──────────────────────────────────────────
    ['header' => 'Administration', 'access' => 'admin'],

    [
        'title'  => 'Employees',
        'icon'   => 'users-group-rounded-bold-duotone',
        'to'     => 'employees.index',
        'access' => 'admin',
    ],

    [
        'title'  => 'Attendance',
        'icon'   => 'clock-circle-bold-duotone',
        'to'     => 'attendance.index',
        'access' => 'admin',
    ],

    [
        'title'    => 'Leave',
        'icon'     => 'calendar-mark-bold-duotone',
        'access'   => 'admin',
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
        'title'  => 'Notice Board',
        'icon'   => 'clipboard-text-bold-duotone',
        'to'     => 'notices.index',
        'access' => 'admin',
    ],

    [
        'title'    => 'Company',
        'icon'     => 'buildings-2-bold-duotone',
        'access'   => 'admin',
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
        'access'   => 'admin',
        'children' => [
            [
                'title' => 'General Settings',
                'icon'  => 'checklist-minimalistic-line-duotone',
                'to'    => 'settings.index',
            ],
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
        'access'   => 'admin',
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

    // ─── Employee ───────────────────────────────────────
    ['header' => 'My Workspace', 'access' => 'employee'],

    [
        'title'  => 'My Attendance',
        'icon'   => 'clock-circle-bold-duotone',
        'to'     => 'emp-attendance.index',
        'access' => 'employee',
    ],

    [
        'title'  => 'Notice Board',
        'icon'   => 'clipboard-text-bold-duotone',
        'to'     => 'emp-notices.index',
        'access' => 'employee',
    ],

    [
        'title'    => 'My Leave',
        'icon'     => 'calendar-mark-bold-duotone',
        'access'   => 'employee',
        'children' => [
            [
                'title' => 'My Leaves',
                'icon'  => 'checklist-minimalistic-line-duotone',
                'to'    => 'emp-leave.index',
            ],
            [
                'title'  => 'Pending Approvals',
                'icon'   => 'checklist-minimalistic-line-duotone',
                'to'     => 'emp-leave.approvals',
                'access' => 'manager',
            ],
        ]
    ],

];
