<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\Enums\UserRole;
use App\Models\AiGeneration;
use App\Models\DocumentVersion;
use App\Models\User;
use RuntimeException;

/**
 * Review state machine for AI-generated drafts and lawyer-edited
 * document versions.
 *
 * Status flow:
 *   draft → reviewed → approved → locked
 *                    ↘ rejected
 *
 * Only partners can move a doc to `approved`. `locked` is set only when
 * an approved version is materialised as a printed/sent artifact.
 */
final class ReviewWorkflow
{
    public function markReviewed(AiGeneration $generation, User $reviewer): void
    {
        $this->assertTransition($generation->status, 'reviewed');
        $generation->update([
            'status' => 'reviewed',
            'reviewed_by_user_id' => $reviewer->id,
            'reviewed_at' => now(),
        ]);
    }

    public function approve(AiGeneration $generation, User $approver): void
    {
        if ($approver->role !== UserRole::Partner && $approver->role !== UserRole::Admin) {
            throw new RuntimeException('Only a partner or admin can approve a draft.');
        }
        $this->assertTransition($generation->status, 'approved');
        $generation->update([
            'status' => 'approved',
            'reviewed_by_user_id' => $approver->id,
            'reviewed_at' => now(),
        ]);
    }

    public function reject(AiGeneration $generation, User $reviewer, ?string $reason = null): void
    {
        $generation->update([
            'status' => 'rejected',
            'reviewed_by_user_id' => $reviewer->id,
            'reviewed_at' => now(),
            'output' => $reason ? $generation->output."\n\n--- rejected: ".$reason : $generation->output,
        ]);
    }

    public function lockVersion(DocumentVersion $version): void
    {
        $version->update(['locked' => true]);
    }

    private function assertTransition(string $current, string $next): void
    {
        $allowed = [
            'draft' => ['reviewed', 'approved', 'rejected'],
            'reviewed' => ['approved', 'rejected'],
            'approved' => ['rejected'],
        ];

        if (! in_array($next, $allowed[$current] ?? [], true)) {
            throw new RuntimeException("Invalid review transition: {$current} → {$next}");
        }
    }
}
