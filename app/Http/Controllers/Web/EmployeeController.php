<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Support\Flash;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        return view('employees.index', [
            'employees' => Employee::query()
                ->search($request->string('q')->toString() ?: null)
                ->orderBy('name')->paginate(50)->withQueryString(),
            'q' => $request->string('q')->toString(),
        ]);
    }

    public function create(): View
    {
        return view('employees.create', ['employee' => new Employee(['is_active' => true])]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $emp = Employee::create($request->validated());
        return redirect()->route('employees.index')
            ->with('flash', Flash::ok("Pegawai '{$emp->name}' berhasil ditambahkan.", 'Berhasil'));
    }

    public function edit(Employee $employee): View
    {
        return view('employees.edit', ['employee' => $employee]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());
        return redirect()->route('employees.index')
            ->with('flash', Flash::ok("Pegawai '{$employee->name}' berhasil diperbarui.", 'Berhasil'));
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $name = $employee->name;
        $employee->delete();
        return redirect()->route('employees.index')
            ->with('flash', Flash::ok("Pegawai '{$name}' dihapus.", 'Berhasil'));
    }
}
