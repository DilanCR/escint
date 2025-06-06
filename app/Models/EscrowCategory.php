<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EscrowCategory extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $casts = [
        'id' => 'integer', 
        'added_by' => 'integer', 
        'name' => 'string',
        'slug' => 'string',
        'status' => 'integer',
        'created_at'           => 'datetime',
        'updated_at'           => 'datetime',
    ];
    public function getEditDataAttribute() { 
        $data = [
            'id'      => $this->id, 
            'name'      => $this->name,
        ];
        return json_encode($data);
    }
    public function scopeSearch($query,$text) {
        $query->where("name","like","%".$text."%");
    }
}
