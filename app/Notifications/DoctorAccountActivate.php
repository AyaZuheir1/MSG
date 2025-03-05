<?php

// namespace App\Notifications;

// use Illuminate\Notifications\Messages\MailMessage;
// use Illuminate\Notifications\Notification;
// use NotificationChannels\Fcm\FcmChannel;
// use NotificationChannels\Fcm\FcmMessage;
// use NotificationChannels\Fcm\Resources\Notification as FcmNotification;
// use Kreait\Firebase\Factory;

// class DoctorAccountActivate extends Notification
// {
//     //Channel on which will the message will be sent
//     public function via($notifiable)
//     {
//         return [FcmChannel::class];
//     }

//     public function toFcm($notifiable): FcmMessage
//     {

//         // $messaging = app('firebase.messaging');

//             $firebase = (new Factory)
//                 ->withServiceAccount(base_path('storage/app/medsg-85fd8-881fafdc81d6.json'))
//                 ->createMessaging();
            
//         return (new FcmMessage(notification: new FcmNotification(
//             title: 'Doctor Account Activated',
//             body: 'Your account has been activated as a doctor in Medical Support Gaza.',
//         )))
//             ->data(['data1' => $notifiable->id, 'data2' => 'value2'])
//             ->custom([
//                 'android' => [
//                     'notification' => [
//                         'color' => '#0A0A0A',
//                         'sound' => 'default',
//                     ],
//                     'fcm_options' => [
//                         'analytics_label' => 'analytics',
//                     ],
//                 ],
//                 // 'apns' => [
//                 //     'payload' => [
//                 //         'aps' => [
//                 //             'sound' => 'default'
//                 //         ],
//                 //     ],
//                 //     'fcm_options' => [
//                 //         'analytics_label' => 'analytics',
//                 //     ],
//                 // ],
//             ]);
//     }


//     /**
//      * Get the mail representation of the notification.
//      */
//     public function toMail(object $notifiable): MailMessage
//     {
//         return (new MailMessage)->markdown('mail.doctor-account-activate');
//     }

//     /**
//      * Get the array representation of the notification.
//      *
//      * @return array<string, mixed>
//      */
//     public function toArray(object $notifiable): array
//     {
//         return [
//             //
//         ];
//     }
// }
