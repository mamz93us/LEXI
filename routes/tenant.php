<?php

declare(strict_types=1);

use App\Livewire\AiDrafter\Detail as AiDrafterDetail;
use App\Livewire\AiDrafter\Index as AiDrafterIndex;
use App\Livewire\AiDrafter\Wizard as AiDrafterWizard;
use App\Livewire\Calendar\Index as CalendarIndex;
use App\Livewire\Cases\Detail as CaseDetail;
use App\Livewire\Cases\Form as CaseForm;
use App\Livewire\Cases\Index as CasesIndex;
use App\Livewire\Clauses\Form as ClauseForm;
use App\Livewire\Clauses\Index as ClausesIndex;
use App\Livewire\Clients\Form as ClientForm;
use App\Livewire\Clients\Index as ClientsIndex;
use App\Livewire\Companies\Form as CompanyForm;
use App\Livewire\Companies\Index as CompaniesIndex;
use App\Livewire\Compliance\Form as ComplianceForm;
use App\Livewire\Compliance\Index as ComplianceIndex;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Documents\Detail as DocumentDetail;
use App\Livewire\Documents\Form as DocumentForm;
use App\Livewire\Documents\Index as DocumentsIndex;
use App\Livewire\Hearings\Index as HearingsIndex;
use App\Livewire\Invoices\Form as InvoiceForm;
use App\Livewire\Invoices\Index as InvoicesIndex;
use App\Livewire\IpAssets\Form as IpAssetForm;
use App\Livewire\IpAssets\Index as IpAssetsIndex;
use App\Livewire\Judgments\Index as JudgmentsIndex;
use App\Livewire\Proxies\Form as ProxyForm;
use App\Livewire\Proxies\Index as ProxiesIndex;
use App\Livewire\Serials\Form as SerialForm;
use App\Livewire\Serials\Index as SerialsIndex;
use App\Livewire\Settings\Index as SettingsIndex;
use App\Livewire\Templates\Form as TemplateForm;
use App\Livewire\Templates\Index as TemplatesIndex;
use App\Livewire\TimeEntries\Form as TimeEntryForm;
use App\Livewire\TimeEntries\Index as TimeEntriesIndex;
use App\Models\DocumentVersion;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomainOrSubdomain;
use Stancl\Tenancy\Middleware\InitializeTenancyByPath;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomainOrSubdomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
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
        // --- Clients ---
        Route::get('clients', ClientsIndex::class)->name('clients.index');
        Route::get('clients/new', ClientForm::class)->name('clients.create');
        Route::get('clients/{client}/edit', ClientForm::class)->name('clients.edit');

        // --- Cases ---
        Route::get('cases', CasesIndex::class)->name('cases.index');
        Route::get('cases/new', CaseForm::class)->name('cases.create');
        Route::get('cases/{case}', CaseDetail::class)->name('cases.show');
        Route::get('cases/{case}/edit', CaseForm::class)->name('cases.edit');

        Route::get('calendar', CalendarIndex::class)->name('calendar.index');

        // Read-only cross-case indexes.
        Route::get('hearings', HearingsIndex::class)->name('hearings.index');
        Route::get('judgments', JudgmentsIndex::class)->name('judgments.index');

        // --- Documents ---
        Route::get('documents', DocumentsIndex::class)->name('documents.index');
        Route::get('documents/new', DocumentForm::class)->name('documents.create');
        Route::get('documents/{document}', DocumentDetail::class)->name('documents.show');
        Route::get('documents/{document}/edit', DocumentForm::class)->name('documents.edit');
        Route::get('documents/version/{version}/download', function (DocumentVersion $version) {
            abort_unless($version->storage_ref && Storage::exists($version->storage_ref), 404);

            return Storage::download(
                $version->storage_ref,
                $version->document->title.'_v'.$version->version_no.'.'.pathinfo($version->storage_ref, PATHINFO_EXTENSION),
            );
        })->name('documents.download');

        // --- Templates + Clauses (RAG inputs) ---
        Route::get('templates', TemplatesIndex::class)->name('templates.index');
        Route::get('templates/new', TemplateForm::class)->name('templates.create');
        Route::get('templates/{template}/edit', TemplateForm::class)->name('templates.edit');

        Route::get('clauses', ClausesIndex::class)->name('clauses.index');
        Route::get('clauses/new', ClauseForm::class)->name('clauses.create');
        Route::get('clauses/{clause}/edit', ClauseForm::class)->name('clauses.edit');

        // --- AI drafter ---
        Route::get('ai-drafter', AiDrafterIndex::class)->name('ai-drafter.index');
        Route::get('ai-drafter/new', AiDrafterWizard::class)->name('ai-drafter.wizard');
        Route::get('ai-drafter/{generation}', AiDrafterDetail::class)->name('ai-drafter.show');

        // --- Proxies ---
        Route::get('proxies', ProxiesIndex::class)->name('proxies.index');
        Route::get('proxies/new', ProxyForm::class)->name('proxies.create');
        Route::get('proxies/{proxy}/edit', ProxyForm::class)->name('proxies.edit');

        // --- Companies (already wired) + nested compliance + IP + serials ---
        Route::get('companies', CompaniesIndex::class)->name('companies.index');
        Route::get('companies/new', CompanyForm::class)->name('companies.create');
        Route::get('companies/{company}/edit', CompanyForm::class)->name('companies.edit');

        Route::get('compliance', ComplianceIndex::class)->name('compliance.index');
        Route::get('compliance/new', ComplianceForm::class)->name('compliance.create');
        Route::get('compliance/{complianceItem}/edit', ComplianceForm::class)->name('compliance.edit');

        Route::get('ip-assets', IpAssetsIndex::class)->name('ip-assets.index');
        Route::get('ip-assets/new', IpAssetForm::class)->name('ip-assets.create');
        Route::get('ip-assets/{ipAsset}/edit', IpAssetForm::class)->name('ip-assets.edit');

        Route::get('serials', SerialsIndex::class)->name('serials.index');
        Route::get('serials/new', SerialForm::class)->name('serials.create');
        Route::get('serials/{serial}/edit', SerialForm::class)->name('serials.edit');

        // --- Business (time, invoicing) ---
        Route::get('time-entries', TimeEntriesIndex::class)->name('time-entries.index');
        Route::get('time-entries/new', TimeEntryForm::class)->name('time-entries.create');
        Route::get('time-entries/{timeEntry}/edit', TimeEntryForm::class)->name('time-entries.edit');

        Route::get('invoices', InvoicesIndex::class)->name('invoices.index');
        Route::get('invoices/new', InvoiceForm::class)->name('invoices.create');
        Route::get('invoices/{invoice}/edit', InvoiceForm::class)->name('invoices.edit');

        // --- Settings (admin / partner only — enforced inside the component) ---
        Route::get('settings', SettingsIndex::class)->name('settings.index');
    });

    require __DIR__.'/auth.php';
});

/*
|--------------------------------------------------------------------------
| Path-based fallback (/t/{tenant}/...)
|--------------------------------------------------------------------------
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
