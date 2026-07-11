<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'brand_id', 'name', 'slug', 'sku', 'description',
        'material', 'base_price', 'sale_price', 'status', 'thumbnail',
        'seo_title', 'seo_description', 'avg_rating', 'reviews_count',
    ];

    protected $casts = [
        'base_price' => 'integer',
        'sale_price' => 'integer',
        'avg_rating' => 'float',
        'reviews_count' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
}
