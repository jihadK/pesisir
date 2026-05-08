<?php

namespace App\Http\Controllers\Web;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use App\Support\Flash;
use App\Support\ResponseCode;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly CategoryService $service) {}

    public function index(Request $request): View
    {
        $tree = $this->service->buildTree();
        $totalCategories = Category::count();
        $totalRoot = Category::root()->count();

        return view('categories.index', compact('tree', 'totalCategories', 'totalRoot'));
    }

    public function create(Request $request): View
    {
        // Pre-fill parent dari query string ?parent=X (saat klik "+" di tree)
        $preParent = $request->integer('parent');

        return view('categories.create', [
            'category'   => new Category(['parent_id' => $preParent ?: null]),
            'parentList' => $this->service->flatTreeForDropdown(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $cat = Category::create($request->validated());

        return redirect()
            ->route('categories.index')
            ->with('flash', Flash::ok("Kategori '{$cat->name}' berhasil ditambahkan.", 'Berhasil Disimpan'));
    }

    public function edit(Category $category): View
    {
        return view('categories.edit', [
            'category'   => $category,
            // exclude self + descendants supaya tidak bisa pilih diri sendiri sebagai parent
            'parentList' => $this->service->flatTreeForDropdown($category->id),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()
            ->route('categories.index')
            ->with('flash', Flash::ok("Kategori '{$category->name}' berhasil diperbarui.", 'Berhasil Diperbarui'));
    }

    public function destroy(Request $request, Category $category): mixed
    {
        if (! $request->user()?->hasPermission('categories.delete')) {
            return $request->expectsJson()
                ? $this->failForbidden()
                : back()->with('flash', Flash::err('Tidak punya akses.', ResponseCode::FORBIDDEN));
        }

        $result = $this->service->delete($category);

        if ($request->expectsJson()) {
            return $result['success']
                ? $this->ok(null, $result['message'])
                : $this->failBusinessRule($result['message']);
        }

        return back()->with(
            'flash',
            $result['success']
                ? Flash::ok($result['message'], 'Berhasil Dihapus')
                : Flash::err($result['message'], ResponseCode::BUSINESS_RULE_FAILED, 'Tidak Bisa Menghapus')
        );
    }

    /**
     * JSON tree endpoint untuk jstree refresh atau API mobile nanti.
     */
    public function tree(Request $request)
    {
        return $this->ok(['tree' => $this->service->buildTree()]);
    }
}
