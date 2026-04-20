<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Nunito', sans-serif;
            -webkit-font-smoothing: antialiased;
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .email-body {
            background-color: #f4f5f6;
            padding: 40px 0;
        }

        .content {
            display: block;
            margin: 0 auto;
            max-width: 600px;
            width: 100%;
            padding: 0 20px;
        }

        .main {
            background: #ffffff;
            width: 100%;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0px 4px 20px rgba(15, 23, 42, 0.06);
        }

        .header {
            background-color: #ffffff;
            padding: 24px;
            text-align: center;
            border-bottom: 1px solid #e8e8ef;
        }

        .header img {
            width: 160px;
            height: auto;
        }

        .wrapper {
            box-sizing: border-box;
            padding: 40px 32px;
        }

        h1 {
            font-size: 22px;
            font-weight: 700;
            color: #344767;
            margin: 0 0 8px 0;
        }

        h2 {
            font-size: 17px;
            font-weight: 700;
            color: #344767;
            margin: 0 0 12px 0;
        }

        p {
            font-size: 15px;
            line-height: 1.6;
            font-weight: 400;
            color: #555770;
            margin: 0 0 16px 0;
        }

        .btn-primary {
            display: inline-block;
            background-color: #344767;
            color: #ffffff !important;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            padding: 14px 40px;
            border-radius: 6px;
            margin: 8px 0 24px 0;
        }

        .btn-primary:hover {
            background-color: #2b3a57;
        }

        .panel {
            background-color: #f8f9fb;
            border-radius: 6px;
            padding: 20px 24px;
            margin: 16px 0;
        }

        .panel p {
            margin: 0 0 6px 0;
        }

        .panel p:last-child {
            margin: 0;
        }

        .divider {
            border: none;
            border-top: 1px solid #e8e8ef;
            margin: 24px 0;
        }

        .note {
            font-size: 13px;
            color: #888a9e;
            line-height: 1.5;
        }

        .link-text {
            font-size: 13px;
            color: #888a9e;
            word-break: break-all;
        }

        .link-text a {
            color: #344767;
            text-decoration: none;
        }

        .footer {
            text-align: center;
            padding: 24px 32px;
        }

        .footer p {
            font-size: 13px;
            color: #a0a0b0;
            margin: 0;
        }
    </style>
</head>
<body>
<div class="email-body">
    <div class="content">
        <div class="main">
            <div class="header">
                <img src="{{ asset('assets/images/logos/logo.png') }}" alt="{{ config('app.name') }}">
            </div>

            <div class="wrapper">
                @yield('body')
            </div>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</div>
</body>
</html>
