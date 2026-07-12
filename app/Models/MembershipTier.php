<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipTier extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'min_points', 'discount_percent', 'sort_order'];

    protected $casts = [
        'min_points' => 'integer',
        'discount_percent' => 'float',
        'sort_order' => 'integer',
    ];
}
