<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Ticket Created - {{ $ticket->ticket_no }}</title>
    <style type="text/css">
        /* Reset styles */
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f4f9; color: #333333; }
        table, td { border-collapse: collapse; }
        a { color: #2563eb; text-decoration: none; }
        a:hover { text-decoration: underline; }

        /* Main container */
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 32px 40px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }

        /* Content */
        .content {
            padding: 32px 40px;
        }
        .greeting {
            font-size: 18px;
            margin: 0 0 20px 0;
            color: #1f2937;
        }
        .info-box {
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 24px;
            margin: 24px 0;
            border: 1px solid #e2e8f0;
        }
        .info-row {
            display: flex;
            margin-bottom: 12px;
            font-size: 15px;
        }
        .info-label {
            width: 140px;
            font-weight: 600;
            color: #4b5563;
        }
        .info-value {
            flex: 1;
            color: #1f2937;
        }

        /* Footer */
        .footer {
            background-color: #f8fafc;
            padding: 24px 40px;
            text-align: center;
            font-size: 14px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }
        .button {
            display: inline-block;
            background-color: #2563eb;
            color: white !important;
            padding: 12px 28px;
            border-radius: 6px;
            font-weight: 500;
            margin: 20px 0;
            text-decoration: none !important;
        }
        .button:hover {
            background-color: #1d4ed8;
        }

        /* Responsive */
        @media only screen and (max-width: 600px) {
            .content, .header, .footer { padding: 24px 20px !important; }
            .info-row { flex-direction: column; }
            .info-label { width: 100%; margin-bottom: 4px; }
        }
    </style>
</head>
<body>

<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
        <td align="center" style="padding: 40px 20px; background-color: #f4f4f9;">
            <table class="email-wrapper" role="presentation">
                <!-- Header -->
                <tr>
                    <td class="header">
                        <h1>New Support Ticket Created</h1>
                    </td>
                </tr>

                <!-- Content -->
                <tr>
                    <td class="content">
                        <p class="greeting">Hello {{ $ticket->customer->name ?? 'there' }},</p>
                        
                        <p>A new support ticket has been created with the following details:</p>
                        <div class="info-box">
                            <div class="info-row">
                                <div class="info-label">Customer Name:</div>
                                <div class="info-value">
                                    <strong>{{ $ticket->customer->name }}</strong>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Ticket Name:</div>
                                <div class="info-value">{{ $ticket->ticket_name }}</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Priority:</div>
                                <div class="info-value">
                                    <strong style="color: {{ match($ticket->priority) {
                                        'Urgent' => '#dc2626',
                                        'High'   => '#ea580c',
                                        'Medium' => '#d97706',
                                        'Low'    => '#059669',
                                        default  => '#6b7280'
                                    } }};">{{ $ticket->priority }}</strong>
                                </div>
                            </div>

                            <div class="info-row">
                                <div class="info-label">Current Status:</div>
                                <div class="info-value">
                                    <strong>{{ $ticket->status }}</strong>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Created At:</div>
                                <div class="info-value">
                                    {{ $ticket->created_at?->format('d M, Y h:i A') }}
                                </div>
                            </div>
                        </div>

                        <p style="margin: 24px 0;">
                            <strong>Description:</strong><br>
                            {!! nl2br(e($ticket->description ?? 'No description provided')) !!}
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>