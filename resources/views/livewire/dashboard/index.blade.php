<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto space-y-6">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">{{ __('Dashboard') }}</h2>
            <p class="text-sm text-gray-500 mt-1">{{ tenant('name') ?? config('app.name') }}</p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
            <div class="bg-white shadow-sm rounded-lg p-4">
                <p class="text-xs text-gray-500">قضايا مفتوحة</p>
                <p class="text-2xl font-semibold text-lexa-700">{{ $this->stats['active_cases'] }}</p>
            </div>
            <div class="bg-white shadow-sm rounded-lg p-4">
                <p class="text-xs text-gray-500">جلسات خلال 7 أيام</p>
                <p class="text-2xl font-semibold text-blue-700">{{ $this->stats['hearings_next_7_days'] }}</p>
            </div>
            <div class="bg-white shadow-sm rounded-lg p-4">
                <p class="text-xs text-gray-500">مواعيد طعن قريبة</p>
                <p class="text-2xl font-semibold text-amber-700">{{ $this->stats['open_deadlines'] }}</p>
            </div>
            <div class="bg-white shadow-sm rounded-lg p-4">
                <p class="text-xs text-gray-500">امتثال متأخر</p>
                <p class="text-2xl font-semibold text-red-700">{{ $this->stats['overdue_compliance'] }}</p>
            </div>
            <div class="bg-white shadow-sm rounded-lg p-4">
                <p class="text-xs text-gray-500">فواتير معلقة</p>
                <p class="text-2xl font-semibold text-gray-900">{{ number_format($this->stats['outstanding_invoices_egp'], 0) }} <span class="text-sm text-gray-500">ج.م</span></p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <a href="{{ route('clients.index') }}" wire:navigate
               class="block bg-white shadow-sm rounded-lg p-6 hover:shadow-md transition">
                <h4 class="font-semibold text-lexa-700">{{ __('Clients') }}</h4>
                <p class="text-sm text-gray-500 mt-1">إدارة العملاء وبياناتهم</p>
            </a>
            <a href="{{ route('cases.index') }}" wire:navigate
               class="block bg-white shadow-sm rounded-lg p-6 hover:shadow-md transition">
                <h4 class="font-semibold text-lexa-700">{{ __('Cases') }}</h4>
                <p class="text-sm text-gray-500 mt-1">القضايا والجلسات والأحكام</p>
            </a>
            <a href="{{ route('calendar.index') }}" wire:navigate
               class="block bg-white shadow-sm rounded-lg p-6 hover:shadow-md transition">
                <h4 class="font-semibold text-lexa-700">{{ __('Calendar') }}</h4>
                <p class="text-sm text-gray-500 mt-1">الجلسات والمواعيد القادمة</p>
            </a>
            <a href="{{ route('companies.index') }}" wire:navigate
               class="block bg-white shadow-sm rounded-lg p-6 hover:shadow-md transition">
                <h4 class="font-semibold text-lexa-700">الشركات</h4>
                <p class="text-sm text-gray-500 mt-1">تأسيس الشركات والامتثال</p>
            </a>
            <a href="{{ route('invoices.index') }}" wire:navigate
               class="block bg-white shadow-sm rounded-lg p-6 hover:shadow-md transition">
                <h4 class="font-semibold text-lexa-700">الفواتير</h4>
                <p class="text-sm text-gray-500 mt-1">الفواتير والمدفوعات</p>
            </a>
        </div>
    </div>
</div>
