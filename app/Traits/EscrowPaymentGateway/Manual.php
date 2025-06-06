<?php

namespace App\Traits\EscrowPaymentGateway;

use App\Constants\PaymentGatewayConst; 
use App\Traits\ControlDynamicInputFields; 
use App\Http\Helpers\Api\Helpers as ApiResponse;

trait Manual
{
use ControlDynamicInputFields;
    public function manualInit($escrow_data = null) { 
        if(!$escrow_data) $escrow_data = $this->request_data->data; 
        if (isset($escrow_data->payment_type) && $escrow_data->payment_type == "approvalPending") {
            return redirect()->route('user.escrow-action.manual.payment');
        }else {
            return redirect()->route('user.my-escrow.manual.payment', ['gateway' => PaymentGatewayConst::MANUA_GATEWAY, 'trx' => $escrow_data->trx]);
        } 
    } 
    //for api
    public function manualInitApi($escrow_data = null) { 
        if(!$escrow_data) $escrow_data = $this->request_data->data;     
        $payment_informations = [
            'trx'                   => $escrow_data->trx,
            'gateway_currency_name' => $escrow_data->gateway_currency->name,
            'request_amount'        => get_amount($escrow_data->escrow->amount, $escrow_data->escrow->escrow_currency),
            'exchange_rate'         => "1".' '.$escrow_data->escrow->escrow_currency.' = '. get_amount($escrow_data->escrow->gateway_exchange_rate, $escrow_data->escrow->gateway_currency),
            'total_charge'          => get_amount($escrow_data->escrow->escrow_total_charge, $escrow_data->escrow->escrow_currency),
            'charge_payer'          => $escrow_data->escrow->charge_payer,
            'seller_get'            => get_amount($escrow_data->escrow->seller_amount, $escrow_data->escrow->escrow_currency),
            'payable_amount'        => get_amount($escrow_data->escrow->buyer_amount, $escrow_data->escrow->gateway_currency),
       ];
       $data =[
            'gategay_type'          => $escrow_data->gateway_currency->gateway->type,
            'gateway_currency_name' => $escrow_data->gateway_currency->name,
            'alias'                 => $escrow_data->gateway_currency->alias,
            'identify'              => $escrow_data->gateway_currency->gateway->name,
            'payment_informations'  => $payment_informations,
            'input_fields'          => $escrow_data->gateway_currency->gateway->input_fields,
            'return_url'            => route('api.v1.user.api-escrow-action.manual.confirm'),
            'method'                => "post",
       ];
        $message = ['success'=>['Escrow manual payment informations']];
        return ApiResponse::success($message, $data); 
    } 

}
