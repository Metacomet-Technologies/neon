<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\License;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class LicensePurchasedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public License $license, public string $customerEmail, public float $amountPaid, public string $currency = 'USD') {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🎉 Your Neon License is Ready!',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.license-purchased',
            with: [
                'license' => $this->license,
                'customerEmail' => $this->customerEmail,
                'amountPaid' => $this->amountPaid,
                'currency' => $this->currency,
                'billingUrl' => url('/billing'),
                'email' => $this->customerEmail,
            ],
        );
    }
}
