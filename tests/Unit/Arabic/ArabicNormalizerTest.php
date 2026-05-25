<?php

declare(strict_types=1);

use App\Services\Arabic\ArabicNormalizer;

beforeEach(function () {
    $this->n = new ArabicNormalizer;
});

it('normalises every alef variant to bare alef', function () {
    expect($this->n->normalize('أحمد'))->toBe('احمد')
        ->and($this->n->normalize('إسماعيل'))->toBe('اسماعيل')
        ->and($this->n->normalize('آدم'))->toBe('ادم')
        ->and($this->n->normalize('ٱلله'))->toBe('الله');
});

it('converts alef-maqsura to ya', function () {
    expect($this->n->normalize('على'))->toBe('علي');
});

it('strips tashkeel and tatweel', function () {
    $with = 'العَرَبِيَّةُ';       // with diacritics
    $tat = 'العــــربية';          // with tatweel
    expect($this->n->normalize($with))->toBe('العربية')
        ->and($this->n->normalize($tat))->toBe('العربية');
});

it('preserves ta-marbuta (semantic, not converted to ه)', function () {
    expect($this->n->normalize('شركة محدودة'))->toBe('شركة محدودة');
});

it('collapses all whitespace to single spaces and trims', function () {
    // الأولى normalises to الاولي (أ→ا, ى→ي)
    expect($this->n->normalize("  المادة\t\u{00A0}الأولى   "))->toBe('المادة الاولي');
});

it('is idempotent — running twice yields the same result', function () {
    $sample = 'أحــمَدُ بنُ علــى';
    $once = $this->n->normalize($sample);
    expect($this->n->normalize($once))->toBe($once);
});

it('returns empty string when given only whitespace or tashkeel', function () {
    expect($this->n->normalize("\u{064E}\u{064F}\u{0650}   "))->toBe('');
});
