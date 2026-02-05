<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone', 'cnic', 'address', 'credit_limit'];

    protected $appends = ['total_due'];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Calculate total outstanding balance across all orders.
     */
    public function getTotalDueAttribute(): float
    {
        return (float) $this->orders()->sum('due');
    }
}
