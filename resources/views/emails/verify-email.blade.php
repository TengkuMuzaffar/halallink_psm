<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Verify Your Email</title>
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
            color: white;
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
        .button {
            display: inline-block;
            background-color: #123524;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .verification-link {
            word-break: break-all;
            color: #123524;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Verify Your Email Address</h2>
        </div>
        <div class="content">
            <p>Hello {{ $user->fullname }},</p>
            
            <p>Thank you for registering with HalalLink. Please verify your email address by clicking the button below:</p>
            
            <div style="text-align: center;">
                <a href="{{ url('/verify-email?token=' . $token . '&email=' . $user->email) }}" class="button">Verify Email Address</a>
            </div>
            
            <p>If the button doesn't work, you can copy and paste the following link into your browser:</p>
            
            <p class="verification-link">{{ url('/verify-email?token=' . $token . '&email=' . $user->email) }}</p>
            
            <p>This verification link will expire in 60 minutes.</p>
            
            <p>If you did not create an account, no further action is required.</p>
            
            <p>Regards,<br>The HalalLink Team</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} HalalLink. All rights reserved.</p>
        </div>
    </div>
</body>
</html>