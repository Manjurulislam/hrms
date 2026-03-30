@extends('emails.layout')

@section('title', 'Leave Request Rejected')

@section('body')
    <h1>Leave Request Rejected</h1>
    <p>Hello {{ $recipientName }},</p>
    <p>The following leave request has been <strong>rejected</strong>.</p>

    <div class="panel">
        <p><strong>Employee:</strong> {{ $employeeName }}</p>
        <p><strong>Leave Type:</strong> {{ $leaveType }}</p>
        <p><strong>Start Date:</strong> {{ $startDate }}</p>
        <p><strong>End Date:</strong> {{ $endDate }}</p>
        <p><strong>Total Days:</strong> {{ $totalDays }}</p>
    </div>

    @if($remarks)
        <p><strong>Reason for rejection:</strong></p>
        <div class="panel">
            <p>{{ $remarks }}</p>
        </div>
    @endif

    <hr class="divider">

    <p class="note">
        If you have any questions, please contact your manager or HR department.
    </p>
@endsection
