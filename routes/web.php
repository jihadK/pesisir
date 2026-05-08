<?php

use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\CustomerController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\GradeController;
use App\Http\Controllers\Web\PriceTierController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\SupplierController;
use App\Http\Controllers\Web\UomController;
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

    // ========== MASTER DATA — Products ==========
    Route::prefix('products')->name('products.')->group(function () {
        // STATIC routes dulu sebelum {product} dynamic
        Route::middleware('permission:products.view')->group(function () {
            Route::get('/suggest-sku', [ProductController::class, 'suggestSku'])->name('suggest-sku');
        });
        Route::middleware('permission:products.create')->group(function () {
            Route::get('/create', [ProductController::class, 'create'])->name('create');
            Route::post('/',      [ProductController::class, 'store'])->name('store');
        });
        Route::middleware('permission:products.view')->group(function () {
            Route::get('/',          [ProductController::class, 'index'])->name('index');
            Route::get('/{product}', [ProductController::class, 'show'])->whereNumber('product')->name('show');
        });
        Route::middleware('permission:products.update')->group(function () {
            Route::get('/{product}/edit', [ProductController::class, 'edit'])->whereNumber('product')->name('edit');
            Route::put('/{product}',      [ProductController::class, 'update'])->whereNumber('product')->name('update');
        });
        Route::middleware('permission:products.delete')->group(function () {
            Route::delete('/{product}',          [ProductController::class, 'destroy'])->whereNumber('product')->name('destroy');
            Route::post('/{product}/restore',    [ProductController::class, 'restore'])->whereNumber('product')->name('restore');
        });
    });

    // ========== MASTER DATA — Categories ==========
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::middleware('permission:categories.create')->group(function () {
            Route::get('/create', [CategoryController::class, 'create'])->name('create');
            Route::post('/',      [CategoryController::class, 'store'])->name('store');
        });
        Route::middleware('permission:categories.view')->group(function () {
            Route::get('/',     [CategoryController::class, 'index'])->name('index');
            Route::get('/tree', [CategoryController::class, 'tree'])->name('tree');
        });
        Route::middleware('permission:categories.update')->group(function () {
            Route::get('/{category}/edit', [CategoryController::class, 'edit'])->whereNumber('category')->name('edit');
            Route::put('/{category}',      [CategoryController::class, 'update'])->whereNumber('category')->name('update');
        });
        Route::middleware('permission:categories.delete')->group(function () {
            Route::delete('/{category}', [CategoryController::class, 'destroy'])->whereNumber('category')->name('destroy');
        });
    });

    // ========== MASTER DATA — Units of Measure (UoM) ==========
    Route::prefix('uoms')->name('uoms.')->group(function () {
        Route::middleware('permission:uom.create')->group(function () {
            Route::get('/create', [UomController::class, 'create'])->name('create');
            Route::post('/',      [UomController::class, 'store'])->name('store');
        });
        Route::middleware('permission:uom.view')->group(function () {
            Route::get('/', [UomController::class, 'index'])->name('index');
        });
        Route::middleware('permission:uom.update')->group(function () {
            Route::get('/{uom}/edit', [UomController::class, 'edit'])->whereNumber('uom')->name('edit');
            Route::put('/{uom}',      [UomController::class, 'update'])->whereNumber('uom')->name('update');
        });
        Route::middleware('permission:uom.delete')->group(function () {
            Route::delete('/{uom}', [UomController::class, 'destroy'])->whereNumber('uom')->name('destroy');
        });
    });

    // ========== MASTER DATA — Product Grades ==========
    Route::prefix('grades')->name('grades.')->group(function () {
        Route::middleware('permission:grades.create')->group(function () {
            Route::get('/create', [GradeController::class, 'create'])->name('create');
            Route::post('/',      [GradeController::class, 'store'])->name('store');
        });
        Route::middleware('permission:grades.view')->group(function () {
            Route::get('/', [GradeController::class, 'index'])->name('index');
        });
        Route::middleware('permission:grades.update')->group(function () {
            Route::get('/{grade}/edit', [GradeController::class, 'edit'])->whereNumber('grade')->name('edit');
            Route::put('/{grade}',      [GradeController::class, 'update'])->whereNumber('grade')->name('update');
        });
        Route::middleware('permission:grades.delete')->group(function () {
            Route::delete('/{grade}', [GradeController::class, 'destroy'])->whereNumber('grade')->name('destroy');
        });
    });

    // ========== MASTER DATA — Price Tiers ==========
    Route::prefix('price-tiers')->name('price_tiers.')->group(function () {
        Route::middleware('permission:price_tiers.create')->group(function () {
            Route::get('/create', [PriceTierController::class, 'create'])->name('create');
            Route::post('/',      [PriceTierController::class, 'store'])->name('store');
        });
        Route::middleware('permission:price_tiers.view')->group(function () {
            Route::get('/', [PriceTierController::class, 'index'])->name('index');
        });
        Route::middleware('permission:price_tiers.update')->group(function () {
            Route::get('/{price_tier}/edit', [PriceTierController::class, 'edit'])->whereNumber('price_tier')->name('edit');
            Route::put('/{price_tier}',      [PriceTierController::class, 'update'])->whereNumber('price_tier')->name('update');
        });
        Route::middleware('permission:price_tiers.delete')->group(function () {
            Route::delete('/{price_tier}', [PriceTierController::class, 'destroy'])->whereNumber('price_tier')->name('destroy');
        });
    });

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
