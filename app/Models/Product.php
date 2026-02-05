<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, \App\Traits\LogActivity;
    protected $fillable = [
        'image',
        'name',
        'urdu_name',
        'slug',
        'sku',
        'hs_code',
        'barcode',
        'description',
        'category_id',
        'brand_id',
        'unit_id',
        'price',
        'discount',
        'discount_type',
        'purchase_price',
        'quantity',
        'total_returned',
        'expire_date',
        'status',
    ];
    protected $appends = ['discounted_price'];

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        if (!$this->exists || empty($this->attributes['slug'])) {
            $this->attributes['slug'] = $this->generateSlug($value);
        }
    }

    /**
     * Generate a unique slug for the category.
     *
     * @param string $name
     * @return string
     */
    protected function generateSlug($name)
    {
        $slug = Str::slug($name);
        $count = static::where('slug', 'like', "$slug%")->count();

        return $count ? "{$slug}-{$count}" : $slug;
    }

    /**
     * Boot the model and register event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            // Auto-generate SKU if not provided
            if (empty($product->sku)) {
                $product->sku = self::generateUniqueSku();
            }
        });
    }

    /**
     * Generate a unique 12-digit EAN-like SKU.
     *
     * @return string
     */
    public static function generateUniqueSku(): string
    {
        do {
            // Generate random 12-digit number
            $sku = str_pad(mt_rand(0, 999999999999), 12, '0', STR_PAD_LEFT);
        } while (static::where('sku', $sku)->exists());

        return $sku;
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
    public function scopeStocked($query)
    {
        return $query->where('quantity','>=',1);
    }
    public function getDiscountedPriceAttribute()
    {
        if ($this->discount_type == 'fixed') {
            $discountedPrice = $this->price - $this->discount;
        } elseif ($this->discount_type == 'percentage') {
            $discountedPrice = $this->price - ($this->price * $this->discount / 100);
        } else {
            $discountedPrice = $this->price;
        }
        return round($discountedPrice, 2);
    }
    public function orderProducts()
    {
        return $this->hasMany(OrderProduct::class);
    }
}
