<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f3f4f6;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: #374151;
            line-height: 1.6;
        }
        .wrapper {
            max-width: 600px;
            margin: 0 auto;
            padding: 24px 16px;
        }
        .header {
            text-align: center;
            padding: 24px;
        }
        .header a {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            text-decoration: none;
            letter-spacing: -0.025em;
        }
        .content {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .content h1, .content h2, .content h3 {
            color: #111827;
            margin-top: 0;
        }
        .content p {
            margin: 0 0 16px;
            color: #4b5563;
        }
        .content a {
            color: #4f46e5;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #4f46e5;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            margin: 8px 0;
        }
        .footer {
            text-align: center;
            padding: 24px;
            font-size: 13px;
            color: #9ca3af;
        }
        .footer a {
            color: #6b7280;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <a href="{{ config('app.url') }}">{{ config('app.name') }}</a>
        </div>
        <div class="content">
            {!! $body !!}
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p><a href="{{ config('app.url') }}">{{ config('app.url') }}</a></p>
        </div>
    </div>
</body>
</html>
