<?php

use App\Http\Controllers\Backend\CurrencyController;
use App\Http\Controllers\Backend\Pos\CartController;
use App\Http\Controllers\Backend\Product\ProductController;
use App\Http\Controllers\Backend\Report\ReportController;
use App\Http\Controllers\Backend\SupplierController;
use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Backend\Product\CategoryController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\RolePermission\PermissionController;
use App\Http\Controllers\Backend\Pos\OrderController;
use App\Http\Controllers\Backend\Product\BrandController;
use App\Http\Controllers\Backend\Product\PurchaseController;
use App\Http\Controllers\Backend\RolePermission\RoleController;
use App\Http\Controllers\Backend\Product\UnitController;
use App\Http\Controllers\Backend\UserManagementController;
use App\Http\Controllers\Backend\WebsiteSettingController;
use App\Models\Supplier;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// ====================== FRONTEND ======================

// homepage
Route::get('/', function () {
    // Check license activation
    if (!\App\Helpers\LicenseHelper::isActivated()) {
        return redirect()->route('license.activate.show');
    }
    
    return to_route('login');
})->name('frontend.home');

// API endpoint for license check (used by Electron)
Route::get('/api/license-check', function () {
    $activated = \App\Helpers\LicenseHelper::isActivated();
    return response()->json([
        'activated' => $activated,
    ]);
});

// Standalone Activation routes (Public)
Route::get('/activate', [\App\Http\Controllers\Backend\LicenseController::class, 'showActivate'])->name('license.activate.show');
Route::post('/activate', [\App\Http\Controllers\Backend\LicenseController::class, 'activatePublic'])->name('license.activate.public');
Route::get('admin/orders/pos-invoice/{id}', [\App\Http\Controllers\Backend\Pos\OrderController::class, 'posInvoice'])->name('orders.pos-invoice');

//authentication
Route::match(['get', 'post'], 'login', [AuthController::class, 'login'])->name('login');

Route::get('logout', [AuthController::class, 'logout'])->name('logout');
// Public registration disabled - users should be created by admin only
// Route::match(['get', 'post'], 'sign-up', [AuthController::class, 'register'])->name('signup');
Route::match(['get', 'post'], 'forget-password', [AuthController::class, 'forgetPassword'])->name('forget.password');
Route::match(['get', 'post'], 'new-password', [AuthController::class, 'newPassword'])->name('new.password');
Route::match(['get', 'post'], 'password-reset', [AuthController::class, 'resetPassword'])->name('password.reset');
Route::get('resend-otp', [AuthController::class, 'resendOtp'])->name('resend.otp');

// google auth
Route::get('auth/google', [GoogleController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback'])->name('auth.google.handle.callback');

// ====================== /FRONTEND =====================

// ====================== BACKEND =======================

Route::prefix('admin')->as('backend.admin.')->middleware(['admin', 'license'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // License routes
    Route::get('/license', [\App\Http\Controllers\Backend\LicenseController::class, 'index'])->name('license');
    Route::post('/license/activate', [\App\Http\Controllers\Backend\LicenseController::class, 'activate'])->name('license.activate');
    
    Route::get('/barcode', [DashboardController::class, 'barcode'])->name('barcode');
    Route::get('/barcode/print', [DashboardController::class, 'printBarcode'])->name('barcode.print');
    Route::post('/barcode/store', [DashboardController::class, 'storeBarcode'])->name('barcode.store');
    Route::get('/barcode/history', [DashboardController::class, 'getBarcodeHistory'])->name('barcode.history');
    Route::delete('/barcode/{id}', [DashboardController::class, 'deleteBarcode'])->name('barcode.delete');
    Route::resource('products', ProductController::class);
    Route::resource('brands', BrandController::class);
    Route::resource('orders', OrderController::class);
    Route::resource('purchase', PurchaseController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('customers', CustomerController::class);
    Route::resource('products', ProductController::class);
    Route::resource('units', UnitController::class);
    Route::resource('currencies', CurrencyController::class);
    Route::match(['get', 'post'], 'import/products', [ProductController::class,'import'])->name('products.import');
    Route::get('currencies/default/{id}', [CurrencyController::class, 'setDefault'])->name('currencies.setDefault');
    Route::get('customers/orders/{id}', [CustomerController::class, 'orders'])->name('customers.orders');
    Route::get('purchase/products/{id}', [PurchaseController::class, 'purchaseProducts'])->name('purchase.products');
    Route::get('orders/pos-invoice/preview', [OrderController::class, 'previewInvoice'])->name('orders.pos-invoice.preview');
    Route::get('orders/invoice/{id}', [OrderController::class,'invoice'])->name('orders.invoice');
    Route::get('orders/orders/invoice/{id}', [OrderController::class,'invoice'])->name('orders.invoice');
    Route::get('orders/receipt-details/{id}', [OrderController::class, 'receiptDetails'])->name('orders.receipt-details'); // Headless JSON
    // Route::get('orders/pos-invoice/{id}', [OrderController::class, 'posInvoice'])->name('orders.pos-invoice'); -- Moved to Public
    Route::get('orders/transactions/{id}', [OrderController::class, 'transactions'])->name('orders.transactions');
    Route::match(['get', 'post'], 'orders/due/collection/{id}', [OrderController::class, 'collection'])->name('due.collection');
    Route::get('collection/invoice/{id}', [OrderController::class, 'collectionInvoice'])->name('collectionInvoice');
    Route::resource('categories', CategoryController::class);
    //start report

    Route::get('/sale/summery', [ReportController::class, 'saleSummery'])->name('sale.summery');
    Route::get('/sale/report', [ReportController::class, 'saleReport'])->name('sale.report');
    Route::get('/inventory/report', [ReportController::class, 'inventoryReport'])->name('inventory.report');
    Route::get('/refund/report', [ReportController::class, 'refundReport'])->name('refund.report');
    //end report
    
    // Refunds
    Route::get('/refunds', [\App\Http\Controllers\Backend\RefundController::class, 'index'])->name('refunds.index');
    Route::get('/refunds/create/{order}', [\App\Http\Controllers\Backend\RefundController::class, 'create'])->name('refunds.create');
    Route::post('/refunds', [\App\Http\Controllers\Backend\RefundController::class, 'store'])->name('refunds.store');
    Route::get('/refunds/{return}/receipt', [\App\Http\Controllers\Backend\RefundController::class, 'receipt'])->name('refunds.receipt');
    Route::get('/refunds/{return}/details', [\App\Http\Controllers\Backend\RefundController::class, 'refundDetails'])->name('refunds.details'); // Professional JSON API
    
    // start pos
    Route::get('/get/products', [CartController::class, 'getProducts'])->name('getProducts');
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
    Route::put('/cart/increment', [CartController::class, 'increment']);
    Route::put('/cart/decrement', [CartController::class, 'decrement']);
    Route::put('/cart/delete', [CartController::class, 'delete']);
    Route::put('/cart/empty', [CartController::class, 'empty']);
    Route::get('/cart/check-journal', [CartController::class, 'checkJournal']);
    Route::delete('/cart/delete-journal', [CartController::class, 'deleteJournal']);
    Route::put('/cart/update-quantity', [CartController::class, 'updateQuantity']);
    Route::put('/cart/update-rate', [CartController::class, 'updateRate']);

    Route::put('/cart/update-by-price', [CartController::class, 'updateByPrice']);
    Route::put('/order/create', [OrderController::class, 'store']);
    Route::get('/get/customers',[CustomerController::class,'getCustomers']);
    Route::post('/create/customers', [CustomerController::class, 'store']);
    //end pos
    Route::get('profile', [DashboardController::class, 'profile'])->name('profile');
    Route::post('profile/update', [AuthController::class, 'update'])->name('profile.update');

    // user management
    Route::prefix('users')->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('users');
        Route::get('suspend/{id}/{status}', [UserManagementController::class, 'suspend'])->name('user.suspend');
        Route::match(['get', 'post'], 'create', [UserManagementController::class, 'create'])->name('user.create');
        Route::match(['get', 'post'], 'edit/{id}', [UserManagementController::class, 'edit'])->name('user.edit');
        Route::get('delete/{id}', [UserManagementController::class, 'delete'])->name('user.delete');
    });

    // settings
    Route::prefix('settings')->group(function () {
        // website settings
        Route::prefix('website')->group(function () {
            Route::controller(WebsiteSettingController::class)->prefix('general')->group(function () {
                Route::get('/', 'websiteGeneral')->name('settings.website.general');
                Route::post('update-info', 'websiteInfoUpdate')->name('settings.website.info.update');
                Route::post('update-contacts', 'websiteContactsUpdate')->name('settings.website.contacts.update');
                Route::post('update-social-links', 'websiteSocialLinkUpdate')->name('settings.website.social.link.update');
                Route::post('update-style-settings', 'websiteStyleSettingsUpdate')->name('settings.website.style.settings.update');
                Route::post('update-custom-css', 'websiteCustomCssUpdate')->name('settings.website.custom.css.update');
                Route::post('update-notification-settings', 'websiteNotificationSettingsUpdate')->name('settings.website.notification.settings.update');
                Route::post('update-website-status', 'websiteStatusUpdate')->name('settings.website.status.update');

                Route::post('update-invoice-settings', 'websiteInvoiceUpdate')->name('settings.website.invoice.update');
                Route::post('update-printer-settings', 'websitePrinterUpdate')->name('settings.website.printer.update');
            });

            // Backup & Restore routes
            Route::controller(\App\Http\Controllers\Backend\BackupController::class)->prefix('backup')->group(function () {
                Route::get('/', 'index')->name('settings.backup');
                Route::post('settings', 'saveSettings')->name('settings.backup.save');
                Route::post('create', 'createBackup')->name('settings.backup.create');
                Route::get('restore/{filename}', 'restoreBackup')->name('settings.backup.restore');
                Route::get('delete/{filename}', 'deleteBackup')->name('settings.backup.delete');
                Route::get('download/{filename}', 'downloadBackup')->name('settings.backup.download');
            });

            Route::controller(RoleController::class)->prefix('roles')->group(function () {
                Route::get('/', 'index')->name('roles');
                Route::post('create', 'store')->name('roles.create');
                Route::get('show/{id}', 'show')->name('roles.show');
                Route::put('update/{id}', 'update')->name('roles.update');
                Route::get('delete/{id}', 'destroy')->name('roles.delete');
                Route::post('role-permission/{id}', 'updatePermission')->name('update.role-permissions');
                Route::get('role-wise-permissions/{id?}', 'roleWisePermissions')->name('role-wise-permissions');
            });

            Route::controller(PermissionController::class)->prefix('permissions')->group(function () {
                Route::get('/', 'index')->name('permissions');
                Route::post('create', 'store')->name('permissions.store');
                // Route::get('show/{id}', 'show')->name('roles.show');
                Route::put('update/{id}', 'update')->name('permissions.update');
                Route::get('delete/{id}', 'destroy')->name('permissions.delete');
            });

            // Reports & Closing
            Route::controller(\App\Http\Controllers\Backend\Report\DailyReportController::class)->prefix('reports')->group(function () {
                Route::get('daily-closing', 'create')->name('report.daily.closing');
                Route::post('daily-closing', 'store')->name('report.daily.closing.store');
                Route::get('closing-history', 'index')->name('report.daily.history');
            });

            // Audit Logs
            Route::controller(\App\Http\Controllers\Backend\ActivityLogController::class)->prefix('audit')->group(function () {
                Route::get('logs', 'index')->name('activity.logs.index');
            });
        });
    });
});

// ====================== /BACKEND ======================

Route::get('clear-all', function () {
    Artisan::call('optimize:clear');
    return redirect()->back();
});

Route::get('storage-link', function () {
    Artisan::call('storage:link');
    return redirect()->back();
});

Route::get('test', [TestController::class, 'test'])->name('test');
