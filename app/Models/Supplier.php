<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Purchase;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = ['name','phone', 'address'];
    protected $table = 'suppliers';
    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}
