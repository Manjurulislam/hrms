<?php
return [

    [
        'title' => 'Dashboard',
        'icon'  => 'widget-add-line-duotone',
        'to'    => 'dashboard',
    ],

    [
        'title' => 'Attendance',
        'icon'  => 'clock-circle-bold-duotone',
        'to'    => 'emp-attendance.index',
    ],

    [
        'title' => 'Notice Board',
        'icon'  => 'clipboard-text-bold-duotone',
        'to'    => 'emp-notices.index',
    ],

    [
        'title'    => 'Leave',
        'icon'     => 'calendar-mark-bold-duotone',
        'children' => [
            [
                'title' => 'My Leaves',
                'icon'  => 'checklist-minimalistic-line-duotone',
                'to'    => 'emp-leave.index',
            ],
            [
                'title' => 'Pending Approvals',
                'icon'  => 'checklist-minimalistic-line-duotone',
                'to'    => 'emp-leave.approvals',
            ],
        ]
    ],

];
