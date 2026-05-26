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

it('substitutes dotted tokens from a flat data map', function () {
    $body = 'البائع: {{seller.name}} - رقمه القومي: {{seller.national_id}}';
    expect($this->r->replace($body, [
        'seller.name' => 'محمد علي',
        'seller.national_id' => '29801011234567',
    ]))->toBe('البائع: محمد علي - رقمه القومي: 29801011234567');
});

it('substitutes dotted tokens from a nested data map', function () {
    $body = 'المشتري: {{buyer.name}} في {{contract.place}}';
    expect($this->r->replace($body, [
        'buyer' => ['name' => 'أحمد'],
        'contract' => ['place' => 'القاهرة'],
    ]))->toBe('المشتري: أحمد في القاهرة');
});

it('reports an unfilled dotted token if neither flat nor nested form has it', function () {
    $body = 'البائع: {{seller.name}} والمشتري: {{buyer.name}}';
    expect($this->r->unfilled($body, ['seller.name' => 'محمد']))
        ->toBe(['buyer.name']);
});
