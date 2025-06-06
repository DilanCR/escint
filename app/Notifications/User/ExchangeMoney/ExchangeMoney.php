<?php

namespace App\Notifications\User\ExchangeMoney;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class ExchangeMoney extends Notification
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
            ->subject("Exchange Money From ". $data['requestData']['exchange_from_amount'].' '.$data['requestData']['exchange_from_currency'].' To '.$data['requestData']['exchange_to_amount'].' '.$data['requestData']['exchange_to_currency'])
            ->line("Your Exchange money request is successful From ".$data['requestData']['exchange_from_amount'].' '.$data['requestData']['exchange_from_currency'].' To '.$data['requestData']['exchange_to_amount'].' '.$data['requestData']['exchange_to_currency'])
            ->line("Transaction Id: " .$trx_id)
            ->line("Request Amount: " .$data['requestData']['exchange_from_amount'].' '.$data['requestData']['exchange_from_currency'])
            ->line("Exchange Rate: " ." 1 ". $data['requestData']['exchange_from_currency'].' = '. getAmount($data['chargeCalculate']->exchange_rate,4).' '.$data['requestData']['exchange_to_currency'])
            ->line("Fees & Charges: " . getAmount($data['chargeCalculate']->total_charge,4).' '.$data['requestData']['exchange_from_currency'])
            ->line("Will Get: " .  get_amount($data['requestData']['exchange_to_amount'],4).$data['requestData']['exchange_to_currency'])
            ->line("Total Payable Amount: " . get_amount($data['chargeCalculate']->payable,4))
            ->line("Status: ". "Pending")
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
