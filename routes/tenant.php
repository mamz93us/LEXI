<?php

declare(strict_types=1);

use App\Livewire\Calendar\Index as CalendarIndex;
use App\Livewire\Cases\Detail as CaseDetail;
use App\Livewire\Cases\Form as CaseForm;
use App\Livewire\Cases\Index as CasesIndex;
use App\Livewire\Clients\Form as ClientForm;
use App\Livewire\Clients\Index as ClientsIndex;
use App\Livewire\Companies\Form as CompanyForm;
use App\Livewire\Companies\Index as CompaniesIndex;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Invoices\Form as InvoiceForm;
use App\Livewire\Invoices\Index as InvoicesIndex;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomainOrSubdomain;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant routes
|--------------------------------------------------------------------------
| In production lexi.deevar.cloud is a TENANT domain (not central), so
| these routes resolve there directly: /login, /dashboard, /clients ...
| In dev the same routes also work under any tenant subdomain such as
| samir.lexa.test thanks to InitializeTenancyByDomainOrSubdomain.
|
| PreventAccessFromCentralDomains blocks any central host (configured
| in config/tenancy.php — empty in production) from hitting tenant
| paths.
*/
Route::middleware([
    'web',
    InitializeTenancyByDomainOrSubdomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    // Root → dashboard for authed users, login otherwise.
    Route::get('/', function () {
        return auth()->check()
            ? redirect()->route('dashboard')
            : redirect()->route('login');
    })->name('tenant.root');

    Route::get('dashboard', DashboardIndex::class)
        ->middleware(['auth', 'verified'])
        ->name('dashboard');

    Route::view('profile', 'profile')
        ->middleware(['auth'])
        ->name('profile');

    Route::middleware(['auth'])->group(function () {
        Route::get('clients', ClientsIndex::class)->name('clients.index');
        Route::get('clients/new', ClientForm::class)->name('clients.create');
        Route::get('clients/{client}/edit', ClientForm::class)->name('clients.edit');

        Route::get('cases', CasesIndex::class)->name('cases.index');
        Route::get('cases/new', CaseForm::class)->name('cases.create');
        Route::get('cases/{case}', CaseDetail::class)->name('cases.show');
        Route::get('cases/{case}/edit', CaseForm::class)->name('cases.edit');

        Route::get('calendar', CalendarIndex::class)->name('calendar.index');

        Route::get('companies', CompaniesIndex::class)->name('companies.index');
        Route::get('companies/new', CompanyForm::class)->name('companies.create');
        Route::get('companies/{company}/edit', CompanyForm::class)->name('companies.edit');

        Route::get('invoices', InvoicesIndex::class)->name('invoices.index');
        Route::get('invoices/new', InvoiceForm::class)->name('invoices.create');
        Route::get('invoices/{invoice}/edit', InvoiceForm::class)->name('invoices.edit');
    });

    require __DIR__.'/auth.php';
});

/*
|--------------------------------------------------------------------------
| Path-based fallback (/t/{tenant}/...)
|--------------------------------------------------------------------------
| Kept for contributors who can't edit their hosts file or for a future
| multi-firm deployment without per-firm subdomains. Disabled by simply
| not creating tenants with Domain rows.
*/
Route::middleware([
    'web',
    InitializeTenancyByPath::class,
])
    ->prefix('/t/{tenant}')
    ->group(function () {
        Route::get('/', function () {
            return auth()->check()
                ? redirect()->route('dashboard')
                : redirect()->route('login');
        })->name('tenant.root.path');
    });
