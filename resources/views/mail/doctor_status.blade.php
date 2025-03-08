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
        <p>Congratulations! 🎉 Your request to join as a volunteer doctor on the <strong>mesSupport Gaza</strong> platform has been <strong>approved</strong>.</p>
        <p>You can now log in to your account and start offering consultations to patients in need. Please click the link below to log in:</p>
        <p><a href="{{ '' }}" style="background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Login to Your Account</a></p>
    @else
        <p>We regret to inform you that your request to join as a volunteer doctor on the <strong>mesSupport Gaza</strong> platform has been <strong>rejected</strong>.</p>
        <p>We encourage you to apply again in the future. Please click the link below to submit a new application:</p>
        <p><a href="{{ '' }}" style="background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Submit a New Application</a></p>
    @endif

    <p>Thank you for your interest in supporting our community!<br>
    {{ config('app.name') }}</p>
</body>
</html>
