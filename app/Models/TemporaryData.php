<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemporaryData extends Model
{
    use HasFactory;
    protected $table = "temporary_datas";
    
    protected $guarded = ['id'];

    protected $casts = ['data' => 'object'];
}
