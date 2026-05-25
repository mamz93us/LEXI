<?php

declare(strict_types=1);

use App\Services\Documents\TokenReplacer;

beforeEach(fn () => $this->r = new TokenReplacer);

it('substitutes plain tokens', function () {
    expect($this->r->replace('عقد بين {{party_a}} و{{party_b}}.', [
        'party_a' => 'محمد',
        'party_b' => 'أحمد',
    ]))->toBe('عقد بين محمد وأحمد.');
});

it('tolerates whitespace inside the braces', function () {
    expect($this->r->replace('{{  name  }}', ['name' => 'X']))->toBe('X');
});

it('leaves unfilled tokens visible so the lawyer notices', function () {
    $body = 'اسم: {{name}}, تاريخ: {{date}}';
    expect($this->r->replace($body, ['name' => 'X']))->toBe('اسم: X, تاريخ: {{date}}');
});

it('reports unfilled tokens by name', function () {
    $body = '{{a}} {{b}} {{a}}';
    expect($this->r->unfilled($body, ['a' => '1']))->toBe(['b']);
});

it('does not evaluate php-like expressions inside braces', function () {
    expect($this->r->replace('{{<?php phpinfo() ?>}}', []))
        ->toContain('<?php')
        ->and($this->r->replace('{{ name + 1 }}', ['name' => 'x']))
        ->toBe('{{ name + 1 }}'); // not a valid identifier → left untouched
});
