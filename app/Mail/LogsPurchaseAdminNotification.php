<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LogsPurchaseAdminNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $transaction;
    public $product;
    public $user;

    public function __construct($transaction, $product, $user)
    {
        $this->transaction = $transaction;
        $this->product = $product;
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('New Logs Purchase: ' . ($this->user->username ?? $this->user->email))
                    ->view('emails.logs_purchase_admin')
                    ->with([
                        'transaction' => $this->transaction,
                        'product' => $this->product,
                        'user' => $this->user,
                    ]);
    }
}
