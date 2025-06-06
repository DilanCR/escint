<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EscrowConversationAttachment extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $casts = [
        'id' => 'integer', 
        'escrow_chat_id' => 'integer', 
        'attachment' => 'string', 
        'attachment_info'  => 'object',
        'created_at'           => 'datetime',
        'updated_at'           => 'datetime',
    ];
}
