<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Alert</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #2c3e50;
            color: white;
            padding: 25px 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            letter-spacing: 1px;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 13px;
            color: #bdc3c7;
        }
        .alert-badge {
            background-color: #e74c3c;
            color: white;
            text-align: center;
            padding: 10px;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .content {
            padding: 30px;
        }
        .content p {
            color: #555;
            font-size: 15px;
            line-height: 1.6;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .info-table tr {
            border-bottom: 1px solid #f0f0f0;
        }
        .info-table td {
            padding: 12px 10px;
            font-size: 14px;
            color: #333;
        }
        .info-table td:first-child {
            font-weight: bold;
            color: #2c3e50;
            width: 40%;
            background-color: #f9f9f9;
        }
        .warning-box {
            background-color: #fff8e1;
            border-left: 4px solid #f39c12;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 13px;
            color: #7d6608;
        }
        .footer {
            background-color: #f9f9f9;
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #aaa;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>

<div class="container">

    <div class="header">
        <h1>User Login Alert</h1>
        <p>Your system security notification</p>
    </div>

    <div class="alert-badge">
        AN User HAS LOGGED INTO THE SYSTEM
    </div>

    <div class="content">
        <p>This is an automated security alert. A staff member has successfully logged into the system. Please review the login details below:</p>

        <table class="info-table">
            <tr>
                <td>👤 Staff Name</td>
                <td>{{ $loginData['name'] }}</td>
            </tr>
            <tr>
                <td>📧 Email Address</td>
                 <td>{{ $loginData['email'] }}</td>
            </tr>
            <tr>
                <td>🏷️ Role / Designation</td>
                 <td>{{ $loginData['role'] }}</td>
            </tr>
            <tr>
                <td>📅 Login Date</td>
                <td>{{ $loginData['date'] }}</td>
            </tr>
            <tr>
                <td>🕐 Login Time</td>
                 <td>{{ $loginData['time'] }}</td>
            </tr>
            <tr>
                <td>🌐 IP Address</td>
                <td>{{ $loginData['ip'] }}</td>
            </tr>
            <tr>
                <td>💻 Device / Browser</td>
                <td>{{ $loginData['browser'] }}</td>
            </tr>
        </table>

        <div class="warning-box">
            ⚠️ <strong>Important:</strong> If this login seems suspicious or you don't recognize this activity, please take immediate action — disable the account or contact your IT security team.
        </div>

        <p>This is a system-generated email. No reply is needed.</p>
        <p>Stay secure,<br><strong>Your System Security Team</strong></p>

    </div>

    <div class="footer">
        <p>© 2026 {{ company_setting()?->name }}. All rights reserved.</p>
        <p>This email was sent automatically. Please do not reply.</p>
    </div>

</div>

</body>
</html>
