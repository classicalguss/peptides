<?php

namespace App\Support;

/**
 * A tiny single-page PDF writer.
 *
 * Deliberately dependency free: we only need flat text and rules for a
 * certificate of analysis, which does not justify pulling in a full PDF
 * toolchain. Supports the standard Helvetica faces, which every reader has
 * built in, so no font embedding is required.
 */
class SimplePdf
{
    protected const WIDTH = 595.28;  // A4 at 72dpi

    protected const HEIGHT = 841.89;

    /** @var array<int, string> content stream operations */
    protected array $ops = [];

    public function text(string $value, float $x, float $y, float $size = 11, bool $bold = false, ?string $rgb = null): static
    {
        $font = $bold ? '/F1' : '/F2';

        $this->ops[] = 'BT';

        if ($rgb) {
            $this->ops[] = $this->colour($rgb).' rg';
        }

        $this->ops[] = "{$font} {$size} Tf";
        $this->ops[] = sprintf('1 0 0 1 %.2F %.2F Tm', $x, self::HEIGHT - $y);
        $this->ops[] = '('.$this->escape($value).') Tj';
        $this->ops[] = 'ET';

        if ($rgb) {
            $this->ops[] = '0 0 0 rg';
        }

        return $this;
    }

    public function rect(float $x, float $y, float $w, float $h, string $rgb = '000000'): static
    {
        $this->ops[] = $this->colour($rgb).' rg';
        $this->ops[] = sprintf('%.2F %.2F %.2F %.2F re f', $x, self::HEIGHT - $y - $h, $w, $h);
        $this->ops[] = '0 0 0 rg';

        return $this;
    }

    public function rule(float $x, float $y, float $w, string $rgb = 'cccccc'): static
    {
        return $this->rect($x, $y, $w, 0.7, $rgb);
    }

    public function render(): string
    {
        $content = implode("\n", $this->ops);

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] '.
                '/Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> /Contents 4 0 R >>',
                self::WIDTH,
                self::HEIGHT
            ),
            '<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $i => $object) {
            $offsets[$i] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n".$object."\nendobj\n";
        }

        $xrefAt = strlen($pdf);
        $count = count($objects) + 1;

        $pdf .= "xref\n0 {$count}\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xrefAt}\n%%EOF";

        return $pdf;
    }

    protected function escape(string $value): string
    {
        // Latin-1 is what WinAnsiEncoding expects; drop anything that will not map.
        $value = mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8');

        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ''], $value);
    }

    protected function colour(string $hex): string
    {
        [$r, $g, $b] = sscanf($hex, '%2x%2x%2x');

        return sprintf('%.3F %.3F %.3F', $r / 255, $g / 255, $b / 255);
    }
}
