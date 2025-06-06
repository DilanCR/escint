<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EscrowDetails extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $casts = [
        'id' => 'integer', 
        'escrow_id ' => 'integer', 
        'fee' => 'double',
        'seller_get' => 'double',
        'buyer_pay' => 'double',
        'gateway_exchange_rate' => 'double',
        'created_at'           => 'datetime',
        'updated_at'           => 'datetime',
    ];
}
