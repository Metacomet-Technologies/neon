<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Guild;
use App\Models\License;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class LicenseAssignedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public License $license, public Guild $guild) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✅ License Assigned to ' . $this->guild->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.license-assigned',
            with: [
                'license' => $this->license,
                'guild' => $this->guild,
                'billingUrl' => url('/billing'),
            ],
        );
    }
}
