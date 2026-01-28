<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarcodeHistory extends Model
{
    use HasFactory;

    protected $table = 'barcode_history';

    protected $fillable = [
        'barcode',
        'label',
        'price',
        'label_size',
        'mfg_date',
        'exp_date',
        'show_price',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
