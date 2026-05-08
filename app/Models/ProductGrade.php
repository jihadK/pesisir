<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class ProductGrade extends BaseModel
{
    protected $table = 'tbm_product_grades';

    public $timestamps = false;

    protected $fillable = ['code', 'name', 'color'];

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) return $q;
        return $q->where(function ($qq) use ($term) {
            $qq->where('code', 'ilike', "%$term%")
               ->orWhere('name', 'ilike', "%$term%");
        });
    }

    /** Hex color dengan fallback ke abu kalau null */
    public function getDisplayColorAttribute(): string
    {
        return $this->color ?: '#6c757d';
    }

    /** Auto pilih warna text (putih/hitam) supaya kontras dengan background */
    public function getContrastTextAttribute(): string
    {
        $hex = ltrim($this->display_color, '#');
        if (strlen($hex) !== 6) return '#ffffff';
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $luma = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        return $luma > 0.6 ? '#1f2937' : '#ffffff';
    }
}
