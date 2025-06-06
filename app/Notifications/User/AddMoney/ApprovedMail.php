<?php

namespace App\Notifications\User\AddMoney;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class ApprovedMail extends Notification
{
    use Queueable;

    public $user;
    public $data;
    public $trx_id;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($user,$data,$trx_id)
    {
        $this->user = $user;
        $this->data = $data;
        $this->trx_id = $trx_id;
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
        $data = $this->data;
        $trx_id = $this->trx_id;
        $date = Carbon::now();
        $dateTime = $date->format('Y-m-d h:i:s A');
        return (new MailMessage)
        ->greeting("Hello ".$user->fullname." !")
        ->subject("Add Money Via ". $data['gateway_currency']->name)
        ->line("Your add money request successful via ".$data['gateway_currency']->name." , details of add money:")
        ->line("Request Amount: " . getAmount($data['amount']->requested_amount,2).' '. $data['amount']->sender_currency)
        ->line("Exchange Rate: " ." 1 ". $data['amount']->sender_currency.' = '. getAmount($data['amount']->exchange_rate,2).' '.$data['amount']->gateway_cur_code)
        ->line("Fees & Charges: " . $data['amount']->gateway_total_charge.' '. $data['amount']->gateway_cur_code)
        ->line("Total Payable Amount: " . getAmount($data['amount']->total_payable_amount,2).' '. $data['amount']->gateway_cur_code)
        ->line("Transaction Id: " .$trx_id)
        ->line("Status: Success")
        ->line("Date And Time: " .$dateTime)
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
