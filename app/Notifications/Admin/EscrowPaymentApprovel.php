<?php

namespace App\Notifications\Admin;

use Illuminate\Bus\Queueable;
use App\Models\Admin\BasicSettings;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class EscrowPaymentApprovel extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public $user;
    public $escrow;
    public function __construct($user, $escrow)
    {
        $this->user = $user;
        $this->escrow = $escrow;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $user = $this->user;
        $escrow = $this->escrow;
        $basic_settings = BasicSettings::first();
        if ($escrow->payment_type == escrow_const()::GATEWAY) {
            $paymentGateway = $escrow->paymentGatewayCurrency->name;
            $exchangeRate = "1 ".$escrow->escrow_currency." = ".get_amount($escrow->escrowDetails->gateway_exchange_rate,$escrow->paymentGatewayCurrency->currency_code);
            $buyerPaid = get_amount($escrow->escrowDetails->buyer_pay,$escrow->paymentGatewayCurrency->currency_code);
        }else{
            $paymentGateway = "Wallet";
            $exchangeRate = "1 ".$escrow->escrow_currency." = 1 ".$escrow->escrow_currency;
            $buyerPaid = get_amount($escrow->escrowDetails->buyer_pay,$escrow->escrow_currency);
        }
        return (new MailMessage)
                    ->greeting("Hello ".$user->fullname."!")
                    ->subject("Admin approved your payment from ". $basic_settings->site_name)
                    ->line('Admin approved your payment. Please go to your escrow page and check it now.')
                    ->line('Escrow ID: '.$escrow->escrow_id)
                    ->line('Title: '.$escrow->title)
                    ->line('Category: '.$escrow->escrowCategory->name)
                    ->line('Charge Payer: '.$escrow->string_who_will_pay->value)
                    ->line('Created By: '.$escrow->user->email)
                    ->line('Amount: '.get_amount($escrow->amount,$escrow->escrow_currency))
                    ->line('Fee: '.get_amount($escrow->escrowDetails->fee,$escrow->escrow_currency))
                    ->line('Seller Amount: '.get_amount($escrow->escrowDetails->seller_get,$escrow->escrow_currency))
                    ->line('Pay With: '.$paymentGateway ?? "")
                    ->line('Exchange Rate: '.$exchangeRate ?? "")
                    ->line('Buyer Paid: '.$buyerPaid ?? "")
                    ->action('View Escrow', url('/user/escrow/conversation/'.encrypt($escrow->id)))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
