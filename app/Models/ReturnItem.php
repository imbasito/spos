<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_id',
        'order_product_id',
        'product_id',
        'quantity',
        'refund_amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'refund_amount' => 'decimal:2',
    ];

    public function productReturn()
    {
        return $this->belongsTo(ProductReturn::class, 'return_id');
    }

    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
