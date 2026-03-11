<?php

namespace App\Enums;

enum ApproverType: string
{
    case DirectManager   = 'direct_manager';
    case DesignationLevel = 'designation_level';
    case SpecificEmployee = 'specific_employee';
    case DepartmentHead  = 'department_head';
}
