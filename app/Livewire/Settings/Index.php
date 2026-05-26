<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Enums\UserRole;
use App\Services\Ai\AnthropicClient;
use App\Services\Settings\SettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
class Index extends Component
{
    // --- AI: Anthropic ---
    public string $anthropic_model = 'claude-opus-4-7';

    public bool $anthropic_zero_retention = true;

    public int $anthropic_max_tokens = 4096;

    public bool $anthropic_key_set = false;

    public string $anthropic_new_key = ''; // only written when user types a replacement

    // --- AI: Embeddings ---
    public string $embeddings_driver = 'null';

    public string $embeddings_model = 'embed-multilingual-v3.0';

    public int $embeddings_dimension = 1024;

    public bool $embeddings_key_set = false;

    public string $embeddings_new_key = '';

    public ?string $test_result = null;

    public ?string $test_error = null;

    public function mount(SettingsService $settings): void
    {
        $this->authorizeAdmin();

        $this->anthropic_model = (string) $settings->get('ai', 'anthropic_model', config('lexa.anthropic.model', 'claude-opus-4-7'));
        $this->anthropic_zero_retention = (bool) $settings->get('ai', 'anthropic_zero_retention', config('lexa.anthropic.zero_retention', true));
        $this->anthropic_max_tokens = (int) $settings->get('ai', 'anthropic_max_tokens', config('lexa.anthropic.max_tokens', 4096));
        $this->anthropic_key_set = $settings->has('ai', 'anthropic_api_key') || ! empty(config('lexa.anthropic.api_key'));

        $this->embeddings_driver = (string) $settings->get('embeddings', 'driver', config('lexa.embeddings.driver', 'null'));
        $this->embeddings_model = (string) $settings->get('embeddings', 'model', config('lexa.embeddings.model', 'embed-multilingual-v3.0'));
        $this->embeddings_dimension = (int) $settings->get('embeddings', 'dimension', config('lexa.embeddings.dimension', 1024));
        $this->embeddings_key_set = $settings->has('embeddings', 'api_key') || ! empty(config('lexa.embeddings.api_key'));
    }

    public function save(SettingsService $settings)
    {
        $this->authorizeAdmin();

        $this->validate([
            'anthropic_model' => ['required', 'string', 'max:64'],
            'anthropic_max_tokens' => ['required', 'integer', 'min:256', 'max:200000'],
            'anthropic_new_key' => ['nullable', 'string', 'min:20', 'max:255'],
            'embeddings_driver' => ['required', Rule::in(['null', 'cohere'])],
            'embeddings_model' => ['required', 'string', 'max:64'],
            'embeddings_dimension' => ['required', 'integer', 'min:64', 'max:4096'],
            'embeddings_new_key' => ['nullable', 'string', 'min:20', 'max:255'],
        ]);

        $settings->set('ai', 'anthropic_model', $this->anthropic_model);
        $settings->set('ai', 'anthropic_zero_retention', $this->anthropic_zero_retention ? '1' : '0');
        $settings->set('ai', 'anthropic_max_tokens', (string) $this->anthropic_max_tokens);
        if ($this->anthropic_new_key !== '') {
            $settings->set('ai', 'anthropic_api_key', $this->anthropic_new_key, isSecret: true);
            $this->anthropic_key_set = true;
            $this->anthropic_new_key = '';
        }

        $settings->set('embeddings', 'driver', $this->embeddings_driver);
        $settings->set('embeddings', 'model', $this->embeddings_model);
        $settings->set('embeddings', 'dimension', (string) $this->embeddings_dimension);
        if ($this->embeddings_new_key !== '') {
            $settings->set('embeddings', 'api_key', $this->embeddings_new_key, isSecret: true);
            $this->embeddings_key_set = true;
            $this->embeddings_new_key = '';
        }

        session()->flash('saved', 'الإعدادات محفوظة.');
    }

    public function testAnthropic(AnthropicClient $client): void
    {
        $this->authorizeAdmin();

        $this->test_result = null;
        $this->test_error = null;

        try {
            $out = $client->sendMessages(
                'You are a Claude API health check responder. Respond with exactly: PONG',
                [['role' => 'user', 'content' => 'ping']],
            );
            $this->test_result = mb_strlen($out) > 0
                ? '✅ متصل. ردّ النموذج: '.mb_substr($out, 0, 200)
                : '⚠️ تم الاتصال لكن لم يعد أي محتوى.';
        } catch (Throwable $e) {
            $this->test_error = $e->getMessage();
        }
    }

    public function clearAnthropicKey(SettingsService $settings): void
    {
        $this->authorizeAdmin();
        $settings->forget('ai', 'anthropic_api_key');
        $this->anthropic_key_set = false;
        session()->flash('saved', 'تم حذف مفتاح Anthropic.');
    }

    public function clearEmbeddingsKey(SettingsService $settings): void
    {
        $this->authorizeAdmin();
        $settings->forget('embeddings', 'api_key');
        $this->embeddings_key_set = false;
        session()->flash('saved', 'تم حذف مفتاح خدمة التضمين.');
    }

    private function authorizeAdmin(): void
    {
        $user = auth()->user();
        abort_unless(
            $user && in_array($user->role, [UserRole::Partner, UserRole::Admin], true),
            403,
            'الإعدادات متاحة للشركاء والمديرين فقط.',
        );
    }

    public function render(): View
    {
        return view('livewire.settings.index');
    }
}
