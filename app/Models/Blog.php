<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin\Admin;

class Blog extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
        'name'             => 'object',
        'tags'             => 'object',
        'details'          => 'object',
    ];
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
    public function category()
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeBanned($query)
    {
        return $query->where('status', false);
    }

    public function scopeSearch($query,$text) {
        $query->Where("name","like","%".$text."%");
    }
}
