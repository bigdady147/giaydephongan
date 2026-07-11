<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'size', 'color', 'sku', 'stock_quantity', 'price_override'];

    protected $casts = [
        'stock_quantity' => 'integer',
        'price_override' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
