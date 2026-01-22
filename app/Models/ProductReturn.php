<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductReturn extends Model
{
    use HasFactory;

    protected $table = 'returns';
    
    protected $fillable = [
        'order_id',
        'return_number',
        'total_refund',
        'reason',
        'processed_by',
    ];

    protected $casts = [
        'total_refund' => 'decimal:2',
    ];

    /**
     * Generate unique return number
     */
    public static function generateReturnNumber(): string
    {
        $lastReturn = static::orderBy('id', 'desc')->first();
        $nextNumber = $lastReturn ? (int) substr($lastReturn->return_number, 4) + 1 : 1;
        return 'RET-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function items()
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
