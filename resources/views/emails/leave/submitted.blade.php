@extends('emails.layout')

@section('title', 'Leave Request Submitted')

@section('body')
    <h1>Leave Request Submitted</h1>
    <p>Hello {{ $employeeName }},</p>
    <p>Your leave request has been submitted successfully and is now pending approval.</p>

    <div class="panel">
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
        You will be notified once your request has been reviewed. You can also check the status of your request from your dashboard.
    </p>
@endsection
