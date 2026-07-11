<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->nullable();
            $table->longText('description')->nullable();
            $table->enum('material', ['full_grain_leather', 'suede', 'pu_leather', 'other'])->default('full_grain_leather');
            $table->unsignedBigInteger('base_price');
            $table->unsignedBigInteger('sale_price')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->string('thumbnail')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description')->nullable();
            $table->decimal('avg_rating', 2, 1)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
