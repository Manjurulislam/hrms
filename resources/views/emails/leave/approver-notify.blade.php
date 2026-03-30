@extends('emails.layout')

@section('title', 'New Leave Request Pending Approval')

@section('body')
    <h1>New Leave Request</h1>
    <p>Hello {{ $approverName }},</p>
    <p>A new leave request from <strong>{{ $employeeName }}</strong> requires your approval.</p>

    <div class="panel">
        <p><strong>Employee:</strong> {{ $employeeName }}</p>
        <p><strong>Leave Type:</strong> {{ $leaveType }}</p>
        <p><strong>Start Date:</strong> {{ $startDate }}</p>
        <p><strong>End Date:</strong> {{ $endDate }}</p>
        <p><strong>Total Days:</strong> {{ $totalDays }}</p>
        @if($title)
            <p><strong>Title:</strong> {{ $title }}</p>
        @endif
    </div>

    @if($notes)
        <p><strong>Reason:</strong></p>
        <p>{{ $notes }}</p>
    @endif

    <hr class="divider">

    <p class="note">
        Please log in to the system to review and take action on this request.
    </p>
@endsection
