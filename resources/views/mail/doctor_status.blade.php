<x-mail::message>
# Hello, {{  $doctorName  }} 

@if($status === 'accepted')
Congratulations! 🎉 Your request to join our platform as a doctor has been **approved**.  
You can now log in and start providing consultations.
@else
We regret to inform you that your request to join our platform as a doctor has been **rejected**.  
@endIf

<x-mail::button :url="''">
Go to Website
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

