<?php

namespace App\Http\Controllers\Web;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Grade\StoreGradeRequest;
use App\Http\Requests\Grade\UpdateGradeRequest;
use App\Models\ProductGrade;
use App\Support\Flash;
use App\Support\ResponseCode;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GradeController extends Controller
{
    use ApiResponse;

    public function index(Request $request): View
    {
        $q = $request->string('q')->toString();
        $grades = ProductGrade::query()->search($q ?: null)->orderBy('code')->get();
        return view('grades.index', ['grades' => $grades, 'filters' => ['q' => $q]]);
    }

    public function create(): View
    {
        return view('grades.create', ['grade' => new ProductGrade(['color' => '#6c757d'])]);
    }

    public function store(StoreGradeRequest $request): RedirectResponse
    {
        $g = ProductGrade::create($request->validated());
        return redirect()->route('grades.index')->with('flash', Flash::ok("Grade '{$g->code}' berhasil ditambahkan."));
    }

    public function edit(ProductGrade $grade): View
    {
        return view('grades.edit', ['grade' => $grade]);
    }

    public function update(UpdateGradeRequest $request, ProductGrade $grade): RedirectResponse
    {
        $grade->update($request->validated());
        return redirect()->route('grades.index')->with('flash', Flash::ok("Grade '{$grade->code}' berhasil diperbarui."));
    }

    public function destroy(Request $request, ProductGrade $grade): mixed
    {
        if (! $request->user()?->hasPermission('grades.delete')) {
            return $request->expectsJson() ? $this->failForbidden() : back()->with('flash', Flash::err('Tidak punya akses.', ResponseCode::FORBIDDEN));
        }

        $using = DB::table('tbm_products')->where('grade_id', $grade->id)->whereNull('deleted_date')->count();
        if ($using > 0) {
            $msg = "Grade '{$grade->code}' masih dipakai di {$using} produk. Tidak bisa dihapus.";
            return $request->expectsJson() ? $this->failBusinessRule($msg) : back()->with('flash', Flash::err($msg, ResponseCode::BUSINESS_RULE_FAILED));
        }

        $code = $grade->code;
        $grade->delete();
        $msg = "Grade '{$code}' berhasil dihapus.";
        return $request->expectsJson() ? $this->ok(null, $msg) : back()->with('flash', Flash::ok($msg));
    }
}
