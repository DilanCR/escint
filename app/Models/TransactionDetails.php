<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionDetails extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $casts = [
        'id' => 'integer',
        'transaction_id ' => 'integer',
        'percent_charge' => 'double',
        'fixed_charge' => 'double',
        'total_charge' => 'double', 
        'created_at'           => 'datetime',
        'updated_at'           => 'datetime',
    ];
}
