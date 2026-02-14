<?php

namespace App\Mail;

use App\Models\EmailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class TemplateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        protected string $emailSubject,
        protected string $bodyHtml,
        protected ?string $templateSlug = null,
        protected ?string $recipientName = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.template',
            with: [
                'body' => $this->bodyHtml,
            ],
        );
    }

    public function sent($message): void
    {
        $recipients = $message->getTo();
        $recipientEmail = '';
        foreach ($recipients as $address) {
            $recipientEmail = $address->getAddress();
            break;
        }

        EmailLog::create([
            'template_slug' => $this->templateSlug,
            'recipient_email' => $recipientEmail,
            'recipient_name' => $this->recipientName,
            'subject' => $this->emailSubject,
            'body_html' => $this->bodyHtml,
            'status' => 'sent',
            'sent_by' => Auth::id(),
            'sent_at' => now(),
        ]);
    }
}
