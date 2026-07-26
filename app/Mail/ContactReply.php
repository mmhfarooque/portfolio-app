<?php

namespace App\Mail;

use App\Models\Contact;
use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactReply extends Mailable
{
    use SerializesModels;

    public function __construct(
        public Contact $contact,
        public string $replySubject,
        public string $replyMessage
    ) {}

    public function envelope(): Envelope
    {
        $replyTo = Setting::get('contact_email') ?: config('mail.from.address');

        return new Envelope(
            subject: $this->replySubject,
            replyTo: [$replyTo],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact-reply',
            with: [
                'contact' => $this->contact,
                'replyMessage' => $this->replyMessage,
            ],
        );
    }
}
