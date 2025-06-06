<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BasicSettings extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'mail_config'               => 'object',
        'push_notification_config'  => 'object',
        'broadcast_config'          => 'object',
        'kyc_verification'          => 'integer',
    ];


    public function mailConfig() {
        
    }
}
