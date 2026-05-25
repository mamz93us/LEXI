<?php

declare(strict_types=1);

namespace App\Services\Litigation;

use App\Models\Judgment;
use App\Models\LegalCase;
use Carbon\CarbonImmutable;

/**
 * Compute the legal challenge deadline (appeal / cassation window) for a
 * given judgment based on the judgment date, the case's current degree,
 * and the case type — pulling the day-counts from `config('lexa_deadlines')`.
 *
 * The exact day-counts are placeholders flagged for lawyer review; see
 * `config/lexa_deadlines.php`.
 */
final class DeadlineCalculator
{
    /**
     * @return array{date: CarbonImmutable, type: string}|null null when no
     *                                                         challenge window applies (e.g. judgment in a cassation case).
     */
    public function compute(Judgment $judgment): ?array
    {
        /** @var LegalCase $case */
        $case = $judgment->case;
        if (! $case) {
            return null;
        }

        $caseTypeCode = $case->caseType?->code;
        if (! $caseTypeCode) {
            return null;
        }

        $windowKey = $this->windowKeyForDegree($case->degree);
        if (! $windowKey) {
            return null; // cassation = no further challenge in ordinary judiciary.
        }

        $days = config("lexa_deadlines.appeal_windows_days.{$windowKey}.{$caseTypeCode}");
        if (! is_int($days)) {
            return null;
        }

        return [
            'date' => CarbonImmutable::parse($judgment->judgment_date)->addDays($days),
            'type' => $windowKey,
        ];
    }

    private function windowKeyForDegree(?string $degree): ?string
    {
        return match ($degree) {
            'partial', 'first_instance' => 'first_instance_to_appeal',
            'appeal' => 'appeal_to_cassation',
            default => null,
        };
    }
}
