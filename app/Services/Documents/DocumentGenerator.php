<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\Jobs\ConvertDocxToPdf;
use App\Models\ClauseVersion;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\TemplateVersion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;

/**
 * Assembles a DOCX from a template + selected verbatim clauses + filled
 * form data, persists the result as a `document_versions` row, and queues
 * a PDF conversion job.
 *
 * Storage strategy: each tenant's documents go under a prefixed key
 * `tenants/{tenantId}/documents/{documentId}/v{N}.docx`. We use the
 * default disk so dev runs against `storage/app/`; production switches
 * the disk to s3 in `config/filesystems.php`.
 */
final class DocumentGenerator
{
    public function __construct(private readonly TokenReplacer $tokens) {}

    /**
     * @param  array<string, scalar|null>  $filledData
     * @param  Collection<int, ClauseVersion>  $verbatimClauses
     */
    public function generate(
        Document $document,
        TemplateVersion $templateVersion,
        Collection $verbatimClauses,
        array $filledData,
        ?int $userId = null,
    ): DocumentVersion {
        $body = $this->tokens->replace($templateVersion->body, $filledData);

        $clausesText = $verbatimClauses
            ->map(fn (ClauseVersion $cv) => $cv->body)
            ->implode("\n\n");

        $fullText = $clausesText !== ''
            ? $body."\n\n".$clausesText
            : $body;

        $word = new PhpWord;
        $word->setDefaultFontName('Cairo');
        $section = $word->addSection([
            'rtl' => true,
        ]);

        // Body can be plain text or simple HTML; addHtml handles both.
        Html::addHtml($section, nl2br(e($fullText)), false, false);

        $nextVersion = ($document->versions()->max('version_no') ?? 0) + 1;
        $relativePath = sprintf(
            'tenants/%s/documents/%d/v%d.docx',
            $document->tenant_id,
            $document->id,
            $nextVersion,
        );

        $tmp = tempnam(sys_get_temp_dir(), 'lexa-docx-');
        $word->save($tmp, 'Word2007');
        Storage::put($relativePath, file_get_contents($tmp));
        @unlink($tmp);

        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_no' => $nextVersion,
            'template_version_id' => $templateVersion->id,
            'clause_version_ids' => $verbatimClauses->pluck('id')->all(),
            'filled_data' => $filledData,
            'storage_ref' => $relativePath,
            'created_by_user_id' => $userId,
            'locked' => false,
        ]);

        $document->update(['current_version_id' => $version->id]);

        Bus::dispatch(new ConvertDocxToPdf($version->id));

        return $version;
    }

    public function unfilledTokens(TemplateVersion $templateVersion, array $filledData): array
    {
        return $this->tokens->unfilled($templateVersion->body, $filledData);
    }

    public function newDocument(string $title, string $type = 'contract'): Document
    {
        return Document::create([
            'title' => $title,
            'type' => $type,
            'format' => 'docx',
        ]);
    }

    public function tempName(): string
    {
        return 'lexa-'.Str::random(8).'.docx';
    }
}
