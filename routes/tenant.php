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
| Subdomain-based tenant routes (primary)
|--------------------------------------------------------------------------
| Anything under {slug}.lexa.test resolves through the subdomain
| middleware. PreventAccessFromCentralDomains blocks the same paths
| from being hit on the bare central domain.
*/
Route::middleware([
    'web',
    InitializeTenancyByDomainOrSubdomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
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
| For contributors who cannot edit their hosts file. Production should
| disable this group via config and rely on subdomains only.
*/
Route::middleware([
    'web',
    InitializeTenancyByPath::class,
])
    ->prefix('/t/{tenant}')
    ->group(function () {
        Route::view('/', 'tenant.dashboard')->name('tenant.dashboard.path');
    });
