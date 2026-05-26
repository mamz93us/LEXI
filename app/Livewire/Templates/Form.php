<?php

declare(strict_types=1);

namespace App\Livewire\Templates;

use App\Models\Template;
use App\Models\TemplateVersion;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?Template $template = null;

    public string $title = '';

    public string $type = 'contract';

    public ?string $description = null;

    public string $body = '';

    public string $variables_json = '[]';

    public bool $is_active = true;

    public function mount(?Template $template = null): void
    {
        if ($template && $template->exists) {
            $this->template = $template->load('currentVersion');
            $this->title = $template->title;
            $this->type = $template->type;
            $this->description = $template->description;
            $this->is_active = (bool) $template->is_active;
            $this->body = $template->currentVersion?->body ?? '';
            $this->variables_json = json_encode(
                $template->currentVersion?->variables ?? [],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
            ) ?: '[]';
        }
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['contract', 'poa', 'memo', 'filing', 'other'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'body' => ['required', 'string'],
            'variables_json' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function save()
    {
        $data = $this->validate();

        $variables = json_decode($data['variables_json'] ?: '[]', true);
        if (! is_array($variables)) {
            $this->addError('variables_json', 'متغيرات القالب يجب أن تكون JSON صالحاً.');

            return;
        }

        if ($this->template) {
            $this->template->update([
                'title' => $data['title'],
                'type' => $data['type'],
                'description' => $data['description'],
                'is_active' => $data['is_active'],
            ]);
            $template = $this->template;
        } else {
            $template = Template::create([
                'title' => $data['title'],
                'type' => $data['type'],
                'description' => $data['description'],
                'is_active' => $data['is_active'],
            ]);
        }

        // Create a new version every save so history is preserved.
        $nextVersion = ($template->versions()->max('version_no') ?? 0) + 1;
        $version = TemplateVersion::create([
            'template_id' => $template->id,
            'version_no' => $nextVersion,
            'body' => $data['body'],
            'variables' => $variables,
            'created_by_user_id' => auth()->id(),
        ]);
        $template->update(['current_version_id' => $version->id]);

        return $this->redirectRoute('templates.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.templates.form');
    }
}
