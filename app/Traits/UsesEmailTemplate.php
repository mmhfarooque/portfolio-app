<?php

namespace App\Traits;

use App\Models\EmailTemplate;
use Illuminate\Notifications\Messages\MailMessage;

trait UsesEmailTemplate
{
    public function toMail($notifiable): MailMessage
    {
        $template = EmailTemplate::findBySlug($this->templateSlug);

        if ($template && $template->is_active) {
            $variables = $this->getTemplateVariables($notifiable);
            $renderedBody = $template->renderBody($variables);
            $renderedSubject = $template->renderSubject($variables);

            return (new MailMessage)
                ->subject($renderedSubject)
                ->view('emails.template', ['body' => $renderedBody]);
        }

        return $this->buildMailMessage($notifiable);
    }

    abstract protected function getTemplateVariables($notifiable): array;

    abstract protected function buildMailMessage($notifiable): MailMessage;
}
