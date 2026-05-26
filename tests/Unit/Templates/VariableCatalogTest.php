<?php

declare(strict_types=1);

use App\Services\Templates\VariableCatalog;

it('expands party fields into dotted tokens', function () {
    $tokens = VariableCatalog::partyTokens('seller');
    $names = array_map(fn ($t) => $t['token'], $tokens);

    expect($names)
        ->toContain('seller.name')
        ->toContain('seller.national_id')
        ->toContain('seller.nationality')
        ->toContain('seller.religion')
        ->toContain('seller.profession')
        ->toContain('seller.address');
});

it('detects party namespaces actually referenced in a template body', function () {
    $body = '
        أبرم هذا العقد بين السيد {{seller.name}}،
        والسيد {{buyer.name}} والمشتري {{buyer.national_id}}،
        ولا توجد إشارة للموكِّل أو الوكيل في هذا النموذج.
    ';

    $detected = VariableCatalog::detectPartiesInTemplate($body);

    expect($detected)
        ->toContain('seller')
        ->toContain('buyer')
        ->not->toContain('principal')
        ->not->toContain('agent');
});

it('detects contract metadata tokens referenced in a template body', function () {
    $body = 'حُرر هذا العقد في {{contract.place}} بتاريخ {{contract.date}}، وتختص بنظره {{court.name}}.';

    $detected = VariableCatalog::detectContractMetaInTemplate($body);

    expect($detected)
        ->toContain('contract.place')
        ->toContain('contract.date')
        ->toContain('court.name');
});

it('groups tokens for the editor sidebar with both parties and contract meta', function () {
    $groups = VariableCatalog::groupTokens();
    $headings = array_map(fn ($g) => $g['heading'], $groups);

    expect($headings)
        ->toContain('البائع')
        ->toContain('المشتري')
        ->toContain('بيانات العقد');
});

it('returns the arabic label for a known party namespace', function () {
    expect(VariableCatalog::partyLabel('seller'))->toBe('البائع');
    expect(VariableCatalog::partyLabel('buyer'))->toBe('المشتري');
});

it('includes a pre-built snippet for every token (avoids Blade {{ }} mis-parsing)', function () {
    $partyTokens = VariableCatalog::partyTokens('buyer');
    $first = $partyTokens[0];

    expect($first)
        ->toHaveKey('snippet')
        ->and($first['snippet'])
        ->toStartWith('{{')
        ->toEndWith('}}')
        ->toContain('buyer.');

    // Groups must also carry snippets on every token entry.
    foreach (VariableCatalog::groupTokens() as $group) {
        foreach ($group['tokens'] as $tk) {
            expect($tk['snippet'] ?? null)
                ->not->toBeNull("missing snippet for {$tk['token']}")
                ->toBe('{{'.$tk['token'].'}}');
        }
    }
});
