<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - Doctor Request Status</title>
</head>
<body>
    <p>Hello, {{ $doctorName }}</p>

    @if($status === 'accepted')
        <p>Congratulations! 🎉 Your request to join our platform as a doctor has been <strong>approved</strong>.</p>
        <p>You can now log in and start providing consultations.</p>
    @else
        <p>We regret to inform you that your request to join our platform as a doctor has been <strong>rejected</strong>.</p>
    @endif

    <p><a href="{{ '' }}" style="background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Go to Website</a></p>

    <p>Thanks,<br>
    {{ config('app.name') }}</p>
</body>
</html>
