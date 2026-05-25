<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\DocumentVersion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Convert the stored DOCX of a DocumentVersion to PDF via LibreOffice
 * headless. The production setup runs a `soffice` container (see
 * docker-compose.yml Phase 2 placeholder); locally we shell out to
 * `soffice` if it exists on PATH, otherwise log and skip.
 */
class ConvertDocxToPdf implements ShouldQueue
{
    use FoundationQueueable;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly int $documentVersionId) {}

    public function handle(): void
    {
        $version = DocumentVersion::query()->withoutTenantScope()->find($this->documentVersionId);
        if (! $version || ! $version->storage_ref) {
            return;
        }

        $sofficeBin = $this->resolveSofficeBinary();
        if (! $sofficeBin) {
            Log::info('lexa.pdf_conversion_skipped', [
                'reason' => 'soffice binary not found',
                'version_id' => $version->id,
            ]);

            return;
        }

        $docxAbs = Storage::path($version->storage_ref);
        $outDir = dirname($docxAbs);

        $cmd = sprintf(
            '%s --headless --convert-to pdf --outdir %s %s',
            escapeshellarg($sofficeBin),
            escapeshellarg($outDir),
            escapeshellarg($docxAbs),
        );

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0) {
            Log::warning('lexa.pdf_conversion_failed', [
                'version_id' => $version->id,
                'exit' => $exitCode,
                'output' => $output,
            ]);

            return;
        }

        $pdfRelative = preg_replace('/\.docx$/', '.pdf', $version->storage_ref);
        $version->update(['pdf_storage_ref' => $pdfRelative]);
    }

    private function resolveSofficeBinary(): ?string
    {
        $candidates = [
            'soffice',
            '/usr/bin/soffice',
            '/usr/local/bin/soffice',
            'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
        ];

        foreach ($candidates as $bin) {
            if (str_contains($bin, '/') || str_contains($bin, '\\')) {
                if (file_exists($bin)) {
                    return $bin;
                }

                continue;
            }

            // bare command — let the shell resolve it
            return $bin;
        }

        return null;
    }
}
