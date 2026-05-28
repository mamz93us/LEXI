<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-2xl font-semibold text-gray-900 mb-1">
            {{ $user ? 'تعديل مستخدم — '.($user->name_ar ?: $user->name) : 'مستخدم جديد' }}
        </h2>
        <p class="text-sm text-gray-500 mb-6">
            بيانات المستخدم تُستخدم للدخول إلى النظام، ولتمثيله كطرف في التوكيلات والعقود إذا كان محامياً.
        </p>

        <form wire:submit="save" class="space-y-5">

            {{-- Identity --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">الاسم بالإنجليزية</label>
                    <input wire:model="name" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">الاسم بالعربية <span class="text-xs text-gray-500">(يُستخدم في العقود)</span></label>
                    <input wire:model="name_ar" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                    @error('name_ar') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">البريد الإلكتروني (للدخول)</label>
                    <input wire:model="email" type="email" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">الدور</label>
                    <select wire:model="role" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        @foreach ($roles as $r)
                            @if ($r->value !== 'client')
                                <option value="{{ $r->value }}">{{ $r->label() }}</option>
                            @endif
                        @endforeach
                    </select>
                    @error('role') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Contact + lawyer-specific identity --}}
            <div class="border-t pt-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">بيانات قانونية وتواصل</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">الهاتف</label>
                        <input wire:model="phone" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">الرقم القومي</label>
                        <input wire:model="national_id" type="text" placeholder="14 رقم" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">رقم نقابة المحامين</label>
                        <input wire:model="bar_association_no" type="text" placeholder="رقم القيد بالنقابة" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">الجنسية</label>
                        <input wire:model="nationality" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                    </div>
                </div>
                <div class="mt-3">
                    <label class="block text-sm font-medium text-gray-700">العنوان</label>
                    <textarea wire:model="address" rows="2" class="mt-1 w-full rounded-md border-gray-300 shadow-sm"></textarea>
                </div>
            </div>

            {{-- Preferences + status --}}
            <div class="border-t pt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">اللغة المفضلة</label>
                    <select wire:model="locale" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        <option value="ar">العربية</option>
                        <option value="en">English</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input wire:model="is_active" type="checkbox" class="rounded border-gray-300" />
                        مستخدم نشط (يستطيع الدخول)
                    </label>
                </div>
            </div>

            {{-- Password --}}
            <div class="border-t pt-4">
                @if ($user)
                    <div class="flex items-center gap-3 mb-3">
                        <h3 class="text-sm font-semibold text-gray-900">كلمة المرور</h3>
                        @if (! $reset_password_mode)
                            <button type="button" wire:click="$set('reset_password_mode', true)"
                                    class="text-xs text-lexa-700 hover:text-lexa-900">إعادة تعيين كلمة المرور</button>
                        @else
                            <button type="button" wire:click="$set('reset_password_mode', false)"
                                    class="text-xs text-gray-500 hover:text-gray-700">إلغاء</button>
                        @endif
                    </div>
                @else
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">كلمة المرور</h3>
                @endif

                @if (! $user || $reset_password_mode)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">كلمة المرور</label>
                            <input wire:model="password" type="password" autocomplete="new-password"
                                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">تأكيد كلمة المرور</label>
                            <input wire:model="password_confirmation" type="password" autocomplete="new-password"
                                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">٨ أحرف على الأقل.</p>
                @else
                    <p class="text-xs text-gray-500">كلمة مرور المستخدم محفوظة مشفّرة. اضغط «إعادة تعيين» لتغييرها.</p>
                @endif
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t">
                <a href="{{ route('users.index') }}" wire:navigate
                   class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900">{{ __('Cancel') }}</a>
                <button type="submit"
                        class="px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md">
                    {{ __('Save') }}
                </button>
            </div>
        </form>
    </div>
</div>
