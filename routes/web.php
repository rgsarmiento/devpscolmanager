<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/login');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // AJAX / Helper Routes
    Route::post('/clients/sync-all', [\App\Http\Controllers\ClientController::class, 'syncAll'])->name('clients.sync-all');
    
    // Invoicing Actions for distributors (allowed actions)
    Route::post('/invoicing/{clientId}/software', [\App\Http\Controllers\InvoicingController::class, 'configSoftware'])->name('invoicing.software');
    Route::post('/invoicing/{clientId}/resolution', [\App\Http\Controllers\InvoicingController::class, 'configResolution'])->name('invoicing.resolution');
    Route::post('/invoicing/{clientId}/numbering-range', [\App\Http\Controllers\InvoicingController::class, 'fetchResolutions'])->name('invoicing.numbering-range');
    Route::post('/invoicing/{clientId}/certificate', [\App\Http\Controllers\InvoicingController::class, 'configCertificate'])->name('invoicing.certificate');

    // Admin Only Routes
    Route::middleware('is_admin')->group(function () {
        Route::post('/clients/parse-rut', [\App\Http\Controllers\ClientController::class, 'parseRut'])->name('clients.parse-rut');
        Route::post('/clients/{client}/refresh-plan', [\App\Http\Controllers\ClientController::class, 'refreshPlan'])->name('clients.refresh-plan');
        Route::resource('clients', \App\Http\Controllers\ClientController::class)->except(['index', 'show']);
        
        Route::post('/computers/generate-license', [\App\Http\Controllers\ComputerController::class, 'generateLicense'])->name('computers.generate-license');
        Route::resource('computers', \App\Http\Controllers\ComputerController::class)->except(['index', 'create', 'edit', 'show']);
        
        // Services
        Route::post('/clients/{client}/services', [\App\Http\Controllers\ClientServiceController::class, 'store'])->name('client-services.store');
        Route::put('/clients/{client}/services/{clientService}', [\App\Http\Controllers\ClientServiceController::class, 'update'])->name('client-services.update');
        Route::delete('/clients/{client}/services/{clientService}', [\App\Http\Controllers\ClientServiceController::class, 'destroy'])->name('client-services.destroy');

        // Users and Distributors and Debts
        Route::resource('users', \App\Http\Controllers\UserController::class)->except(['create', 'edit', 'show']);
        Route::resource('distributors', \App\Http\Controllers\DistributorController::class);
        Route::resource('license-packages', \App\Http\Controllers\LicensePackageController::class)->except(['create', 'show', 'edit']);
        Route::resource('folio-rates', \App\Http\Controllers\FolioRateController::class)->only(['store', 'update', 'destroy']);
        Route::resource('service-rates', \App\Http\Controllers\ServiceRateController::class)->only(['store', 'update', 'destroy']);
        Route::resource('debts', \App\Http\Controllers\DebtController::class)->only(['index', 'store']);
        Route::post('/debts/{debt}/pay', [\App\Http\Controllers\DebtController::class, 'pay'])->name('debts.pay');
        Route::post('/license-transactions/{transaction}/pay', [\App\Http\Controllers\DebtController::class, 'payTransaction'])->name('license-transactions.pay');
        Route::post('/license-transactions/{transaction}/renew', [\App\Http\Controllers\DebtController::class, 'renewService'])->name('license-transactions.renew');
        Route::post('/license-transactions/{transaction}/cancel', [\App\Http\Controllers\DebtController::class, 'cancelService'])->name('license-transactions.cancel');
        
        // Invoicing Routes (Admin only)
        Route::post('/invoicing/{clientId}/company', [\App\Http\Controllers\InvoicingController::class, 'configCompany'])->name('invoicing.company');
        Route::post('/invoicing/{clientId}/update-plan', [\App\Http\Controllers\InvoicingController::class, 'updatePlanDirect'])->name('invoicing.update-plan');
        Route::post('/invoicing/{clientId}/test-set', [\App\Http\Controllers\InvoicingController::class, 'sendTestInvoice'])->name('invoicing.test-set');
    });

    // Client access for distributors (read only mostly, with exceptions)
    Route::patch('/clients/{client}/toggle-environment', [\App\Http\Controllers\ClientController::class, 'toggleEnvironment'])->name('clients.toggle-environment');
    Route::patch('/clients/{client}/whatsapp-contact', [\App\Http\Controllers\ClientController::class, 'updateWhatsappContact'])->name('clients.whatsapp-contact');
    Route::resource('clients', \App\Http\Controllers\ClientController::class)->only(['index', 'show']);
    Route::get('/computers', [\App\Http\Controllers\ComputerController::class, 'index'])->name('computers.index');

    // DIAN Catalogs
    Route::apiResource('type-document-identifications', \App\Http\Controllers\TypeDocumentIdentificationController::class)->only(['index', 'show']);
    Route::apiResource('type-liabilities', \App\Http\Controllers\TypeLiabilityController::class)->only(['index', 'show']);
    Route::apiResource('type-organizations', \App\Http\Controllers\TypeOrganizationController::class)->only(['index', 'show']);
    Route::apiResource('type-regimes', \App\Http\Controllers\TypeRegimeController::class)->only(['index', 'show']);
    Route::apiResource('departments', \App\Http\Controllers\DepartmentController::class)->only(['index', 'show']);
    Route::apiResource('municipalities', \App\Http\Controllers\MunicipalityController::class)->only(['index', 'show']);
    
    Route::get('/folio-consumption', [\App\Http\Controllers\FolioConsumptionController::class, 'index'])->name('folio-consumption.index');
    Route::get('/certificates', [\App\Http\Controllers\CertificateController::class, 'index'])->name('certificates.index');
});
