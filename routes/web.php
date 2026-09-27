<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PlatformSettingController as AdminPlatformSettingController;
use App\Http\Controllers\Admin\TenantController as AdminTenantController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\Admin\PlatformSettingController as ApiAdminPlatformSettingController;
use App\Http\Controllers\Api\V1\Admin\TenantController as ApiAdminTenantController;
use App\Http\Controllers\Api\V1\Admin\UserController as ApiAdminUserController;
use App\Http\Controllers\Api\V1\BillingSettingController as ApiBillingSettingController;
use App\Http\Controllers\Api\V1\CashSessionController as ApiCashSessionController;
use App\Http\Controllers\Api\V1\CustomerController as ApiCustomerController;
use App\Http\Controllers\Api\V1\DiningTableController as ApiDiningTableController;
use App\Http\Controllers\Api\V1\DocumentController as ApiDocumentController;
use App\Http\Controllers\Api\V1\KitchenController as ApiKitchenController;
use App\Http\Controllers\Api\V1\MemberController as ApiMemberController;
use App\Http\Controllers\Api\V1\MenuCategoryController as ApiMenuCategoryController;
use App\Http\Controllers\Api\V1\MenuController as ApiMenuController;
use App\Http\Controllers\Api\V1\ModifierGroupController as ApiModifierGroupController;
use App\Http\Controllers\Api\V1\OrderController as ApiOrderController;
use App\Http\Controllers\Api\V1\OrderItemController as ApiOrderItemController;
use App\Http\Controllers\Api\V1\PaymentController as ApiPaymentController;
use App\Http\Controllers\Api\V1\ProductController as ApiProductController;
use App\Http\Controllers\Api\V1\ProductImageController as ApiProductImageController;
use App\Http\Controllers\Api\V1\ReportController as ApiReportController;
use App\Http\Controllers\Api\V1\RoleController as ApiRoleController;
use App\Http\Controllers\Api\V1\ZoneController as ApiZoneController;
use App\Http\Controllers\App\BillingSettingController as AppBillingSettingController;
use App\Http\Controllers\App\CashController as AppCashController;
use App\Http\Controllers\App\CustomerController as AppCustomerController;
use App\Http\Controllers\App\DashboardController as AppDashboardController;
use App\Http\Controllers\App\DiningTableController as AppDiningTableController;
use App\Http\Controllers\App\DocumentController as AppDocumentController;
use App\Http\Controllers\App\KitchenController as AppKitchenController;
use App\Http\Controllers\App\MemberController as AppMemberController;
use App\Http\Controllers\App\MenuCategoryController as AppMenuCategoryController;
use App\Http\Controllers\App\ModifierGroupController as AppModifierGroupController;
use App\Http\Controllers\App\OrderController as AppOrderController;
use App\Http\Controllers\App\ProductController as AppProductController;
use App\Http\Controllers\App\ProfileController;
use App\Http\Controllers\App\RoleController as AppRoleController;
use App\Http\Controllers\App\TenantSettingsController;
use App\Http\Controllers\App\ZoneController as AppZoneController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\TenantSwitchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Central (no tenant)
|--------------------------------------------------------------------------
*/

Route::get('/', LandingController::class)->name('landing');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::post('tenant/switch', [TenantSwitchController::class, 'store'])->name('tenant.switch');
});

/*
|--------------------------------------------------------------------------
| Platform admin (super admin only)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'super-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::get('tenants', [AdminTenantController::class, 'index'])->name('tenants.index');
    Route::post('tenants/{tenant}/enter', [AdminTenantController::class, 'enter'])->name('tenants.enter');
    Route::post('leave', [AdminTenantController::class, 'leave'])->name('leave');
    Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('settings/integrations', [AdminPlatformSettingController::class, 'edit'])->name('settings.integrations');
});

/*
|--------------------------------------------------------------------------
| Tenant application (Inertia pages)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'tenant'])->prefix('app')->name('app.')->group(function () {
    Route::get('/', AppDashboardController::class)->name('dashboard');
    Route::get('zones', [AppZoneController::class, 'index'])->name('zones')->middleware('can:tables.manage');
    Route::get('tables', [AppDiningTableController::class, 'index'])->name('tables')->middleware('can:tables.view');
    Route::get('orders', [AppOrderController::class, 'index'])->name('orders')->middleware('can:orders.view');
    Route::get('pos', [AppOrderController::class, 'pos'])->name('pos')->middleware('can:orders.create');
    Route::get('cash', [AppCashController::class, 'index'])->name('cash')->middleware('can:cash.view');
    Route::get('documents', [AppDocumentController::class, 'index'])->name('documents')->middleware('can:documents.view');
    Route::get('kitchen', [AppKitchenController::class, 'index'])->name('kitchen')->middleware('can:kitchen.view');
    Route::get('customers', [AppCustomerController::class, 'index'])->name('customers')->middleware('can:customers.view');
    Route::get('menu/categories', [AppMenuCategoryController::class, 'index'])->name('menu.categories')->middleware('can:menu.view');
    Route::get('menu/products', [AppProductController::class, 'index'])->name('menu.products')->middleware('can:menu.view');
    Route::get('modifiers', [AppModifierGroupController::class, 'index'])->name('modifiers')->middleware('can:menu.view');
    Route::get('members', [AppMemberController::class, 'index'])->name('members.index')->middleware('can:members.view');
    Route::get('roles', [AppRoleController::class, 'index'])->name('roles.index')->middleware('can:roles.view');
    Route::get('settings', [TenantSettingsController::class, 'edit'])->name('settings.edit')->middleware('can:settings.view');
    Route::put('settings', [TenantSettingsController::class, 'update'])->name('settings.update')->middleware('can:settings.manage');
    Route::get('settings/billing', [AppBillingSettingController::class, 'edit'])->name('billing.settings')->middleware('can:billing.manage');
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
});

/*
|--------------------------------------------------------------------------
| JSON API for the SPA (session + CSRF, same domain)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'tenant', 'throttle:api'])
    ->prefix('api/v1')
    ->name('api.')
    ->group(function () {
        Route::get('roles', [ApiRoleController::class, 'index'])->name('roles.index');

        Route::get('zones', [ApiZoneController::class, 'index'])->name('zones.index');
        Route::post('zones', [ApiZoneController::class, 'store'])->name('zones.store');
        Route::get('zones/{zone}', [ApiZoneController::class, 'show'])->name('zones.show');
        Route::patch('zones/{zone}', [ApiZoneController::class, 'update'])->name('zones.update');
        Route::delete('zones/{zone}', [ApiZoneController::class, 'destroy'])->name('zones.destroy');

        Route::get('dining-tables', [ApiDiningTableController::class, 'index'])->name('dining-tables.index');
        Route::post('dining-tables', [ApiDiningTableController::class, 'store'])->name('dining-tables.store');
        Route::get('dining-tables/{table}', [ApiDiningTableController::class, 'show'])->name('dining-tables.show');
        Route::patch('dining-tables/{table}', [ApiDiningTableController::class, 'update'])->name('dining-tables.update');
        Route::delete('dining-tables/{table}', [ApiDiningTableController::class, 'destroy'])->name('dining-tables.destroy');

        Route::get('menu-categories', [ApiMenuCategoryController::class, 'index'])->name('menu-categories.index');
        Route::post('menu-categories', [ApiMenuCategoryController::class, 'store'])->name('menu-categories.store');
        Route::get('menu-categories/{category}', [ApiMenuCategoryController::class, 'show'])->name('menu-categories.show');
        Route::patch('menu-categories/{category}', [ApiMenuCategoryController::class, 'update'])->name('menu-categories.update');
        Route::delete('menu-categories/{category}', [ApiMenuCategoryController::class, 'destroy'])->name('menu-categories.destroy');

        Route::get('products', [ApiProductController::class, 'index'])->name('products.index');
        Route::post('products', [ApiProductController::class, 'store'])->name('products.store');
        Route::get('products/{product}', [ApiProductController::class, 'show'])->name('products.show');
        Route::patch('products/{product}', [ApiProductController::class, 'update'])->name('products.update');
        Route::delete('products/{product}', [ApiProductController::class, 'destroy'])->name('products.destroy');
        Route::post('products/{product}/images', [ApiProductImageController::class, 'store'])->name('products.images.store');
        Route::delete('product-images/{image}', [ApiProductImageController::class, 'destroy'])->name('product-images.destroy');

        Route::get('modifier-groups', [ApiModifierGroupController::class, 'index'])->name('modifier-groups.index');
        Route::post('modifier-groups', [ApiModifierGroupController::class, 'store'])->name('modifier-groups.store');
        Route::get('modifier-groups/{modifier_group}', [ApiModifierGroupController::class, 'show'])->name('modifier-groups.show');
        Route::patch('modifier-groups/{modifier_group}', [ApiModifierGroupController::class, 'update'])->name('modifier-groups.update');
        Route::delete('modifier-groups/{modifier_group}', [ApiModifierGroupController::class, 'destroy'])->name('modifier-groups.destroy');

        Route::get('menu', [ApiMenuController::class, 'index'])->name('menu');

        Route::get('kitchen', [ApiKitchenController::class, 'index'])->name('kitchen');

        Route::get('orders', [ApiOrderController::class, 'index'])->name('orders.index');
        Route::post('orders', [ApiOrderController::class, 'store'])->name('orders.store');
        Route::get('orders/{order}', [ApiOrderController::class, 'show'])->name('orders.show');
        Route::patch('orders/{order}', [ApiOrderController::class, 'update'])->name('orders.update');
        Route::post('orders/{order}/send', [ApiOrderController::class, 'send'])->name('orders.send');
        Route::post('orders/{order}/cancel', [ApiOrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('orders/{order}/items', [ApiOrderItemController::class, 'store'])->name('orders.items.store');

        Route::patch('order-items/{item}', [ApiOrderItemController::class, 'update'])->name('order-items.update');
        Route::patch('order-items/{item}/status', [ApiOrderItemController::class, 'status'])->name('order-items.status');
        Route::delete('order-items/{item}', [ApiOrderItemController::class, 'destroy'])->name('order-items.destroy');

        Route::get('cash/session', [ApiCashSessionController::class, 'current'])->name('cash.session.current');
        Route::get('cash/sessions', [ApiCashSessionController::class, 'index'])->name('cash.sessions.index');
        Route::post('cash/sessions', [ApiCashSessionController::class, 'store'])->name('cash.sessions.store');
        Route::get('cash/sessions/{session}', [ApiCashSessionController::class, 'show'])->name('cash.sessions.show');
        Route::post('cash/sessions/{session}/close', [ApiCashSessionController::class, 'close'])->name('cash.sessions.close');
        Route::post('cash/sessions/{session}/movements', [ApiCashSessionController::class, 'storeMovement'])->name('cash.movements.store');

        Route::get('payments', [ApiPaymentController::class, 'index'])->name('payments.index');
        Route::get('orders/{order}/payments', [ApiPaymentController::class, 'ordersIndex'])->name('orders.payments.index');
        Route::post('orders/{order}/payments', [ApiPaymentController::class, 'store'])->name('orders.payments.store');

        Route::get('billing/settings', [ApiBillingSettingController::class, 'show'])->name('billing.settings.show');
        Route::put('billing/settings', [ApiBillingSettingController::class, 'update'])->name('billing.settings.update');
        Route::post('billing/settings/certificate', [ApiBillingSettingController::class, 'storeCertificate'])->name('billing.settings.certificate');

        Route::get('customers', [ApiCustomerController::class, 'index'])->name('customers.index');
        Route::post('customers', [ApiCustomerController::class, 'store'])->name('customers.store');
        Route::get('customers/{customer}', [ApiCustomerController::class, 'show'])->name('customers.show');
        Route::patch('customers/{customer}', [ApiCustomerController::class, 'update'])->name('customers.update');
        Route::delete('customers/{customer}', [ApiCustomerController::class, 'destroy'])->name('customers.destroy');

        Route::get('documents', [ApiDocumentController::class, 'index'])->name('documents.index');
        Route::get('documents/{document}', [ApiDocumentController::class, 'show'])->name('documents.show');
        Route::post('documents/{document}/annul', [ApiDocumentController::class, 'annul'])->name('documents.annul');
        Route::get('documents/{document}/pdf', [ApiDocumentController::class, 'pdf'])->name('documents.pdf');
        Route::get('orders/{order}/documents', [ApiDocumentController::class, 'indexForOrder'])->name('orders.documents.index');
        Route::post('orders/{order}/documents', [ApiDocumentController::class, 'storeForOrder'])->name('orders.documents.store');

        Route::get('reports/dashboard', [ApiReportController::class, 'dashboard'])->name('reports.dashboard');

        Route::get('members', [ApiMemberController::class, 'index'])->name('members.index');
        Route::post('members', [ApiMemberController::class, 'store'])->name('members.store');
        Route::delete('members/{member}', [ApiMemberController::class, 'destroy'])->name('members.destroy');
    });

Route::middleware(['auth', 'super-admin', 'throttle:api'])
    ->prefix('api/v1/admin')
    ->name('api.admin.')
    ->group(function () {
        Route::get('tenants', [ApiAdminTenantController::class, 'index'])->name('tenants.index');
        Route::post('tenants', [ApiAdminTenantController::class, 'store'])->name('tenants.store');
        Route::patch('tenants/{tenant}', [ApiAdminTenantController::class, 'update'])->name('tenants.update');
        Route::delete('tenants/{tenant}', [ApiAdminTenantController::class, 'destroy'])->name('tenants.destroy');
        Route::post('tenants/{tenant}/members', [ApiAdminTenantController::class, 'grantAccess'])->name('tenants.members.store');
        Route::get('users', [ApiAdminUserController::class, 'index'])->name('users.index');

        Route::get('settings/integrations', [ApiAdminPlatformSettingController::class, 'show'])->name('settings.integrations.show');
        Route::put('settings/integrations', [ApiAdminPlatformSettingController::class, 'update'])->name('settings.integrations.update');
        Route::post('settings/integrations/test', [ApiAdminPlatformSettingController::class, 'test'])->name('settings.integrations.test');
    });
