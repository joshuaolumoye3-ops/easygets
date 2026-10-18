<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Sent to the admin when a gift-card withdrawal cannot be paid automatically
 * (low Flutterwave balance, unknown outcome, provider error, duplicate payout, …).
 * Contains NO full account numbers, BVN/NIN or secrets.
 */
class PayoutNeedsAttention extends Mailable
{
    public function __construct(public array $ctx)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->ctx['subject']);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payout-needs-attention');
    }
}