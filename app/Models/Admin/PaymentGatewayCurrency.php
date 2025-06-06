<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGatewayCurrency extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $casts = [
        'id'        => 'integer',
        'payment_gateway_id'        => 'integer',
        'name'                      => 'string',
        'alias'                     => 'string',
        'currency_code'             => 'string',
        'currency_symbol'           => 'string',
        'image'                     => 'string',
        'min_limit'                 => 'double',
        'max_limit'                 => 'double',
        'percent_charge'            => 'double',
        'fixed_charge'              => 'double',
        'rate'                      => 'double',
        'created_at'           => 'datetime',
        'updated_at'           => 'datetime',
    ];
    public function gateway() {
        return $this->belongsTo(PaymentGateway::class,"payment_gateway_id");
    }
    public function currency() {
        return $this->belongsTo(Currency::class,"currency_code","code");
    }
}
