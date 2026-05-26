<?php

declare(strict_types=1);

namespace App\Livewire\AiDrafter;

use App\Enums\UserRole;
use App\Models\AiGeneration;
use App\Services\Documents\ReviewWorkflow;
use App\Services\Rag\RagGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
class Detail extends Component
{
    public AiGeneration $generation;

    public string $refinement_instruction = '';

    public string $manual_edit = '';

    public bool $show_manual_editor = false;

    public ?string $error = null;

    public ?string $info = null;

    public function mount(AiGeneration $generation): void
    {
        $this->generation = $generation;
        $this->manual_edit = $generation->output ?? '';
    }

    #[Computed]
    public function chain(): Collection
    {
        return $this->generation->chain()->loadMissing('reviewer');
    }

    public function refineWithAi(RagGenerator $generator): void
    {
        $this->error = null;
        $this->info = null;

        $instruction = trim($this->refinement_instruction);
        if ($instruction === '') {
            $this->error = 'اكتب تعليمات التعديل قبل الإرسال للذكاء الاصطناعي.';

            return;
        }

        try {
            $new = $generator->refine($this->generation, $instruction);
            $this->refinement_instruction = '';
            $this->redirectRoute('ai-drafter.show', $new, navigate: true);
        } catch (Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    public function saveManualEdit(RagGenerator $generator): void
    {
        $this->error = null;
        $newOutput = trim($this->manual_edit);
        if ($newOutput === '') {
            $this->error = 'النص الجديد فارغ.';

            return;
        }
        if ($newOutput === trim($this->generation->output ?? '')) {
            $this->info = 'لا تغييرات لحفظها.';

            return;
        }

        $new = $generator->recordManualEdit(
            previous: $this->generation,
            newOutput: $newOutput,
            note: 'تعديل يدوي من المحامي',
        );

        $this->redirectRoute('ai-drafter.show', $new, navigate: true);
    }

    public function approve(ReviewWorkflow $workflow): void
    {
        $user = auth()->user();
        if (! $user || ! in_array($user->role, [UserRole::Partner, UserRole::Admin], true)) {
            $this->error = 'اعتماد المسودات متاح للشركاء والمديرين فقط.';

            return;
        }

        try {
            $workflow->approve($this->generation, $user);
            $this->generation->refresh();
            $this->info = 'تم اعتماد هذه المسودة.';
        } catch (Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    public function reject(ReviewWorkflow $workflow): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        try {
            $workflow->reject($this->generation, $user);
            $this->generation->refresh();
            $this->info = 'تم رفض هذه المسودة.';
        } catch (Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    public function render(): View
    {
        return view('livewire.ai-drafter.detail');
    }
}
