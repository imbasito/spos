<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'user_id',
        'sub_total',
        'tax',
        'discount_value',
        'discount_type',
        'shipping',
        'grand_total',
        'paid_amount',
        'payment_status',
        'status',
        'date',
    ];

    protected $appends = ['due_amount'];

    /**
     * Calculate remaining amount due on this purchase.
     */
    public function getDueAmountAttribute(): float
    {
        return max(0, (float)$this->grand_total - (float)$this->paid_amount);
    }
    protected $table = 'purchases';
    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }
    public function supplier(){
        return $this->belongsTo(Supplier::class);
    }
}
