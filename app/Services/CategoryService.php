<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryService
{
    /**
     * Generate slug unik dari nama. Auto-append -2, -3 kalau bentrok.
     */
    public function generateSlug(string $name, ?int $excludeId = null): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $i = 2;

        while (
            DB::table('tbm_categories')
                ->where('slug', $slug)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    /**
     * Cycle prevention: cek apakah $newParentId merupakan descendant dari $categoryId.
     * Return true berarti BAHAYA — tidak boleh diset sebagai parent.
     */
    public function wouldCreateCycle(int $categoryId, ?int $newParentId): bool
    {
        if (! $newParentId) return false;
        if ($categoryId === $newParentId) return true; // self-parent

        // Walk ancestors of newParent. Kalau ketemu categoryId, berarti cycle.
        $current = Category::find($newParentId);
        while ($current) {
            if ($current->id === $categoryId) return true;
            $current = $current->parent;
        }
        return false;
    }

    /**
     * Tidak boleh hapus kalau punya child atau punya produk yang refer ke sini.
     */
    public function canDelete(Category $cat): array
    {
        $childCount = $cat->getChildrenCount();
        if ($childCount > 0) {
            return [
                'allowed' => false,
                'reason'  => "Kategori '{$cat->name}' masih memiliki {$childCount} sub-kategori. Pindahkan atau hapus dulu sub-kategorinya.",
            ];
        }

        $productCount = $cat->getProductCount();
        if ($productCount > 0) {
            return [
                'allowed' => false,
                'reason'  => "Kategori '{$cat->name}' masih dipakai di {$productCount} produk. Pindahkan produk ke kategori lain dulu.",
            ];
        }

        return ['allowed' => true, 'reason' => null];
    }

    public function delete(Category $cat): array
    {
        $check = $this->canDelete($cat);
        if (! $check['allowed']) {
            return ['success' => false, 'message' => $check['reason']];
        }
        $name = $cat->name;
        $cat->delete();
        return ['success' => true, 'message' => "Kategori '{$name}' berhasil dihapus."];
    }

    /**
     * Build nested tree array dari semua categories.
     * Format: [['id'=>..., 'name'=>..., 'children'=>[...]], ...]
     */
    public function buildTree(?Collection $allCategories = null): array
    {
        $all = $allCategories ?? Category::orderBy('name')->get();

        // Group by parent_id
        $byParent = $all->groupBy(fn ($c) => $c->parent_id ?? 0);

        $build = function ($parentId) use (&$build, $byParent) {
            return ($byParent->get($parentId, collect()))->map(function ($c) use (&$build) {
                $childCount = $c->getChildrenCount();
                $productCount = $c->getProductCount();
                return [
                    'id'    => $c->id,
                    'text'  => $c->name . ($childCount > 0 ? " <span class='badge badge-light-secondary fs-9 ms-2'>{$childCount}</span>" : '')
                                       . ($productCount > 0 ? " <span class='badge badge-light-info fs-9 ms-1'>{$productCount} produk</span>" : ''),
                    'name'    => $c->name,
                    'slug'    => $c->slug,
                    'icon'    => 'ki-outline ki-folder fs-3 ' . ($childCount === 0 ? 'text-info' : 'text-warning'),
                    'state'   => ['opened' => true],
                    'children' => $build($c->id),
                ];
            })->values()->toArray();
        };

        return $build(0);
    }

    /**
     * Untuk dropdown parent select2: list flat dengan indentasi.
     * Return array of ['id'=>, 'name'=>, 'depth'=>, 'breadcrumb'=>]
     * Exclude $excludeId beserta SEMUA descendants-nya (untuk cycle prevention saat edit).
     */
    public function flatTreeForDropdown(?int $excludeId = null): array
    {
        $all = Category::orderBy('name')->get();
        $byParent = $all->groupBy(fn ($c) => $c->parent_id ?? 0);

        // Kumpulkan id excluded (self + descendants)
        $excludedIds = collect();
        if ($excludeId) {
            $collectDescendants = function ($id) use (&$collectDescendants, $byParent, $excludedIds) {
                $excludedIds->push($id);
                foreach ($byParent->get($id, collect()) as $child) {
                    $collectDescendants($child->id);
                }
            };
            $collectDescendants($excludeId);
        }

        $result = [];
        $walk = function ($parentId, $depth) use (&$walk, $byParent, &$result, $excludedIds) {
            foreach ($byParent->get($parentId, collect()) as $c) {
                if ($excludedIds->contains($c->id)) continue;
                $result[] = [
                    'id'         => $c->id,
                    'name'       => $c->name,
                    'depth'      => $depth,
                    'breadcrumb' => $c->getBreadcrumb(),
                ];
                $walk($c->id, $depth + 1);
            }
        };
        $walk(0, 0);

        return $result;
    }
}
