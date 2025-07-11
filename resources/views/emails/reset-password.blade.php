<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reset Your Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #123524;
            color: white !important;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 0 0 5px 5px;
            border: 1px solid #ddd;
            border-top: none;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #777;
        }
        .btn {
            display: inline-block;
            background-color: #3E7B27;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            margin-top: 15px;
        }
        h1, h2 {
            color: #123524;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="color: #fff;">HalalLink</h1>
        </div>
        <div class="content">
            <h2>Reset Your Password</h2>
            <p>Hello {{ $user->fullname }},</p>
            <p>You are receiving this email because we received a password reset request for your account.</p>
            <p>Please click the button below to reset your password:</p>
            <p style="text-align: center;">
                <a href="{{ url('/reset-password?token=' . $token . '&email=' . urlencode($user->email)) }}" class="btn">Reset Password</a>
            </p>
            <p>If you did not request a password reset, no further action is required.</p>
            <p>This password reset link will expire in 60 minutes.</p>
            <p>Thank you for using HalalLink!</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} HalalLink. All rights reserved.</p>
            <p>This is an automated email, please do not reply.</p>
        </div>
    </div>
</body>
</html>