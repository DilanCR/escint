<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserKycData extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'id' => 'integer', 
        'user_id ' => 'integer', 
        'data'      => 'object',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
