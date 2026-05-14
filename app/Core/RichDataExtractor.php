<?php

declare(strict_types=1);

namespace App\Core;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use ZipArchive;

/**
 * RichDataExtractor — pulls "image in cell" photos out of a .xlsx workbook.
 *
 * PHPSpreadsheet only sees images anchored to drawings (the `xdr:twoCellAnchor`
 * format). Modern Excel ships with "Insert > Picture > Place in Cell", which
 * stores images via the Rich Value metadata feature instead. To map those back
 * to their cells we have to walk three different XML manifests:
 *
 *   1. `xl/worksheets/sheetN.xml`         → cells with `vm` attribute
 *   2. `xl/metadata.xml`                  → vm index → rich value index
 *   3. `xl/richData/richValueRel.xml`     → rich value index → rId
 *   4. `xl/richData/_rels/richValueRel.xml.rels` → rId → media/image file
 *
 * This class loads all three maps once, then offers a fast `imageForCell()`
 * lookup the importer can call per row/column.
 */
final class RichDataExtractor
{
    private string $xlsxPath;

    /** @var array<int,int> Map: vm index (1-based) → richValue index (0-based). */
    private array $vmToRv = [];

    /** @var array<int,string> Map: richValue index (0-based) → rId. */
    private array $rvToRid = [];

    /** @var array<string,string> Map: rId → media/imageN.png. */
    private array $ridToFile = [];

    /** @var array<string,int> Map: cell coordinate ("D3") → vm index. */
    private array $cellToVm = [];

    private ?ZipArchive $zip = null;

    public function __construct(string $xlsxPath)
    {
        $this->xlsxPath = $xlsxPath;
    }

    public function load(string $sheetXmlPath = 'xl/worksheets/sheet1.xml'): void
    {
        $this->zip = new ZipArchive();
        if ($this->zip->open($this->xlsxPath) !== true) {
            throw new \RuntimeException("Cannot open xlsx as zip: {$this->xlsxPath}");
        }

        $this->parseMetadata($this->zip->getFromName('xl/metadata.xml'));
        $this->parseRichValueRel($this->zip->getFromName('xl/richData/richValueRel.xml'));
        $this->parseRichValueRelRels($this->zip->getFromName('xl/richData/_rels/richValueRel.xml.rels'));
        $this->parseSheetCells($this->zip->getFromName($sheetXmlPath));
    }

    public function close(): void
    {
        if ($this->zip instanceof ZipArchive) {
            $this->zip->close();
            $this->zip = null;
        }
    }

    public function totalImageCells(): int
    {
        return count($this->cellToVm);
    }

    /**
     * Returns the media path inside the xlsx (e.g. "xl/media/image117.png")
     * for the given cell coordinate, or null if the cell has no image.
     */
    public function mediaPathForCell(int $col, int $row): ?string
    {
        $coord = Coordinate::stringFromColumnIndex($col) . $row;
        $vm    = $this->cellToVm[$coord] ?? null;
        if ($vm === null) {
            return null;
        }
        $rv = $this->vmToRv[$vm] ?? null;
        if ($rv === null) {
            return null;
        }
        $rid = $this->rvToRid[$rv] ?? null;
        if ($rid === null) {
            return null;
        }
        return $this->ridToFile[$rid] ?? null;
    }

    /**
     * Returns the binary contents of the image at the given cell, or null.
     */
    public function imageBytesForCell(int $col, int $row): ?string
    {
        $mediaPath = $this->mediaPathForCell($col, $row);
        if ($mediaPath === null || $this->zip === null) {
            return null;
        }
        $bytes = $this->zip->getFromName($mediaPath);
        return $bytes === false ? null : $bytes;
    }

    /**
     * @return array<string,string> coord → mediaPath
     */
    public function allCellMappings(): array
    {
        $out = [];
        foreach ($this->cellToVm as $coord => $vm) {
            $rv  = $this->vmToRv[$vm]  ?? null;
            $rid = $rv  === null ? null : ($this->rvToRid[$rv]    ?? null);
            $f   = $rid === null ? null : ($this->ridToFile[$rid] ?? null);
            if ($f !== null) {
                $out[$coord] = $f;
            }
        }
        return $out;
    }

    private function parseMetadata(string|false $xml): void
    {
        if ($xml === false || $xml === '') {
            return;
        }
        // Order of <bk> elements inside <futureMetadata name="XLRICHVALUE"> is the
        // 1-based vm index. Each <bk> contains <xlrd:rvb i="N"/> giving the rv index.
        if (!preg_match('~<futureMetadata\s+name="XLRICHVALUE"[^>]*>(.*?)</futureMetadata>~s', $xml, $m)) {
            return;
        }
        if (!preg_match_all('~<bk>.*?<xlrd:rvb\s+i="(\d+)"\s*/>.*?</bk>~s', $m[1], $matches)) {
            return;
        }
        foreach ($matches[1] as $position => $rvIndex) {
            $this->vmToRv[$position + 1] = (int) $rvIndex;
        }
    }

    private function parseRichValueRel(string|false $xml): void
    {
        if ($xml === false || $xml === '') {
            return;
        }
        if (!preg_match_all('~<rel\s+r:id="([^"]+)"\s*/>~', $xml, $m)) {
            return;
        }
        foreach ($m[1] as $position => $rid) {
            $this->rvToRid[$position] = $rid;
        }
    }

    private function parseRichValueRelRels(string|false $xml): void
    {
        if ($xml === false || $xml === '') {
            return;
        }
        if (!preg_match_all('~<Relationship\s+Id="([^"]+)"[^>]*Target="([^"]+)"~', $xml, $m, PREG_SET_ORDER)) {
            return;
        }
        foreach ($m as $row) {
            $rid    = $row[1];
            $target = $row[2];
            // "../media/image117.png" → "xl/media/image117.png"
            if (str_starts_with($target, '../')) {
                $target = 'xl/' . substr($target, 3);
            }
            $this->ridToFile[$rid] = $target;
        }
    }

    private function parseSheetCells(string|false $xml): void
    {
        if ($xml === false || $xml === '') {
            return;
        }
        if (!preg_match_all('~<c\s+r="([A-Z]+\d+)"[^>]*\svm="(\d+)"~', $xml, $m, PREG_SET_ORDER)) {
            return;
        }
        foreach ($m as $row) {
            $this->cellToVm[$row[1]] = (int) $row[2];
        }
    }
}
