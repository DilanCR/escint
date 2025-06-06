<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin\Currency;
use App\Models\User;

class UserWallet extends Model
{
    use HasFactory;
    public $timestamps = true;
    protected $fillable = ['balance', 'status','user_id','currency_id','created_at','updated_at'];
    protected $casts = [
        'user_id' => 'integer',
        'currency_id' => 'integer',
        'balance' => 'double',
        'status' => 'integer',
    ];
    public function scopeAuth($query) {
        return $query->where('user_id',auth(get_auth_guard())->user()->id);
    }
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
