<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'email',
        'phone',
        'notes',
        'mpesa_no',
        'quantity',
        'total',
        'shipping_address',
        'order_status',
        'payment_status',
        'tranx_ref',
        'page_id',
        'payment_mode'
    ];
    public function page()
    {
        return $this->belongsTo(Page::class,'page_id');
    }
}
