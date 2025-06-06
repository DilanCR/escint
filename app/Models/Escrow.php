<?php

namespace App\Models;

use App\Constants\EscrowConstants;
use App\Models\Admin\Currency;
use App\Models\Admin\PaymentGatewayCurrency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Escrow extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $casts = [
        'id' => 'integer', 
        'user_id' => 'integer', 
        'escrow_category_id' => 'integer', 
        'payment_gateway_currency_id' => 'integer', 
        'payment_type' => 'integer', 
        'buyer_or_seller_id' => 'integer', 
        'amount' => 'double',
        'escrow_id' => 'string', 
        'role' => 'string', 
        'who_will_pay' => 'string', 
        'escrow_currency' => 'string', 
        'title' => 'string', 
        'remark' => 'string', 
        'details' => 'object',
        'file' => 'object',
        'status' => 'integer', 
        'created_at'           => 'datetime',
        'updated_at'           => 'datetime',
    ];
    public function user() {
        return $this->belongsTo(User::class);
    }
    public function escrowCategory()
    {
        return $this->belongsTo(EscrowCategory::class);
    }
    public function escrowDetails()
    {
        return $this->hasOne(EscrowDetails::class);
    }
    public function paymentGatewayCurrency()
    {
        return $this->belongsTo(PaymentGatewayCurrency::class);
    }
    public function escrowCurrency()
    {
        return $this->belongsTo(Currency::class,'escrow_currency', 'code');
    }
    public function conversations()
    {
        return $this->hasMany(EscrowChat::class);
    }
    public function getOppositeRoleAttribute(){
        if ($this->user_id == auth()->user()->id) {
            return $this->role;
        }else if($this->role == "buyer"){
            return "seller";
        }else if($this->role == "seller"){
            return "buyer";
        }
    }
    public function getStringStatusAttribute() {
        $status = $this->status;
        $data = [
            'class' => "",
            'value' => "",
        ];
        if($status == EscrowConstants::ONGOING) {
            $data = [
                'class'     => "badge badge--warning",
                'value'     => __("ongoing"),
            ];
        }else if($status == EscrowConstants::PAYMENT_PENDING) {
            $data = [
                'class'     => "badge badge--warning",
                'value'     => __("payment Pending"),
            ];
        }else if($status == EscrowConstants::APPROVAL_PENDING) {
            $data = [
                'class'     => "badge badge--warning",
                'value'     => __("approval Pending"),
            ];
        }else if($status == EscrowConstants::RELEASED) {
            $data = [
                'class'     => "badge badge--danger",
                'value'     => __("released"),
            ];
        }else if($status == EscrowConstants::ACTIVE_DISPUTE) {
            $data = [
                'class'     => "badge badge--danger",
                'value'     => __("active Dispute"),
            ];
        }else if($status == EscrowConstants::DISPUTED) {
            $data = [
                'class'     => "badge badge--danger",
                'value'     => __("disputed"),
            ];
        }else if($status == EscrowConstants::CANCELED) {
            $data = [
                'class'     => "badge badge--danger",
                'value'     => __("canceled"),
            ];
        }else if($status == EscrowConstants::REFUNDED) {
            $data = [
                'class'     => "badge badge--success",
                'value'     => __("refunded"),
            ];
        }else if($status == EscrowConstants::PAYMENT_WATTING) {
            $data = [
                'class'     => "badge badge--danger",
                'value'     => __("Payment Watting"),
            ];
        }

        return (object) $data;
    }
    public function getStringWhoWillPayAttribute() { 
        $role = $this->role;
        $who_will_pay = $this->who_will_pay;
        if ($role == EscrowConstants::SELLER_TYPE) {
            if ($who_will_pay == EscrowConstants::ME) {
                $who_will_pay = EscrowConstants::SELLER;
            }else{
                $who_will_pay = $this->who_will_pay;
            }
        }else if($role == EscrowConstants::BUYER_TYPE){
            if ($who_will_pay == EscrowConstants::ME) {
                $who_will_pay = EscrowConstants::BUYER;
            }else{
                $who_will_pay = $this->who_will_pay;
            }
        }
        $data = [
            'class' => "",
            'value' => "",
        ];
        if($who_will_pay == EscrowConstants::ME) {
            $data = [
                'class'     => "badge badge--warning",
                'value'     => "Me",
            ];
        }else if($who_will_pay == EscrowConstants::SELLER) {
            $data = [
                'class'     => "badge badge--success",
                'value'     => "Seller",
            ];
        }else if($who_will_pay == EscrowConstants::BUYER) {
            $data = [
                'class'     => "badge badge--success",
                'value'     => "Buyer",
            ];
        }else if($who_will_pay == EscrowConstants::HALF) {
            $data = [
                'class'     => "badge badge--primary",
                'value'     => "50%-50%",
            ];
        }

        return (object) $data;
    }
}
