@extends('emails.layout')

@section('title', 'Leave Request Approved')

@section('body')
    <h1>Leave Request Approved</h1>
    <p>Hello {{ $recipientName }},</p>
    <p>The following leave request has been <strong>approved</strong>.</p>

    <div class="panel">
        <p><strong>Employee:</strong> {{ $employeeName }}</p>
        <p><strong>Leave Type:</strong> {{ $leaveType }}</p>
        <p><strong>Start Date:</strong> {{ $startDate }}</p>
        <p><strong>End Date:</strong> {{ $endDate }}</p>
        <p><strong>Total Days:</strong> {{ $totalDays }}</p>
    </div>

    <hr class="divider">

    <p class="note">
        This is an automated notification. The leave balance has been updated accordingly.
    </p>
@endsection
