<?php

namespace App\Mail;

use App\Models\Contact;
use App\Models\Photo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PrintInquirySubmitted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Contact $contact,
        public Photo $photo
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Print Inquiry: ' . $this->photo->title,
            replyTo: [$this->contact->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.print-inquiry',
            with: [
                'contact' => $this->contact,
                'photo' => $this->photo,
            ],
        );
    }
}
