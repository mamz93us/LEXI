<?php

declare(strict_types=1);

namespace App\Livewire\Users;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?User $user = null;

    public string $name = '';

    public ?string $name_ar = null;

    public string $email = '';

    public string $role = 'associate';

    public ?string $phone = null;

    public ?string $national_id = null;

    public ?string $bar_association_no = null;

    public string $nationality = 'مصري';

    public ?string $address = null;

    public string $locale = 'ar';

    public bool $is_active = true;

    /** Only set when creating, or when the manager explicitly clicks "reset". */
    public string $password = '';

    public string $password_confirmation = '';

    public bool $reset_password_mode = false;

    public function mount(?User $user = null): void
    {
        if ($user && $user->exists) {
            $this->authorize('update', $user);
            $this->user = $user;
            $this->name = $user->name;
            $this->name_ar = $user->name_ar;
            $this->email = $user->email;
            $this->role = $user->role?->value ?? 'associate';
            $this->phone = $user->phone;
            $this->national_id = $user->national_id;
            $this->bar_association_no = $user->bar_association_no;
            $this->nationality = $user->nationality ?: 'مصري';
            $this->address = $user->address;
            $this->locale = $user->locale ?: 'ar';
            $this->is_active = (bool) $user->is_active;
        } else {
            $this->authorize('create', User::class);
        }
    }

    protected function rules(): array
    {
        $userId = $this->user?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'role' => ['required', Rule::in(array_map(fn (UserRole $r) => $r->value, UserRole::cases()))],
            'phone' => ['nullable', 'string', 'max:32'],
            'national_id' => ['nullable', 'string', 'max:32'],
            'bar_association_no' => ['nullable', 'string', 'max:64'],
            'nationality' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:2000'],
            'locale' => ['required', Rule::in(['ar', 'en'])],
            'is_active' => ['boolean'],
            // Password: required on create, optional on edit (unless reset_password_mode toggled).
            'password' => $this->user && ! $this->reset_password_mode
                ? ['nullable']
                : ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function save()
    {
        $data = $this->validate();

        $payload = collect($data)->except(['password', 'password_confirmation'])->all();

        if ($this->user) {
            $this->user->update($payload);
            if ($this->reset_password_mode && $this->password !== '') {
                $this->user->update(['password' => Hash::make($this->password)]);
            }
        } else {
            $payload['password'] = Hash::make($this->password);
            User::create($payload);
        }

        session()->flash('saved', $this->user ? 'تم حفظ بيانات المستخدم.' : 'تم إنشاء المستخدم.');

        return $this->redirectRoute('users.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.users.form', [
            'roles' => UserRole::cases(),
        ]);
    }
}
