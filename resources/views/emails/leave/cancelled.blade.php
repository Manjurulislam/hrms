@extends('emails.layout')

@section('title', 'Leave Request Cancelled')

@section('body')
    <h1>Leave Request Cancelled</h1>
    <p>Hello {{ $recipientName }},</p>
    <p>The following leave request has been <strong>cancelled</strong> by the employee.</p>

    <div class="panel">
        <p><strong>Employee:</strong> {{ $employeeName }}</p>
        <p><strong>Leave Type:</strong> {{ $leaveType }}</p>
        <p><strong>Start Date:</strong> {{ $startDate }}</p>
        <p><strong>End Date:</strong> {{ $endDate }}</p>
        <p><strong>Total Days:</strong> {{ $totalDays }}</p>
    </div>

    <hr class="divider">

    <p class="note">
        This is an automated notification. No further action is required.
    </p>
@endsection
