<?php
return [

    [
        'title'    => 'Dashboard',
        'icon'     => 'widget-add-line-duotone',
        'to'       => 'dashboard',
        'hasChild' => false,
    ],

    [
        'title'    => 'Employees',
        'icon'     => 'chat-square-2-outline',
        'to'       => 'employees.index',
        'hasChild' => false,
    ],

    [
        'title'    => 'Attendance',
        'icon'     => 'chat-square-2-outline',
        'to'       => '',
        'hasChild' => false,
    ],

    [
        'title'    => 'Leave',
        'icon'     => 'chat-square-2-outline',
        'to'       => '',
        'hasChild' => false,
        'children' => [
            [
                'title' => 'Leave Requests',
                'icon'  => 'checklist-minimalistic-line-duotone',
                'to'    => '',
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
        'icon'     => 'chat-square-2-outline',
        'to'       => '',
        'hasChild' => false,
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
        'icon'     => 'settings-outline',
        'to'       => '',
        'hasChild' => true,
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
        'icon'     => 'settings-minimalistic-bold',
        'to'       => '',
        'hasChild' => true,
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
