<?php

declare(strict_types=1);

namespace App\Core;

final class SimplePdf
{
    private float $pageWidth;
    private float $pageHeight;
    /** @var array<int, string> */
    private array $pages = [];
    /** @var array<int, array<string, mixed>> */
    private array $images = [];
    /** @var array<int, array<string, bool>> */
    private array $pageImageUsage = [];
    private int $currentPage = -1;

    public function __construct(float $pageWidth = 595.28, float $pageHeight = 841.89)
    {
        $this->pageWidth = $pageWidth;
        $this->pageHeight = $pageHeight;
    }

    public function addPage(): void
    {
        $this->pages[] = '';
        $this->pageImageUsage[] = [];
        $this->currentPage = count($this->pages) - 1;
    }

    public function text(float $x, float $yTop, string $text, int $size = 10, string $font = 'F1'): void
    {
        if ($this->currentPage < 0) {
            $this->addPage();
        }
        $txt = self::pdfEscape(self::toAnsi($text));
        $y = $this->toPdfY($yTop);
        $this->pages[$this->currentPage] .= "0 g BT /{$font} {$size} Tf {$x} {$y} Td ({$txt}) Tj ET\n";
    }

    public function line(float $x1, float $y1Top, float $x2, float $y2Top, float $width = 1.0): void
    {
        if ($this->currentPage < 0) {
            $this->addPage();
        }
        $y1 = $this->toPdfY($y1Top);
        $y2 = $this->toPdfY($y2Top);
        $this->pages[$this->currentPage] .= "{$width} w {$x1} {$y1} m {$x2} {$y2} l S\n";
    }

    public function rect(float $x, float $yTop, float $w, float $h, string $style = 'S', float $gray = 1.0): void
    {
        if ($this->currentPage < 0) {
            $this->addPage();
        }
        $y = $this->toPdfY($yTop + $h);
        if ($style === 'F' || $style === 'B') {
            $g = max(0.0, min(1.0, $gray));
            $this->pages[$this->currentPage] .= "{$g} g\n";
        }
        $op = $style === 'F' ? 'f' : ($style === 'B' ? 'B' : 'S');
        $this->pages[$this->currentPage] .= "{$x} {$y} {$w} {$h} re {$op}\n";
    }

    public function circle(float $cx, float $cyTop, float $r, string $style = 'S', float $gray = 1.0): void
    {
        if ($this->currentPage < 0) {
            $this->addPage();
        }
        $k = 0.5522847498;
        $cy = $this->toPdfY($cyTop);
        $op = $style === 'F' ? 'f' : ($style === 'B' ? 'B' : 'S');
        if ($style === 'F' || $style === 'B') {
            $g = max(0.0, min(1.0, $gray));
            $this->pages[$this->currentPage] .= "{$g} g\n";
        }
        $this->pages[$this->currentPage] .= sprintf(
            "%.3f %.3f m %.3f %.3f %.3f %.3f %.3f %.3f c %.3f %.3f %.3f %.3f %.3f %.3f c %.3f %.3f %.3f %.3f %.3f %.3f c %.3f %.3f %.3f %.3f %.3f %.3f c %s\n",
            $cx + $r, $cy,
            $cx + $r, $cy + $k * $r, $cx + $k * $r, $cy + $r, $cx, $cy + $r,
            $cx - $k * $r, $cy + $r, $cx - $r, $cy + $k * $r, $cx - $r, $cy,
            $cx - $r, $cy - $k * $r, $cx - $k * $r, $cy - $r, $cx, $cy - $r,
            $cx + $k * $r, $cy - $r, $cx + $r, $cy - $k * $r, $cx + $r, $cy,
            $op
        );
    }

    public function image(string $path, float $x, float $yTop, float $w, float $h): void
    {
        if ($this->currentPage < 0) {
            $this->addPage();
        }
        $img = $this->loadImage($path);
        if ($img === null) {
            return;
        }
        $key = md5(
            (string) ($img['data'] ?? '')
            . '|'
            . (string) ($img['width'] ?? 0)
            . '|'
            . (string) ($img['height'] ?? 0)
            . '|'
            . (string) ($img['filter'] ?? '')
            . '|'
            . (string) ($img['color_space'] ?? '')
            . '|'
            . (string) ($img['bits'] ?? 8)
            . '|'
            . (string) ($img['smask_data'] ?? '')
        );
        if (!isset($this->images[$key])) {
            $name = 'Im' . (count($this->images) + 1);
            $this->images[$key] = [
                'name' => $name,
                'width' => $img['width'],
                'height' => $img['height'],
                'data' => $img['data'],
                'filter' => $img['filter'] ?? 'DCTDecode',
                'color_space' => $img['color_space'] ?? 'DeviceRGB',
                'bits' => $img['bits'] ?? 8,
                'smask_data' => $img['smask_data'] ?? null,
            ];
        }
        $name = (string) ($this->images[$key]['name'] ?? '');
        if ($name === '') {
            return;
        }
        $this->pageImageUsage[$this->currentPage][$name] = true;
        $y = $this->toPdfY($yTop + $h);
        $this->pages[$this->currentPage] .= "q {$w} 0 0 {$h} {$x} {$y} cm /{$name} Do Q\n";
    }

    public function output(string $title = 'documento.pdf'): string
    {
        if ($this->pages === []) {
            $this->addPage();
        }

        $nPages = count($this->pages);
        $firstPageObj = 3;
        $fontObj1 = $firstPageObj + ($nPages * 2);
        $fontObj2 = $fontObj1 + 1;
        $firstImageObj = $fontObj2 + 1;
        $imageObjMap = [];
        $smaskObjMap = [];
        $nextObj = $firstImageObj;
        foreach ($this->images as $k => $img) {
            $imageObjMap[$k] = $nextObj++;
            if (!empty($img['smask_data'])) {
                $smaskObjMap[$k] = $nextObj++;
            }
        }

        $objects = [];
        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[2] = "<< /Type /Pages /Kids [" . implode(' ', array_map(
            static fn (int $i): string => ((string) ($firstPageObj + ($i * 2))) . " 0 R",
            array_keys($this->pages)
        )) . "] /Count {$nPages} >>";

        foreach ($this->pages as $i => $stream) {
            $pageObj = $firstPageObj + ($i * 2);
            $contentObj = $pageObj + 1;
            $xobj = '';
            $used = $this->pageImageUsage[$i] ?? [];
            if ($used !== []) {
                $parts = [];
                foreach (array_keys($used) as $imgName) {
                    foreach ($this->images as $imgKey => $imgMeta) {
                        if (($imgMeta['name'] ?? '') === $imgName && isset($imageObjMap[$imgKey])) {
                            $parts[] = '/' . $imgName . ' ' . $imageObjMap[$imgKey] . ' 0 R';
                        }
                    }
                }
                if ($parts !== []) {
                    $xobj = ' /XObject << ' . implode(' ', $parts) . ' >>';
                }
            }
            $objects[$pageObj] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$this->pageWidth} {$this->pageHeight}] /Resources << /Font << /F1 {$fontObj1} 0 R /F2 {$fontObj2} 0 R >>{$xobj} >> /Contents {$contentObj} 0 R >>";
            $len = strlen($stream);
            $objects[$contentObj] = "<< /Length {$len} >>\nstream\n{$stream}endstream";
        }

        $objects[$fontObj1] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
        $objects[$fontObj2] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>";
        foreach ($this->images as $imgKey => $img) {
            $objNum = $imageObjMap[$imgKey] ?? 0;
            if ($objNum <= 0) {
                continue;
            }
            $data = (string) ($img['data'] ?? '');
            $w = (int) ($img['width'] ?? 0);
            $h = (int) ($img['height'] ?? 0);
            $filter = (string) ($img['filter'] ?? 'DCTDecode');
            $colorSpace = (string) ($img['color_space'] ?? 'DeviceRGB');
            $bits = (int) ($img['bits'] ?? 8);
            if ($data === '' || $w <= 0 || $h <= 0) {
                continue;
            }
            $smask = '';
            if (isset($smaskObjMap[$imgKey])) {
                $smask = ' /SMask ' . $smaskObjMap[$imgKey] . ' 0 R';
            }
            $objects[$objNum] = "<< /Type /XObject /Subtype /Image /Width {$w} /Height {$h} /ColorSpace /{$colorSpace} /BitsPerComponent {$bits} /Filter /{$filter}{$smask} /Length " . strlen($data) . " >>\nstream\n{$data}\nendstream";
            if (isset($smaskObjMap[$imgKey])) {
                $smaskObj = $smaskObjMap[$imgKey];
                $smaskData = (string) ($img['smask_data'] ?? '');
                if ($smaskObj > 0 && $smaskData !== '') {
                    $objects[$smaskObj] = "<< /Type /XObject /Subtype /Image /Width {$w} /Height {$h} /ColorSpace /DeviceGray /BitsPerComponent 8 /Filter /FlateDecode /Length " . strlen($smaskData) . " >>\nstream\n{$smaskData}\nendstream";
                }
            }
        }

        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "{$num} 0 obj\n{$body}\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $lastObj = max(array_keys($objects));
        $pdf .= "xref\n0 " . ($lastObj + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $lastObj; $i++) {
            $off = $offsets[$i] ?? 0;
            $pdf .= sprintf('%010d 00000 n ', $off) . "\n";
        }
        $safeTitle = self::pdfEscape(self::toAnsi($title));
        $pdf .= "trailer << /Size " . ($lastObj + 1) . " /Root 1 0 R /Info << /Title ({$safeTitle}) >> >>\n";
        $pdf .= "startxref\n{$xrefPos}\n%%EOF";

        return $pdf;
    }

    /** @return array<string,mixed>|null */
    private function loadImage(string $path): ?array
    {
        $path = trim($path);
        if ($path === '' || !is_file($path)) {
            return null;
        }
        $info = @getimagesize($path);
        if (!is_array($info) || !isset($info[2])) {
            return null;
        }
        $type = (int) $info[2];
        if ($type === IMAGETYPE_JPEG) {
            $bin = @file_get_contents($path);
            if ($bin === false || $bin === '') {
                return null;
            }
            return [
                'data' => $bin,
                'width' => (int) ($info[0] ?? 0),
                'height' => (int) ($info[1] ?? 0),
                'filter' => 'DCTDecode',
                'color_space' => 'DeviceRGB',
                'bits' => 8,
            ];
        }

        if ($type === IMAGETYPE_PNG) {
            $parsed = $this->parsePng($path);
            if ($parsed !== null) {
                return $parsed;
            }
        }

        if (!function_exists('imagecreatetruecolor') || !function_exists('imagejpeg')) {
            return null;
        }

        $src = null;
        if ($type === IMAGETYPE_PNG && function_exists('imagecreatefrompng')) {
            $src = @imagecreatefrompng($path);
        } elseif ($type === IMAGETYPE_GIF && function_exists('imagecreatefromgif')) {
            $src = @imagecreatefromgif($path);
        } elseif ($type === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp')) {
            $src = @imagecreatefromwebp($path);
        }
        if (!$src) {
            return null;
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $dst = imagecreatetruecolor($w, $h);
        if (!$dst) {
            imagedestroy($src);
            return null;
        }
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $w, $h, $white);
        imagecopy($dst, $src, 0, 0, 0, 0, $w, $h);

        ob_start();
        imagejpeg($dst, null, 88);
        $jpeg = (string) ob_get_clean();
        imagedestroy($src);
        imagedestroy($dst);
        if ($jpeg === '') {
            return null;
        }

        return ['data' => $jpeg, 'width' => $w, 'height' => $h];
    }

    /** @return array<string,mixed>|null */
    private function parsePng(string $path): ?array
    {
        $bin = @file_get_contents($path);
        if ($bin === false || strlen($bin) < 33) {
            return null;
        }
        $sig = substr($bin, 0, 8);
        if ($sig !== "\x89PNG\r\n\x1a\n") {
            return null;
        }

        $offset = 8;
        $w = 0;
        $h = 0;
        $bit = 0;
        $colorType = 0;
        $idat = '';
        while ($offset + 8 <= strlen($bin)) {
            $lenData = substr($bin, $offset, 4);
            if ($lenData === false || strlen($lenData) !== 4) {
                break;
            }
            $len = unpack('N', $lenData)[1] ?? 0;
            $type = substr($bin, $offset + 4, 4);
            $data = substr($bin, $offset + 8, $len);
            $offset += 12 + $len;
            if ($type === 'IHDR') {
                $w = unpack('N', substr($data, 0, 4))[1] ?? 0;
                $h = unpack('N', substr($data, 4, 4))[1] ?? 0;
                $bit = ord($data[8] ?? "\x00");
                $colorType = ord($data[9] ?? "\x00");
            } elseif ($type === 'IDAT') {
                $idat .= $data;
            } elseif ($type === 'IEND') {
                break;
            }
        }
        if ($w <= 0 || $h <= 0 || $idat === '' || $bit !== 8 || !in_array($colorType, [2, 6], true)) {
            return null;
        }

        $raw = @zlib_decode($idat);
        if (!is_string($raw) || $raw === '') {
            $raw = @gzuncompress($idat);
        }
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $bpp = $colorType === 6 ? 4 : 3;
        $stride = $w * $bpp;
        $pos = 0;
        $prev = str_repeat("\x00", $stride);
        $rgb = '';
        $alpha = '';
        for ($row = 0; $row < $h; $row++) {
            if ($pos + 1 + $stride > strlen($raw)) {
                return null;
            }
            $filter = ord($raw[$pos]);
            $scan = substr($raw, $pos + 1, $stride);
            $pos += 1 + $stride;
            $unfiltered = $this->pngUnfilterRow($filter, $scan, $prev, $bpp);
            if ($unfiltered === null) {
                return null;
            }
            $prev = $unfiltered;

            if ($colorType === 6) {
                $len = strlen($unfiltered);
                for ($i = 0; $i < $len; $i += 4) {
                    $rgb .= $unfiltered[$i] . $unfiltered[$i + 1] . $unfiltered[$i + 2];
                    $alpha .= $unfiltered[$i + 3];
                }
            } else {
                $rgb .= $unfiltered;
            }
        }

        $rgbCompressed = gzcompress($rgb, 6);
        if ($rgbCompressed === false) {
            return null;
        }
        $out = [
            'data' => $rgbCompressed,
            'width' => $w,
            'height' => $h,
            'filter' => 'FlateDecode',
            'color_space' => 'DeviceRGB',
            'bits' => 8,
        ];
        if ($colorType === 6) {
            $alphaCompressed = gzcompress($alpha, 6);
            if ($alphaCompressed !== false) {
                $out['smask_data'] = $alphaCompressed;
            }
        }
        return $out;
    }

    private function pngPaeth(int $a, int $b, int $c): int
    {
        $p = $a + $b - $c;
        $pa = abs($p - $a);
        $pb = abs($p - $b);
        $pc = abs($p - $c);
        if ($pa <= $pb && $pa <= $pc) {
            return $a;
        }
        if ($pb <= $pc) {
            return $b;
        }
        return $c;
    }

    private function pngUnfilterRow(int $filter, string $scan, string $prev, int $bpp): ?string
    {
        $len = strlen($scan);
        $out = '';
        if ($filter === 0) {
            return $scan;
        }
        for ($i = 0; $i < $len; $i++) {
            $x = ord($scan[$i]);
            $left = $i >= $bpp ? ord($out[$i - $bpp]) : 0;
            $up = ord($prev[$i] ?? "\x00");
            $upLeft = $i >= $bpp ? ord($prev[$i - $bpp] ?? "\x00") : 0;
            $val = 0;
            if ($filter === 1) {
                $val = ($x + $left) & 0xFF;
            } elseif ($filter === 2) {
                $val = ($x + $up) & 0xFF;
            } elseif ($filter === 3) {
                $val = ($x + intdiv($left + $up, 2)) & 0xFF;
            } elseif ($filter === 4) {
                $val = ($x + $this->pngPaeth($left, $up, $upLeft)) & 0xFF;
            } else {
                return null;
            }
            $out .= chr($val);
        }
        return $out;
    }

    private static function pdfEscape(string $text): string
    {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\(', '\)', '', ' '], $text);
    }

    private static function toAnsi(string $text): string
    {
        if ($text === '') {
            return '';
        }
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
        if ($converted === false || $converted === '') {
            return preg_replace('/[^\x20-\x7E]/', '', $text) ?? '';
        }
        return $converted;
    }

    private function toPdfY(float $topY): float
    {
        return $this->pageHeight - $topY;
    }
}
