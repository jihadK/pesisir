<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Category extends BaseModel
{
    protected $table = 'tbm_categories';

    /** Tabel ini di DDL hanya punya created_date — tidak ada updated_date */
    public $timestamps = false;

    protected $fillable = ['parent_id', 'name', 'slug', 'description'];

    protected $casts = ['created_date' => 'datetime'];

    /* ========== Relations ========== */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('name');
    }

    /** Eager-load semua descendants secara recursive */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /* ========== Scopes ========== */
    public function scopeRoot(Builder $q): Builder
    {
        return $q->whereNull('parent_id');
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) return $q;
        return $q->where(function ($qq) use ($term) {
            $qq->where('name', 'ilike', "%$term%")
               ->orWhere('slug', 'ilike', "%$term%")
               ->orWhere('description', 'ilike', "%$term%");
        });
    }

    /* ========== Helpers ========== */

    /** Daftar ancestor dari root → parent (urut top-down) */
    public function getAncestors(): Collection
    {
        $ancestors = collect();
        $current = $this->parent;
        while ($current) {
            $ancestors->prepend($current);
            $current = $current->parent;
        }
        return $ancestors;
    }

    /** Breadcrumb string: "Ikan Laut > Ikan Pelagis > Tuna" */
    public function getBreadcrumb(string $sep = ' › '): string
    {
        return $this->getAncestors()->push($this)->pluck('name')->implode($sep);
    }

    /** Depth dari root (root = 0, anak = 1, dst) */
    public function getDepth(): int
    {
        return $this->getAncestors()->count();
    }

    /** Apakah category ini descendant dari $other */
    public function isDescendantOf(int $otherId): bool
    {
        $current = $this->parent;
        while ($current) {
            if ($current->id === $otherId) return true;
            $current = $current->parent;
        }
        return false;
    }

    /** Hitung child langsung */
    public function getChildrenCount(): int
    {
        return (int) DB::table('tbm_categories')->where('parent_id', $this->id)->count();
    }

    /** Hitung produk yang pakai kategori ini langsung */
    public function getProductCount(): int
    {
        return (int) DB::table('tbm_products')
            ->where('category_id', $this->id)
            ->whereNull('deleted_date')
            ->count();
    }
}
