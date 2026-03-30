@extends('emails.layout')

@section('title', 'Reset Your Password')

@section('body')
    <h1>Reset Your Password</h1>
    <p>Hello {{ $name }},</p>
    <p>
        We received a request to reset the password for your account.
        Click the button below to set a new password.
    </p>

    <div style="text-align: center; margin: 32px 0;">
        <a href="{{ $url }}" class="btn-primary">Reset Password</a>
    </div>

    <p class="note">
        This link will expire in <strong>{{ $expireMinutes }} minutes</strong>.
        If you did not request a password reset, no action is needed — your account is safe.
    </p>

    <hr class="divider">

    <p class="link-text">
        If the button above doesn't work, copy and paste this link into your browser:<br>
        <a href="{{ $url }}">{{ $url }}</a>
    </p>
@endsection
