<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'url',
        'page_id',

    ];
    public function page()
    {
        return $this->belongsTo(Page::class,'page_id');
    }
}
