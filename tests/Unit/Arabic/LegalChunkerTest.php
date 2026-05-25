<?php

declare(strict_types=1);

use App\Services\Arabic\ArabicNormalizer;
use App\Services\Arabic\LegalChunker;

beforeEach(function () {
    $this->c = new LegalChunker(new ArabicNormalizer);
});

it('splits a contract into preamble + articles + signature', function () {
    $contract = <<<'TXT'
ديباجة هذا العقد محرر بين الطرفين الأول والثاني.

المادة 1: التزامات الطرف الأول، ويتعهد بدفع المقابل المتفق عليه.

المادة 2: التزامات الطرف الثاني، ويتعهد بتقديم الخدمة محل التعاقد.

المادة 3: الاختصاص القضائي، وتختص محاكم القاهرة بنظر أي نزاع.

التوقيع: عن الطرف الأول ___________ عن الطرف الثاني ___________
TXT;

    $chunks = $this->c->chunk($contract);

    expect(count($chunks))->toBeGreaterThanOrEqual(4);

    $kinds = array_column($chunks, 'kind');
    expect($kinds)->toContain('article')
        ->and($kinds)->toContain('signature');

    $articles = array_values(array_filter($chunks, fn ($c) => $c['kind'] === 'article'));
    expect($articles)->toHaveCount(3)
        ->and($articles[0]['article_no'])->toBe('1')
        ->and($articles[1]['article_no'])->toBe('2')
        ->and($articles[2]['article_no'])->toBe('3');
});

it('returns one preamble chunk when no structural markers exist', function () {
    $chunks = $this->c->chunk('هذا نص قانوني قصير لا يحتوي على أي عناوين هيكلية.');

    expect($chunks)->toHaveCount(1)
        ->and($chunks[0]['kind'])->toBe('preamble');
});

it('returns an empty array for empty input', function () {
    expect($this->c->chunk(''))->toBe([])
        ->and($this->c->chunk('   '))->toBe([]);
});

it('chunks are already-normalised (ya, alef, no tashkeel)', function () {
    $chunks = $this->c->chunk('المادة 1: أعطى الطرف الأول على نفسه التزاماً');
    $body = $chunks[0]['text'] ?? '';

    // alef variants and ى flattened
    expect($body)->toContain('اعطي')
        ->and($body)->not->toContain('أ')
        ->and($body)->not->toContain('ى');
});
