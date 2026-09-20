<?php

namespace App\Services\Excel;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class XlsxWriter
{
    /**
     * Writes small-to-medium reports without requiring a third-party package.
     * Rows may be generators, so the export service does not need to load every
     * database record into memory at once.
     *
     * @param array<string, array{headers: array<int, string>, rows: iterable}> $sheets
     */
    public function download(string $filename, array $sheets)
    {
        $directory = storage_path('app/private/exports');
        File::ensureDirectoryExists($directory);

        $path = $directory . '/' . Str::uuid() . '.xlsx';
        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('فایل Excel قابل ایجاد نیست.');
        }

        $sheetNames = array_keys($sheets);
        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml(count($sheetNames)));
        $zip->addFromString('_rels/.rels', $this->rootRelationshipsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml($sheetNames));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationshipsXml(count($sheetNames)));
        $zip->addFromString('xl/styles.xml', $this->stylesXml());

        $temporaryWorksheets = [];
        $sheetNumber = 1;
        foreach ($sheets as $sheet) {
            $worksheetPath = $directory . '/' . Str::uuid() . '.xml';
            $this->writeWorksheetFile($worksheetPath, $sheet['headers'], $sheet['rows']);
            $zip->addFile($worksheetPath, 'xl/worksheets/sheet' . $sheetNumber . '.xml');
            $temporaryWorksheets[] = $worksheetPath;
            $sheetNumber++;
        }

        $zip->close();

        foreach ($temporaryWorksheets as $worksheetPath) {
            @unlink($worksheetPath);
        }

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    private function writeWorksheetFile(string $path, array $headers, iterable $rows): void
    {
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new \RuntimeException('فایل موقت Excel قابل ایجاد نیست.');
        }

        fwrite($handle, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>');
        fwrite($handle, '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>');
        $rowNumber = 1;
        fwrite($handle, $this->rowXml($rowNumber++, $headers, true));

        foreach ($rows as $row) {
            fwrite($handle, $this->rowXml($rowNumber++, array_values($row)));
        }

        fwrite($handle, '</sheetData></worksheet>');
        fclose($handle);
    }

    private function rowXml(int $rowNumber, array $values, bool $header = false): string
    {
        $xml = '<row r="' . $rowNumber . '">';

        foreach ($values as $column => $value) {
            $cell = $this->columnName($column + 1) . $rowNumber;
            $text = $this->escape($value);
            $style = $header ? ' s="1"' : '';
            $xml .= '<c r="' . $cell . '" t="inlineStr"' . $style . '><is><t xml:space="preserve">' . $text . '</t></is></c>';
        }

        return $xml . '</row>';
    }

    private function columnName(int $number): string
    {
        $name = '';
        while ($number > 0) {
            $remainder = ($number - 1) % 26;
            $name = chr(65 + $remainder) . $name;
            $number = intdiv($number - 1, 26);
        }

        return $name;
    }

    private function escape(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            $value = $value->format('Y-m-d H:i:s');
        }

        return htmlspecialchars((string) ($value ?? ''), ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function contentTypesXml(int $sheetCount): string
    {
        $overrides = '';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $overrides . '</Types>';
    }

    private function rootRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function workbookXml(array $sheetNames): string
    {
        $sheets = '';
        foreach ($sheetNames as $index => $name) {
            $safeName = mb_substr((string) $name, 0, 31);
            $sheets .= '<sheet name="' . $this->escape($safeName) . '" sheetId="' . ($index + 1) . '" r:id="rId' . ($index + 1) . '"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheets . '</sheets></workbook>';
    }

    private function workbookRelationshipsXml(int $sheetCount): string
    {
        $relationships = '';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $relationships .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $relationships . '</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Arial"/></font><font><b/><sz val="11"/><name val="Arial"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0"/></cellXfs>'
            . '</styleSheet>';
    }
}
