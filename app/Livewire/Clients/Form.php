<?php

declare(strict_types=1);

namespace App\Livewire\Clients;

use App\Models\Client;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?Client $client = null;

    public string $type = 'individual';

    public string $name = '';

    public ?string $name_ar = null;

    public ?string $phone = null;

    public ?string $whatsapp_phone = null;

    public ?string $email = null;

    public ?string $national_id = null;

    public ?string $commercial_register_no = null;

    public ?string $address = null;

    public ?string $notes = null;

    public function mount(?Client $client = null): void
    {
        if ($client && $client->exists) {
            $this->authorize('update', $client);

            $this->client = $client;
            $this->type = $client->type;
            $this->name = $client->name;
            $this->name_ar = $client->name_ar;
            $this->phone = $client->phone;
            $this->whatsapp_phone = $client->whatsapp_phone;
            $this->email = $client->email;
            $this->national_id = $client->national_id;
            $this->commercial_register_no = $client->commercial_register_no;
            $this->address = $client->address;
            $this->notes = $client->notes;
        } else {
            $this->authorize('create', Client::class);
        }
    }

    protected function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['individual', 'company', 'vip'])],
            'name' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'whatsapp_phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'national_id' => ['nullable', 'string', 'max:32'],
            'commercial_register_no' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function save()
    {
        $data = $this->validate();

        if ($this->client) {
            $this->client->update($data);
        } else {
            Client::create($data);
        }

        return $this->redirectRoute('clients.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.clients.form');
    }
}
