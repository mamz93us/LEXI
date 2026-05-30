<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\Services\Ai\AnthropicClient;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use RuntimeException;
use Throwable;

/**
 * Extract plain text from a stored document for RAG ingestion.
 *
 *   - .txt              read directly
 *   - .docx / .doc      PhpWord element walk (no external binary)
 *   - .pdf / image      Claude vision OCR (Arabic-aware, no Tesseract dep)
 *
 * Returns '' when nothing usable could be extracted — the caller's
 * quality gate decides whether to index or skip.
 */
final class DocumentTextExtractor
{
    public function __construct(private readonly AnthropicClient $anthropic) {}

    public function extract(string $storageRef): string
    {
        $disk = Storage::disk(config('filesystems.default'));
        if (! $disk->exists($storageRef)) {
            throw new RuntimeException("Document file not found in storage: {$storageRef}");
        }

        $ext = strtolower(pathinfo($storageRef, PATHINFO_EXTENSION));

        return match ($ext) {
            'txt' => (string) $disk->get($storageRef),
            'docx', 'doc' => $this->fromWord($disk->path($storageRef), $disk, $storageRef),
            'pdf', 'png', 'jpg', 'jpeg', 'webp', 'gif' => $this->fromVision($disk->get($storageRef), $ext),
            default => '',
        };
    }

    /**
     * Walk the PhpWord element tree collecting text from every section.
     * Works for the common contract case (paragraphs + tables of TextRun).
     */
    private function fromWord(string $absolutePath, $disk, string $storageRef): string
    {
        // Some disks (e.g. S3) won't give a local path — pull to a temp file.
        $tmp = null;
        if (! is_file($absolutePath)) {
            $tmp = tempnam(sys_get_temp_dir(), 'lexa-docx-');
            file_put_contents($tmp, $disk->get($storageRef));
            $absolutePath = $tmp;
        }

        try {
            $phpWord = IOFactory::load($absolutePath);
            $buffer = [];
            foreach ($phpWord->getSections() as $section) {
                $this->collectWordText($section->getElements(), $buffer);
            }

            return trim(implode("\n", array_filter($buffer)));
        } catch (Throwable $e) {
            // Corrupt or unsupported .doc — give the vision path a try as a
            // last resort is not possible (binary .doc), so just return ''.
            return '';
        } finally {
            if ($tmp && is_file($tmp)) {
                @unlink($tmp);
            }
        }
    }

    /** @param  array<int, object>  $elements */
    private function collectWordText(array $elements, array &$buffer): void
    {
        foreach ($elements as $el) {
            if (method_exists($el, 'getText')) {
                $text = $el->getText();
                if (is_string($text) && trim($text) !== '') {
                    $buffer[] = $text;
                }
            }
            if (method_exists($el, 'getElements')) {
                $this->collectWordText($el->getElements(), $buffer);
            }
            // Tables expose rows → cells → elements.
            if (method_exists($el, 'getRows')) {
                foreach ($el->getRows() as $row) {
                    foreach ($row->getCells() as $cell) {
                        $this->collectWordText($cell->getElements(), $buffer);
                    }
                }
            }
        }
    }

    /**
     * OCR a PDF/image with Claude's native vision. Returns the transcribed
     * Arabic text only — no commentary.
     */
    private function fromVision(string $bytes, string $ext): string
    {
        $mime = match ($ext) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/jpeg',
        };

        $system = 'أنت أداة استخراج نصوص. أعد النص الكامل للوثيقة المرفقة كما هو حرفياً بالعربية، '.
            'دون أي شرح أو تعليق أو عناوين إضافية. إذا كانت الوثيقة فارغة أو غير مقروءة، أعد سطراً فارغاً.';

        $user = 'استخرج النص الكامل من هذه الوثيقة.';

        try {
            return trim($this->anthropic->sendMessagesWithFiles($system, $user, [
                ['data' => $bytes, 'mime' => $mime],
            ]));
        } catch (Throwable $e) {
            throw new RuntimeException('Vision text extraction failed: '.$e->getMessage(), previous: $e);
        }
    }
}
