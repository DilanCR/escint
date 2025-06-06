<?php

namespace App\Models;

use App\Models\Admin\Admin;
use App\Constants\GlobalConst;
use App\Models\Admin\Currency;
use App\Models\TransactionDetails;
use App\Models\Admin\PaymentGateway;
use App\Constants\PaymentGatewayConst;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin\PaymentGatewayCurrency;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;

    protected $casts = [
        'id' => 'integer',
        'admin_id' => 'integer',
        'user_id' => 'integer',
        'user_wallet_id' => 'integer', 
        'payment_gateway_currency_id' => 'integer',
        'trx_id' => 'string',
        'sender_request_amount' => 'double',
        'payable' => 'double',
        'available_balance' => 'double',
        'exchange_rate' => 'double',
        'remark' => 'string', 
        'details' => 'object',
        'type' => 'string',
        'reject_reason' => 'string',
        'status' => 'integer', 
        'created_at'           => 'datetime',
        'updated_at'           => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function admin() {
        return $this->belongsTo(Admin::class,'admin_id');
    }
    public function currency()
    {
        return $this->belongsTo(Currency::class,'sender_currency_code','code');
    }
    public function gateway_currency()
    {
        return $this->belongsTo(PaymentGatewayCurrency::class,'payment_gateway_currency_id');
    }
    public function user_wallets()
    {
        return $this->belongsTo(UserWallet::class, 'user_wallet_id');
    }
    public function payment_gateway()
    {
        return $this->belongsTo(PaymentGateway::class);
    }
    public function transaction_details()
    {
        return $this->hasOne(TransactionDetails::class,'transaction_id');
    }
    public function getCreatorAttribute() {
        if($this->user_id != null) {
            return $this->user;
        }else if($this->admin_id != null) {
            return $this->admin;
        }
    }
    public function getStringStatusAttribute() {
        $status = $this->status;
        $data = [
            'class' => "",
            'value' => "",
        ];
        if($status == PaymentGatewayConst::STATUSSUCCESS) {
            $data = [
                'class'     => "badge badge--success",
                'value'     => __("success"),
            ];
        }else if($status == PaymentGatewayConst::STATUSPENDING) {
            $data = [
                'class'     => "badge badge--warning",
                'value'     => __("pending"),
            ];
        }else if($status == PaymentGatewayConst::STATUSHOLD) {
            $data = [
                'class'     => "badge badge--warning",
                'value'     => __("Hold"),
            ];
        }else if($status == PaymentGatewayConst::STATUSREJECTED) {
            $data = [
                'class'     => "badge badge--danger",
                'value'     => __("Rejected"),
            ];
        }else if($status == PaymentGatewayConst::STATUSWAITING) {
            $data = [
                'class'     => "badge badge--warning",
                'value'     => __("Waiting"),
            ];
        }

        return (object) $data;
    }
    public function scopeAddMoney($query) {
        return $query->where("type",PaymentGatewayConst::TYPEADDMONEY);
    }

    public function scopeMoneyOut($query) {
        return $query->where("type",PaymentGatewayConst::TYPEMONEYOUT);
    }
}
