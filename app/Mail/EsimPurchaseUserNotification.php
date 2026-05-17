<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EsimPurchaseUserNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $transaction;
    public $country;
    public $package;
    public $user;

    public function __construct($transaction, $country, $package, $user)
    {
        $this->transaction = $transaction;
        $this->country = $country;
        $this->package = $package;
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('Your eSIM Purchase Confirmation')
                    ->view('emails.esim_purchase_user')
                    ->with([
                        'transaction' => $this->transaction,
                        'country' => $this->country,
                        'package' => $this->package,
                        'user' => $this->user,
                    ]);
    }
}
