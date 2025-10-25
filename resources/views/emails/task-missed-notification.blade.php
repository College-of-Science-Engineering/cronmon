<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Task Notification</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        @if($alert->alert_type === 'recovered')
            <h2 style="color: #10b981;">✓ Task Recovered</h2>
            <p><strong>{{ $task->name }}</strong> has recovered and is checking in again.</p>
        @elseif($alert->alert_type === 'late')
            <h2 style="color: #f59e0b;">⚠ Task Running Late</h2>
            <p><strong>{{ $task->name }}</strong> is running later than expected.</p>
        @else
            <h2 style="color: #ef4444;">✗ Task Missed</h2>
            <p><strong>{{ $task->name }}</strong> has missed its scheduled run.</p>
        @endif

        <div style="background: #f3f4f6; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0;">Task Details</h3>
            <p style="margin: 5px 0;"><strong>Team:</strong> {{ $task->team->name }}</p>
            <p style="margin: 5px 0;"><strong>Schedule:</strong> {{ $task->schedule_type === 'simple' ? $task->schedule_value : 'Cron: ' . $task->schedule_value }}</p>
            <p style="margin: 5px 0;"><strong>Grace Period:</strong> {{ $task->grace_period_minutes }} minutes</p>
            @if($task->last_checked_in_at)
                <p style="margin: 5px 0;"><strong>Last Check-in:</strong> {{ $task->last_checked_in_at->format('M j, Y g:i A') }}</p>
            @else
                <p style="margin: 5px 0;"><strong>Last Check-in:</strong> Never</p>
            @endif
        </div>

        <p><strong>Alert Message:</strong></p>
        <p style="background: #fef3c7; padding: 10px; border-left: 4px solid #f59e0b; margin: 10px 0;">
            {{ $alert->message }}
        </p>

        <p style="color: #666; font-size: 14px; margin-top: 30px;">
            This is an automated notification from Cronmon. Please check your scheduled tasks.
        </p>
    </div>
</body>
</html>
