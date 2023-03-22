<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BasicNotify extends Mailable
{
    use Queueable, SerializesModels;

    public  $msg;
    public  $from_name;
    public $email;
    public  $subject;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($msg,$subject,$email = null,$from_name = null)
    {
        $this->email=$email;
        $this->msg = $msg;
        $this->from_name = env('MAIL_FROM_ADDRESS');
        $this->subject = $subject;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            from: new Address($this->from_name, 'DukaApp'),
            subject:  $this->subject,
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'emails.notify',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
