<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UniqueSlug
{
    /**
     * Generate a slug from $name that is unique in $table.slug,
     * appending -2, -3, ... on collision. $ignoreId excludes the
     * row being updated from the collision check.
     */
    public static function make(string $name, string $table, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $n = 2;

        while (
            DB::table($table)
                ->where('slug', $slug)
                ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$n}";
            $n++;
        }

        return $slug;
    }
}
