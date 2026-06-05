<?php

use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\CleaningServiceController;
use App\Http\Controllers\Web\CustomerController;
use App\Http\Controllers\Web\CustomerPriceController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DeliveryOrderController;
use App\Http\Controllers\Web\EmployeeController;
use App\Http\Controllers\Web\InvoiceController;
use App\Http\Controllers\Web\PaymentController;
use App\Http\Controllers\Web\GradeController;
use App\Http\Controllers\Web\PaymentMethodController;
use App\Http\Controllers\Web\PriceTierController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\PurchaseOrderController;
use App\Http\Controllers\Web\ReceivableController;
use App\Http\Controllers\Web\SalesOrderController;
use App\Http\Controllers\Web\ServiceRateController;
use App\Http\Controllers\Web\SuppliesPurchaseController;
use App\Http\Controllers\Web\StockAdjustmentController;
use App\Http\Controllers\Web\StockCardController;
use App\Http\Controllers\Web\StockOpeningController;
use App\Http\Controllers\Web\SupplierController;
use App\Http\Controllers\Web\UomController;
use App\Http\Controllers\Web\WarehouseController;
use Illuminate\Support\Facades\Route;

// Dev-only: reset opcache via browser. Hit GET /__opcache-reset
Route::get('/__opcache-reset', function () {
    $report = [];
    $report[] = 'PHP: ' . PHP_VERSION;
    $report[] = 'SAPI: ' . PHP_SAPI;
    if (function_exists('opcache_get_status')) {
        $s = @opcache_get_status(false);
        $report[] = 'opcache enabled: ' . ($s ? 'YES' : 'NO/RESTRICTED');
        if ($s) $report[] = 'cached scripts: ' . ($s['opcache_statistics']['num_cached_scripts'] ?? '?');
    } else {
        $report[] = 'opcache_get_status not available';
    }
    if (function_exists('opcache_reset')) {
        $ok = @opcache_reset();
        $report[] = 'opcache_reset(): ' . ($ok ? 'OK cleared' : 'FAILED (restrict_api set?)');
    } else {
        $report[] = 'opcache_reset not available';
    }
    // Hard-invalidate the two service files
    if (function_exists('opcache_invalidate')) {
        foreach ([
            app_path('Services/StockAdjustmentService.php'),
            app_path('Services/StockMovementService.php'),
            app_path('Services/SalesOrderService.php'),
        ] as $f) {
            $r = @opcache_invalidate($f, true);
            $report[] = "invalidate $f: " . ($r ? 'OK' : 'FAIL');
        }
    }
    return '<pre>' . implode("\n", $report) . '</pre>';
});

// ===== PORTAL CUSTOMER (PUBLIC — no auth) =====
// Root domain = portal. Akses bebas, no login.
// Halaman home dipasangi LogPortalVisit supaya pengunjung tercatat.
Route::get('/',                    [\App\Http\Controllers\Portal\PortalController::class, 'index'])
    ->middleware(\App\Http\Middleware\LogPortalVisit::class)
    ->name('portal.home');
Route::get('/portal/products.json',[\App\Http\Controllers\Portal\PortalController::class, 'productsJson'])->name('portal.products');

// Endpoint anonim untuk catat lead (intent checkout via WA). Tidak butuh CSRF
// (lihat bootstrap/app.php → validateCsrfTokens.except).
Route::post('/portal/lead', [\App\Http\Controllers\Portal\PortalAnalyticsController::class, 'recordLead'])
    ->name('portal.lead');

// Public — link kuitansi untuk customer (signed URL, tanpa login)
Route::get('/p/so/{salesOrder}/receipt', [SalesOrderController::class, 'publicPrint'])
    ->whereNumber('salesOrder')
    ->middleware('signed')
    ->name('sales_orders.public-print');

// Public — viewer QRIS dengan tombol Download (dipakai di pesan WA customer)
Route::get('/p/qris/{paymentMethod}', [PaymentMethodController::class, 'qrisView'])
    ->whereNumber('paymentMethod')
    ->name('payment_methods.qris-view');

// ===== ADMIN AREA (semua di prefix /admin) =====
Route::prefix('admin')->group(function () {

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
        Route::middleware('permission:stock_adjustment.create')->group(function () {
            Route::post('/{product}/update-stock', [ProductController::class, 'updateStock'])->whereNumber('product')->name('update-stock');
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

    // ========== MASTER — Payment Method ==========
    Route::prefix('payment-methods')->name('payment_methods.')->group(function () {
        Route::middleware('permission:payment_method.create')->group(function () {
            Route::get('/create', [PaymentMethodController::class, 'create'])->name('create');
            Route::post('/',      [PaymentMethodController::class, 'store'])->name('store');
        });
        Route::middleware('permission:payment_method.view')->group(function () {
            Route::get('/', [PaymentMethodController::class, 'index'])->name('index');
        });
        Route::middleware('permission:payment_method.update')->group(function () {
            Route::get('/{payment_method}/edit', [PaymentMethodController::class, 'edit'])->whereNumber('payment_method')->name('edit');
            Route::put('/{payment_method}',      [PaymentMethodController::class, 'update'])->whereNumber('payment_method')->name('update');
        });
        Route::middleware('permission:payment_method.delete')->group(function () {
            Route::delete('/{payment_method}', [PaymentMethodController::class, 'destroy'])->whereNumber('payment_method')->name('destroy');
        });
    });

    // ========== SALES — Piutang (Receivables) ==========
    Route::prefix('receivables')->name('receivables.')->middleware('permission:receivable.view')->group(function () {
        Route::get('/', [ReceivableController::class, 'index'])->name('index');
    });

    // ========== SALES — Kontrak Harga Customer ==========
    Route::prefix('customer-prices')->name('customer_prices.')->group(function () {
        Route::middleware('permission:customer_price.view')->group(function () {
            Route::get('/', [CustomerPriceController::class, 'index'])->name('index');
        });
        Route::middleware('permission:customer_price.create')->group(function () {
            Route::get('/create', [CustomerPriceController::class, 'create'])->name('create');
            Route::post('/',      [CustomerPriceController::class, 'store'])->name('store');
        });
        Route::middleware('permission:customer_price.update')->group(function () {
            Route::get('/{customerPrice}/edit', [CustomerPriceController::class, 'edit'])->whereNumber('customerPrice')->name('edit');
            Route::put('/{customerPrice}',      [CustomerPriceController::class, 'update'])->whereNumber('customerPrice')->name('update');
        });
        Route::middleware('permission:customer_price.delete')->group(function () {
            Route::delete('/{customerPrice}', [CustomerPriceController::class, 'destroy'])->whereNumber('customerPrice')->name('destroy');
        });
    });

    // ========== SALES — Sales Order ==========
    Route::prefix('sales-orders')->name('sales_orders.')->group(function () {
        Route::middleware('permission:sales_order.view')->group(function () {
            Route::get('/available-stock',  [SalesOrderController::class, 'availableStock'])->name('available-stock');
            Route::get('/resolved-price',   [SalesOrderController::class, 'resolvedPrice'])->name('resolved-price');
        });
        Route::middleware('permission:sales_order.create')->group(function () {
            Route::get('/create', [SalesOrderController::class, 'create'])->name('create');
            Route::post('/',      [SalesOrderController::class, 'store'])->name('store');
        });
        Route::middleware('permission:sales_order.view')->group(function () {
            Route::get('/',                    [SalesOrderController::class, 'index'])->name('index');
            Route::get('/{salesOrder}',        [SalesOrderController::class, 'show'])->whereNumber('salesOrder')->name('show');
        });
        Route::middleware('permission:sales_order.print')->group(function () {
            Route::get('/{salesOrder}/print',  [SalesOrderController::class, 'print'])->whereNumber('salesOrder')->name('print');
        });
        Route::middleware('permission:sales_order.update')->group(function () {
            Route::get('/{salesOrder}/edit',                [SalesOrderController::class, 'edit'])->whereNumber('salesOrder')->name('edit');
            Route::put('/{salesOrder}',                     [SalesOrderController::class, 'update'])->whereNumber('salesOrder')->name('update');
            Route::patch('/{salesOrder}/payment-method',    [SalesOrderController::class, 'updatePaymentMethod'])->whereNumber('salesOrder')->name('payment-method.update');
        });
        Route::middleware('permission:sales_order.confirm')->group(function () {
            Route::post('/{salesOrder}/confirm', [SalesOrderController::class, 'confirm'])->whereNumber('salesOrder')->name('confirm');
        });
        Route::middleware('permission:sales_order.cancel')->group(function () {
            Route::post('/{salesOrder}/cancel',  [SalesOrderController::class, 'cancel'])->whereNumber('salesOrder')->name('cancel');
        });
        Route::middleware('permission:sales_order.mark_paid')->group(function () {
            Route::post('/{salesOrder}/mark-paid', [SalesOrderController::class, 'markPaid'])->whereNumber('salesOrder')->name('mark-paid');
        });
        Route::middleware('permission:sales_order.fulfill')->group(function () {
            Route::post('/{salesOrder}/fulfill', [SalesOrderController::class, 'fulfill'])->whereNumber('salesOrder')->name('fulfill');
        });
        Route::middleware('permission:sales_order.update')->group(function () {
            Route::post('/{salesOrder}/items', [SalesOrderController::class, 'appendItem'])->whereNumber('salesOrder')->name('items.append');
        });
    });

    // ========== SALES — Delivery Order ==========
    Route::prefix('delivery-orders')->name('delivery_orders.')->group(function () {
        Route::middleware('permission:delivery_order.view')->group(function () {
            Route::get('/so-items', [DeliveryOrderController::class, 'soItems'])->name('so-items');
        });
        Route::middleware('permission:delivery_order.create')->group(function () {
            Route::get('/create', [DeliveryOrderController::class, 'create'])->name('create');
            Route::post('/',      [DeliveryOrderController::class, 'store'])->name('store');
        });
        Route::middleware('permission:delivery_order.view')->group(function () {
            Route::get('/',                       [DeliveryOrderController::class, 'index'])->name('index');
            Route::get('/{deliveryOrder}',        [DeliveryOrderController::class, 'show'])->whereNumber('deliveryOrder')->name('show');
        });
        Route::middleware('permission:delivery_order.print')->group(function () {
            Route::get('/{deliveryOrder}/print',  [DeliveryOrderController::class, 'print'])->whereNumber('deliveryOrder')->name('print');
        });
        Route::middleware('permission:delivery_order.ship')->group(function () {
            Route::post('/{deliveryOrder}/ship',  [DeliveryOrderController::class, 'ship'])->whereNumber('deliveryOrder')->name('ship');
        });
        Route::middleware('permission:delivery_order.cancel')->group(function () {
            Route::post('/{deliveryOrder}/cancel', [DeliveryOrderController::class, 'cancel'])->whereNumber('deliveryOrder')->name('cancel');
        });
    });

    // ========== INVOICING — Invoice ==========
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::middleware('permission:invoice.view')->group(function () {
            Route::get('/',              [InvoiceController::class, 'index'])->name('index');
            Route::get('/{invoice}',     [InvoiceController::class, 'show'])->whereNumber('invoice')->name('show');
        });
        Route::middleware('permission:invoice.print')->group(function () {
            Route::get('/{invoice}/print', [InvoiceController::class, 'print'])->whereNumber('invoice')->name('print');
        });
        Route::middleware('permission:invoice.create')->group(function () {
            Route::post('/from-do/{deliveryOrder}', [InvoiceController::class, 'createFromDO'])->whereNumber('deliveryOrder')->name('create-from-do');
        });
        Route::middleware('permission:invoice.cancel')->group(function () {
            Route::post('/{invoice}/cancel', [InvoiceController::class, 'cancel'])->whereNumber('invoice')->name('cancel');
        });
        Route::middleware('permission:payment.create')->group(function () {
            Route::post('/{invoice}/quick-pay', [InvoiceController::class, 'quickPay'])->whereNumber('invoice')->name('quick-pay');
        });
    });

    // ========== INVOICING — Payment ==========
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::middleware('permission:payment.view')->group(function () {
            Route::get('/outstanding-invoices', [PaymentController::class, 'outstandingInvoices'])->name('outstanding-invoices');
        });
        Route::middleware('permission:payment.create')->group(function () {
            Route::get('/create', [PaymentController::class, 'create'])->name('create');
            Route::post('/',      [PaymentController::class, 'store'])->name('store');
        });
        Route::middleware('permission:payment.view')->group(function () {
            Route::get('/',           [PaymentController::class, 'index'])->name('index');
            Route::get('/{payment}',  [PaymentController::class, 'show'])->whereNumber('payment')->name('show');
        });
        Route::middleware('permission:payment.cancel')->group(function () {
            Route::post('/{payment}/cancel', [PaymentController::class, 'cancel'])->whereNumber('payment')->name('cancel');
        });
    });

    // ========== MASTER — Employee ==========
    Route::prefix('employees')->name('employees.')->group(function () {
        Route::middleware('permission:employee.create')->group(function () {
            Route::get('/create', [EmployeeController::class, 'create'])->name('create');
            Route::post('/',      [EmployeeController::class, 'store'])->name('store');
        });
        Route::middleware('permission:employee.view')->group(function () {
            Route::get('/', [EmployeeController::class, 'index'])->name('index');
        });
        Route::middleware('permission:employee.update')->group(function () {
            Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->whereNumber('employee')->name('edit');
            Route::put('/{employee}',      [EmployeeController::class, 'update'])->whereNumber('employee')->name('update');
        });
        Route::middleware('permission:employee.delete')->group(function () {
            Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->whereNumber('employee')->name('destroy');
        });
    });

    // ========== MASTER — Service Rate ==========
    Route::prefix('service-rates')->name('service_rates.')->group(function () {
        Route::middleware('permission:service_rate.create')->group(function () {
            Route::get('/create', [ServiceRateController::class, 'create'])->name('create');
            Route::post('/',      [ServiceRateController::class, 'store'])->name('store');
        });
        Route::middleware('permission:service_rate.view')->group(function () {
            Route::get('/', [ServiceRateController::class, 'index'])->name('index');
        });
        Route::middleware('permission:service_rate.update')->group(function () {
            Route::get('/{service_rate}/edit', [ServiceRateController::class, 'edit'])->whereNumber('service_rate')->name('edit');
            Route::put('/{service_rate}',      [ServiceRateController::class, 'update'])->whereNumber('service_rate')->name('update');
        });
        Route::middleware('permission:service_rate.delete')->group(function () {
            Route::delete('/{service_rate}', [ServiceRateController::class, 'destroy'])->whereNumber('service_rate')->name('destroy');
        });
    });

    // ========== PEMBELIAN — Cleaning Service ==========
    Route::prefix('cleaning-services')->name('cleaning_services.')->group(function () {
        Route::middleware('permission:cleaning_service.create')->group(function () {
            Route::get('/create', [CleaningServiceController::class, 'create'])->name('create');
            Route::post('/',      [CleaningServiceController::class, 'store'])->name('store');
        });
        Route::middleware('permission:cleaning_service.view')->group(function () {
            Route::get('/', [CleaningServiceController::class, 'index'])->name('index');
        });
        Route::middleware('permission:cleaning_service.update')->group(function () {
            Route::get('/{cleaningService}/edit', [CleaningServiceController::class, 'edit'])->whereNumber('cleaningService')->name('edit');
            Route::put('/{cleaningService}',      [CleaningServiceController::class, 'update'])->whereNumber('cleaningService')->name('update');
        });
        Route::middleware('permission:cleaning_service.delete')->group(function () {
            Route::delete('/{cleaningService}', [CleaningServiceController::class, 'destroy'])->whereNumber('cleaningService')->name('destroy');
        });
    });

    // ========== PEMBELIAN — Supplies Purchase ==========
    Route::prefix('supplies-purchases')->name('supplies_purchases.')->group(function () {
        Route::middleware('permission:supplies_purchase.create')->group(function () {
            Route::get('/create', [SuppliesPurchaseController::class, 'create'])->name('create');
            Route::post('/',      [SuppliesPurchaseController::class, 'store'])->name('store');
        });
        Route::middleware('permission:supplies_purchase.view')->group(function () {
            Route::get('/', [SuppliesPurchaseController::class, 'index'])->name('index');
        });
        Route::middleware('permission:supplies_purchase.update')->group(function () {
            Route::get('/{suppliesPurchase}/edit', [SuppliesPurchaseController::class, 'edit'])->whereNumber('suppliesPurchase')->name('edit');
            Route::put('/{suppliesPurchase}',      [SuppliesPurchaseController::class, 'update'])->whereNumber('suppliesPurchase')->name('update');
        });
        Route::middleware('permission:supplies_purchase.delete')->group(function () {
            Route::delete('/{suppliesPurchase}', [SuppliesPurchaseController::class, 'destroy'])->whereNumber('suppliesPurchase')->name('destroy');
        });
    });

    // ========== INVENTORY — Purchase Order ==========
    Route::prefix('purchase-orders')->name('purchase_orders.')->group(function () {
        Route::middleware('permission:purchase_order.create')->group(function () {
            Route::get('/create', [PurchaseOrderController::class, 'create'])->name('create');
            Route::post('/',      [PurchaseOrderController::class, 'store'])->name('store');
        });
        Route::middleware('permission:purchase_order.view')->group(function () {
            Route::get('/',                    [PurchaseOrderController::class, 'index'])->name('index');
            Route::get('/{purchaseOrder}',     [PurchaseOrderController::class, 'show'])->whereNumber('purchaseOrder')->name('show');
        });
        Route::middleware('permission:purchase_order.print')->group(function () {
            Route::get('/{purchaseOrder}/print', [PurchaseOrderController::class, 'print'])->whereNumber('purchaseOrder')->name('print');
        });
        Route::middleware('permission:purchase_order.update')->group(function () {
            Route::get('/{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])->whereNumber('purchaseOrder')->name('edit');
            Route::put('/{purchaseOrder}',      [PurchaseOrderController::class, 'update'])->whereNumber('purchaseOrder')->name('update');
        });
        Route::middleware('permission:purchase_order.submit')->group(function () {
            Route::post('/{purchaseOrder}/submit', [PurchaseOrderController::class, 'submit'])->whereNumber('purchaseOrder')->name('submit');
        });
        Route::middleware('permission:purchase_order.cancel')->group(function () {
            Route::post('/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->whereNumber('purchaseOrder')->name('cancel');
        });
        Route::middleware('permission:purchase_order.mark_paid')->group(function () {
            Route::post('/{purchaseOrder}/mark-paid', [PurchaseOrderController::class, 'markPaid'])->whereNumber('purchaseOrder')->name('mark-paid');
        });
    });

    // ========== INVENTORY — Stock Opening ==========
    Route::prefix('stock-openings')->name('stock_openings.')->group(function () {
        Route::middleware('permission:stock_opening.create')->group(function () {
            Route::get('/create', [StockOpeningController::class, 'create'])->name('create');
            Route::post('/',      [StockOpeningController::class, 'store'])->name('store');
        });
        Route::middleware('permission:stock_opening.view')->group(function () {
            Route::get('/',                [StockOpeningController::class, 'index'])->name('index');
            Route::get('/{stockOpening}',  [StockOpeningController::class, 'show'])->whereNumber('stockOpening')->name('show');
        });
    });

    // ========== INVENTORY — Stock Adjustment ==========
    Route::prefix('stock-adjustments')->name('stock_adjustments.')->group(function () {
        Route::middleware('permission:stock_adjustment.view')->group(function () {
            Route::get('/batches', [StockAdjustmentController::class, 'batches'])->name('batches');
        });
        Route::middleware('permission:stock_adjustment.create')->group(function () {
            Route::get('/create', [StockAdjustmentController::class, 'create'])->name('create');
            Route::post('/',      [StockAdjustmentController::class, 'store'])->name('store');
        });
        Route::middleware('permission:stock_adjustment.view')->group(function () {
            Route::get('/',                  [StockAdjustmentController::class, 'index'])->name('index');
            Route::get('/{stockAdjustment}', [StockAdjustmentController::class, 'show'])->whereNumber('stockAdjustment')->name('show');
        });
    });

    // ========== INVENTORY — Stock Card (Kartu Stok) ==========
    Route::prefix('stock-cards')->name('stock_cards.')->middleware('permission:stock_card.view')->group(function () {
        Route::get('/',             [StockCardController::class, 'index'])->name('index');
        Route::get('/{stockCard}',  [StockCardController::class, 'show'])->whereNumber('stockCard')->name('show');
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
}); // end /admin prefix
