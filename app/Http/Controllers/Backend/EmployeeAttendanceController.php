<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class EmployeeAttendanceController extends Controller
{
    public function index()
    {
        return Inertia::render('Employee/attendance');
    }
}
