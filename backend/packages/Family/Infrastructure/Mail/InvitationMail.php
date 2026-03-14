<?php

declare(strict_types=1);

namespace Packages\Family\Infrastructure\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly string $familyName,
        private readonly string $inviterName,
        private readonly string $acceptUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->familyName} への招待",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.invitation',
            with: [
                'familyName' => $this->familyName,
                'inviterName' => $this->inviterName,
                'acceptUrl' => $this->acceptUrl,
            ],
        );
    }
}
