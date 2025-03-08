<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DoctorRequestStatusMail extends Mailable
{
    use Queueable, SerializesModels;
    public $doctor;
    public $status;

    /**
     * Create a new message instance.
     */
    public function __construct($doctor, $status)
    {
        $this->doctor = $doctor;
        $this->status = $status;
    
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Doctor Request has been '.ucfirst($this->status),
        );

       
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.doctor_status', // تأكد أن الملف موجود في resources/views/emails/doctor_status.blade.php
            with: [
                'doctorName' => $this->doctor->first_name . ' ' . $this->doctor->last_name,
                'status' => $this->status,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
