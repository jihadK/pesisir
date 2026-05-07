<?php

use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\CustomerController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\SupplierController;
use App\Http\Controllers\Web\WarehouseController;
use Illuminate\Support\Facades\Route;

// Guest
Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

// Authenticated
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/',          [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ========== MASTER DATA — Customers ==========
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::middleware('permission:customers.create')->group(function () {
            Route::get('/create', [CustomerController::class, 'create'])->name('create');
            Route::post('/',      [CustomerController::class, 'store'])->name('store');
        });
        Route::middleware('permission:customers.view')->group(function () {
            Route::get('/',                [CustomerController::class, 'index'])->name('index');
            Route::get('/{customer}',      [CustomerController::class, 'show'])->whereNumber('customer')->name('show');
        });
        Route::middleware('permission:customers.update')->group(function () {
            Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->whereNumber('customer')->name('edit');
            Route::put('/{customer}',      [CustomerController::class, 'update'])->whereNumber('customer')->name('update');
        });
        Route::middleware('permission:customers.delete')->group(function () {
            Route::delete('/{customer}',          [CustomerController::class, 'destroy'])->whereNumber('customer')->name('destroy');
            Route::post('/{customer}/restore',    [CustomerController::class, 'restore'])->whereNumber('customer')->name('restore');
        });
    });

    // ========== MASTER DATA — Suppliers ==========
    Route::prefix('suppliers')->name('suppliers.')->group(function () {
        Route::middleware('permission:suppliers.create')->group(function () {
            Route::get('/create', [SupplierController::class, 'create'])->name('create');
            Route::post('/',      [SupplierController::class, 'store'])->name('store');
        });
        Route::middleware('permission:suppliers.view')->group(function () {
            Route::get('/',                  [SupplierController::class, 'index'])->name('index');
            Route::get('/{supplier}',        [SupplierController::class, 'show'])->whereNumber('supplier')->name('show');
        });
        Route::middleware('permission:suppliers.update')->group(function () {
            Route::get('/{supplier}/edit',   [SupplierController::class, 'edit'])->whereNumber('supplier')->name('edit');
            Route::put('/{supplier}',        [SupplierController::class, 'update'])->whereNumber('supplier')->name('update');
        });
        Route::middleware('permission:suppliers.delete')->group(function () {
            Route::delete('/{supplier}',          [SupplierController::class, 'destroy'])->whereNumber('supplier')->name('destroy');
            Route::post('/{supplier}/restore',    [SupplierController::class, 'restore'])->whereNumber('supplier')->name('restore');
        });
    });

    // ========== MASTER DATA — Warehouses ==========
    // Static routes (create) HARUS di-declare dulu sebelum dynamic routes ({warehouse})
    // supaya tidak match {warehouse} = "create".
    Route::prefix('warehouses')->name('warehouses.')->group(function () {
        // Create
        Route::middleware('permission:warehouses.create')->group(function () {
            Route::get('/create',  [WarehouseController::class, 'create'])->name('create');
            Route::post('/',       [WarehouseController::class, 'store'])->name('store');
        });

        // List & Show
        Route::middleware('permission:warehouses.view')->group(function () {
            Route::get('/',                       [WarehouseController::class, 'index'])->name('index');
            Route::get('/{warehouse}',            [WarehouseController::class, 'show'])->whereNumber('warehouse')->name('show');
        });

        // Edit & Update & Toggle
        Route::middleware('permission:warehouses.update')->group(function () {
            Route::get('/{warehouse}/edit',       [WarehouseController::class, 'edit'])->whereNumber('warehouse')->name('edit');
            Route::put('/{warehouse}',            [WarehouseController::class, 'update'])->whereNumber('warehouse')->name('update');
            Route::patch('/{warehouse}/toggle',   [WarehouseController::class, 'toggle'])->whereNumber('warehouse')->name('toggle');
        });
    });
});
