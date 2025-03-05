<?php

// namespace App\Notifications;

// use Illuminate\Bus\Queueable;
// use Illuminate\Notifications\Notification;
// use NotificationChannels\Fcm\FcmChannel;
// use Kreait\Firebase\Messaging\AndroidConfig;
// use Kreait\Firebase\Messaging\CloudMessage;
// use Kreait\Laravel\Firebase\Facades\Firebase;
// use NotificationChannels\Fcm\FcmMessage;

// class ReviewDoctorRequestNotification extends Notification
// {
//     use Queueable;

//     protected $status;
//     protected $message;

//     /**
//      * Create a new notification instance.
//      */
//     public function __construct(string $status)
//     {
//         $this->status = $status;
//         $this->message = $status === 'accepted'
//             ? 'Your registration request has been accepted.'
//             : 'Your registration request has been rejected.';
//     }
//     /**
//      * Get the notification's delivery channels.
//      *
//      * @return array<int, string>
//      */
//     public function via(object $notifiable): array
//     {
//         return ['fcm'];
//     }

//     /**
//      * Get the mail representation of the notification.
//      */
//     public function toFcm($notifiable)
//     {
//         return ['fcm'];
//         // $deviceToken = $notifiable->fcm_token; // Ensure the `fcm_token` is available in the notifiable model

//         // if (!$deviceToken) {
//         //     return;
//         // }
//         // return FcmMessage::create()
//         // ->setData([
//         //     'key' => 'value', // Custom data
//         // ])
//         // ->setNotification([
//         //     'title' => 'Your Notification Title',
//         //     'body' => 'Your Notification Body',
//         //     // 'image' => 'https://example.com/image.png', // Optional
//         // ]);


//         // $messaging = Firebase::messaging();

//         // $message = CloudMessage::withTarget('token', $deviceToken)
//         //     ->withNotification([
//         //         'title' => 'Registration Status',
//         //         'body'  => $this->message,
//         //     ]);

//         // $messaging->send($message);

//         // $message = new CloudMessage;
//         // $message->setNotification(new Notification('title',"Acepted $notifiable->id"));
//         // return FcmMessage::create()
//         // ->setData([
//         //     'user_id' => $notifiable->id,

//         // ])
//         // ->setNotification(\NotificationChannels\Fcm\Resources\Notification::create()
//         //     ->setTitle('MSG Reviews your request')
//         //     ->setBody("MSG Accept you , user your email & the folowing password to login 123456")
//         // )->setAndroid(
//         //     AndroidConfig::create()
//         //     ->setFcmOptions(AndroidFcmOptions::create()->setAnalysticsLabel('Analystics'))
//         //     ->setNotification(AndroidNotification::create()->setColor('000000'))
//         // );
//         // return (new FcmMessage(notification: new FcmNotification(
//         //     title: 'Account Activated',
//         //     body: 'Your account has been activated.',
//         //     image: 'http://example.com/url-to-image-here.png',
//         // )))
//         //     ->data(['data1' => 'value', 'data2' => 'value2'])
//         //     ->custom([
//         //         'android' => [
//         //             'notification' => [
//         //                 'color' => '#0A0A0A',
//         //             ],
//         //             'fcm_options' => [
//         //                 'analytics_label' => 'analytics',
//         //             ],
//         //         ],
//         //         'apns' => [
//         //             'fcm_options' => [
//         //                 'analytics_label' => 'analytics',
//         //             ],
//         //         ],
//         //     ]);
//      ;
// }
// }