<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
</head>

<body
    style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; color: #333333; margin: 0; padding: 0;">

    <div
        style="max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); border: 1px solid #e1e6eb;">

        <div style="background-color: #252a0c; color: #ffffff; padding: 30px; text-align: center;">
            <h1 style="margin: 0; font-size: 24px; font-weight: 600;">Password Reset Request</h1>
        </div>

        <div style="padding: 40px 30px; line-height: 1.6;">
            <p style="margin: 0 0 20px; font-size: 16px;">We received a request to reset the password for your account.
                Please use the secure verification code below to proceed with the password reset:</p>

            <div
                style="text-align: center; margin: 30px 0; padding: 20px; background-color: #f8fafc; border: 2px dashed #e2e8f0; border-radius: 6px;">
                <div
                    style="font-size: 40px; font-weight: bold; letter-spacing: 8px; color: #252a0c; display: inline-block;">
                    {{ $code }}
                </div>
            </div>

            <p style="margin: 0 0 20px; font-size: 16px;"><strong>Note:</strong> This code is valid for <strong>15
                    minutes</strong>. If you did not request this change, please ignore this email or contact support if
                you have security concerns.</p>

            <p style="margin: 0; font-size: 16px;">Best regards,<br>The Security Team</p>
        </div>

        <div
            style="background-color: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #718096; border-top: 1px solid #e2e8f0;">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br>
            This is an automated security notification. Please do not reply directly to this email.
        </div>
    </div>

</body>

</html>
