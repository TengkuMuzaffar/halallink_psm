<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Password Changed</title>
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
        .header h1 {
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="color: #fff;">HalalLink</h1>
        </div>
        <div class="content">
            <h2>Password Changed</h2>
            <p>Hello {{ $user->fullname }},</p>
            <p>Your password has been successfully changed. If you did not make this change, please contact our support team immediately.</p>
            <p>Thank you for using HalalLink!</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} HalalLink. All rights reserved.</p>
            <p>This is an automated email, please do not reply.</p>
        </div>
    </div>
</body>
</html>