<?php

namespace Services\Reports;

class SimplePdfWriter
{
    private const PAGE_WIDTH = 595;
    private const PAGE_HEIGHT = 842;
    private const LEFT_MARGIN = 40;
    private const RIGHT_MARGIN = 40;
    private const TOP_MARGIN = 48;
    private const BOTTOM_MARGIN = 48;

    private array $pages = [];
    private int $currentPageIndex = -1;
    private float $cursorY = 0.0;

    public function __construct()
    {
        $this->addPage();
    }

    public function addPage(): void
    {
        $this->pages[] = [];
        $this->currentPageIndex = count($this->pages) - 1;
        $this->cursorY = self::PAGE_HEIGHT - self::TOP_MARGIN;
    }

    public function addTitle(string $text): void
    {
        $this->addText($text, 18, 'bold');
        $this->addSpacer(6);
    }

    public function addSubtitle(string $text): void
    {
        $this->addText($text, 11, 'regular');
        $this->addSpacer(10);
    }

    public function addHeading(string $text): void
    {
        $this->addSpacer(4);
        $this->addText($text, 12, 'bold');
        $this->addRule();
        $this->addSpacer(4);
    }

    public function addParagraph(string $text, int $fontSize = 10): void
    {
        $wrappedLines = $this->wrapText($this->sanitizeText($text), $this->charsPerLine($fontSize));
        foreach ($wrappedLines as $line) {
            $this->addText($line, $fontSize, 'regular');
        }
        $this->addSpacer(4);
    }

    public function addKeyValueList(array $pairs, int $fontSize = 10): void
    {
        foreach ($pairs as $label => $value) {
            $line = sprintf('%s: %s', $this->sanitizeText((string) $label), $this->sanitizeText((string) $value));
            $this->addText($line, $fontSize, 'regular');
        }
        $this->addSpacer(6);
    }

    public function addTable(array $headers, array $rows, array $widths, int $fontSize = 9): void
    {
        $this->addText($this->formatTableRow($headers, $widths), $fontSize, 'bold');
        $this->addRule();

        if (empty($rows)) {
            $this->addText('No records found.', $fontSize, 'regular');
            $this->addSpacer(4);
            return;
        }

        foreach ($rows as $row) {
            $this->addText($this->formatTableRow($row, $widths), $fontSize, 'regular');
        }

        $this->addSpacer(4);
    }

    public function output(): string
    {
        $pageCount = count($this->pages);
        $fontRegularObjectId = 1;
        $fontBoldObjectId = 2;
        $firstPageContentObjectId = 3;
        $pagesObjectId = 2 + ($pageCount * 2) + 1;
        $catalogObjectId = $pagesObjectId + 1;

        $objects = [];
        $objects[$fontRegularObjectId] = $this->buildFontObject('Courier');
        $objects[$fontBoldObjectId] = $this->buildFontObject('Courier-Bold');

        $objectId = $firstPageContentObjectId;
        $pageObjectIds = [];

        foreach ($this->pages as $page) {
            $contentObjectId = $objectId++;
            $pageObjectId = $objectId++;
            $pageObjectIds[] = $pageObjectId;

            $objects[$contentObjectId] = $this->buildContentObject($page);
            $objects[$pageObjectId] = $this->buildPageObject($contentObjectId, $pagesObjectId, $fontRegularObjectId, $fontBoldObjectId);
        }

        $objects[$pagesObjectId] = $this->buildPagesObject($pageObjectIds);
        $objects[$catalogObjectId] = $this->buildCatalogObject($pagesObjectId);

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= sprintf("%010d 65535 f \n", 0);

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root {$catalogObjectId} 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    private function addText(string $text, int $fontSize, string $font = 'regular'): void
    {
        $lineHeight = max(12, $fontSize + 4);
        $this->ensureSpace($lineHeight);

        $this->pages[$this->currentPageIndex][] = [
            'type' => 'text',
            'x' => self::LEFT_MARGIN,
            'y' => $this->cursorY,
            'font' => $font,
            'size' => $fontSize,
            'text' => $this->sanitizeText($text),
        ];

        $this->cursorY -= $lineHeight;
    }

    private function addSpacer(int $points): void
    {
        $this->cursorY -= max(0, $points);
        if ($this->cursorY < self::BOTTOM_MARGIN) {
            $this->addPage();
        }
    }

    private function addRule(): void
    {
        $this->ensureSpace(10);
        $y = $this->cursorY - 3;
        $this->pages[$this->currentPageIndex][] = [
            'type' => 'line',
            'x1' => self::LEFT_MARGIN,
            'y1' => $y,
            'x2' => self::PAGE_WIDTH - self::RIGHT_MARGIN,
            'y2' => $y,
        ];
        $this->cursorY -= 10;
    }

    private function ensureSpace(int $height): void
    {
        if ($this->cursorY - $height < self::BOTTOM_MARGIN) {
            $this->addPage();
        }
    }

    private function formatTableRow(array $cells, array $widths): string
    {
        $formatted = [];
        foreach ($widths as $index => $width) {
            $cell = $this->sanitizeText((string) ($cells[$index] ?? ''));
            $formatted[] = str_pad($this->fitText($cell, (int) $width), (int) $width, ' ');
        }

        return implode(' | ', $formatted);
    }

    private function fitText(string $text, int $width): string
    {
        if ($width <= 0) {
            return '';
        }

        if (strlen($text) <= $width) {
            return $text;
        }

        if ($width <= 3) {
            return substr($text, 0, $width);
        }

        return rtrim(substr($text, 0, $width - 3) . '...');
    }

    private function wrapText(string $text, int $maxChars): array
    {
        $maxChars = max(20, $maxChars);
        $wrapped = wordwrap($text, $maxChars, "\n", true);
        $lines = array_map('trim', explode("\n", $wrapped));

        return array_values(array_filter($lines, static fn (string $line): bool => $line !== ''));
    }

    private function charsPerLine(int $fontSize): int
    {
        $availableWidth = self::PAGE_WIDTH - self::LEFT_MARGIN - self::RIGHT_MARGIN;
        return max(40, (int) floor($availableWidth / max(1, $fontSize * 0.55)));
    }

    private function sanitizeText(string $text): string
    {
        $text = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $text) ?? $text;
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
            if ($converted !== false) {
                return $converted;
            }
        }

        return preg_replace('/[^\x20-\x7E]/', '?', $text) ?? $text;
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function buildFontObject(string $baseFont): string
    {
        return "<< /Type /Font /Subtype /Type1 /BaseFont /{$baseFont} >>";
    }

    private function buildContentObject(array $elements): string
    {
        $commands = [];

        foreach ($elements as $element) {
            if (($element['type'] ?? '') === 'text') {
                $font = ($element['font'] ?? 'regular') === 'bold' ? 'F2' : 'F1';
                $size = (int) ($element['size'] ?? 10);
                $x = number_format((float) ($element['x'] ?? self::LEFT_MARGIN), 2, '.', '');
                $y = number_format((float) ($element['y'] ?? 0), 2, '.', '');
                $text = $this->escapePdfText((string) ($element['text'] ?? ''));
                $commands[] = sprintf('BT /%s %d Tf %s %s Td (%s) Tj ET', $font, $size, $x, $y, $text);
                continue;
            }

            if (($element['type'] ?? '') === 'line') {
                $x1 = number_format((float) ($element['x1'] ?? 0), 2, '.', '');
                $y1 = number_format((float) ($element['y1'] ?? 0), 2, '.', '');
                $x2 = number_format((float) ($element['x2'] ?? 0), 2, '.', '');
                $y2 = number_format((float) ($element['y2'] ?? 0), 2, '.', '');
                $commands[] = sprintf('%s %s m %s %s l S', $x1, $y1, $x2, $y2);
            }
        }

        $content = implode("\n", $commands) . "\n";
        return "<< /Length " . strlen($content) . " >>\nstream\n{$content}endstream";
    }

    private function buildPageObject(int $contentObjectId, int $pagesObjectId, int $regularFontObjectId, int $boldFontObjectId): string
    {
        return sprintf(
            '<< /Type /Page /Parent %d 0 R /MediaBox [0 0 %d %d] /Resources << /Font << /F1 %d 0 R /F2 %d 0 R >> >> /Contents %d 0 R >>',
            $pagesObjectId,
            self::PAGE_WIDTH,
            self::PAGE_HEIGHT,
            $regularFontObjectId,
            $boldFontObjectId,
            $contentObjectId
        );
    }

    private function buildPagesObject(array $pageObjectIds): string
    {
        $kids = array_map(static fn (int $id): string => $id . ' 0 R', $pageObjectIds);

        return sprintf('<< /Type /Pages /Kids [%s] /Count %d >>', implode(' ', $kids), count($pageObjectIds));
    }

    private function buildCatalogObject(int $pagesObjectId): string
    {
        return sprintf('<< /Type /Catalog /Pages %d 0 R >>', $pagesObjectId);
    }
}
