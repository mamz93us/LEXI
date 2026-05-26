<?php

declare(strict_types=1);

namespace App\Livewire\IpAssets;

use App\Models\IpAsset;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public function delete(int $id): void
    {
        IpAsset::query()->findOrFail($id)->delete();
    }

    #[Computed]
    public function assets(): Collection
    {
        return IpAsset::query()->with('company', 'client')->orderByDesc('created_at')->limit(200)->get();
    }

    public function render(): View
    {
        return view('livewire.ip-assets.index');
    }
}
