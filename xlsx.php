<?php
/**
 * xlsx.php — tiny dependency-free Excel writer.
 *
 *   send_workbook('backup.xlsx', [
 *       'Stock'    => ['headers' => ['Name','Qty','Rate'], 'rows' => [['Paracetamol', 10, 12.5], ...], 'money' => [2]],
 *       'Invoices' => [...],
 *   ]);
 *
 * - real .xlsx (zip of XML) when PHP's ZipArchive is available — bold header row, frozen header,
 *   auto-filter, sensible column widths; ints/floats are stored as numbers, 'money' columns as 0.00.
 * - otherwise falls back to an Excel-2003 XML workbook (.xls) that Excel/LibreOffice also open.
 * Text is always written as text, so a cell like "=1+1" can never run as a formula.
 */

// Works even if the PHP "mbstring" extension is missing.
if (!function_exists('mb_substr')) { function mb_substr($s, $a, $l = null) { return $l === null ? substr($s, $a) : substr($s, $a, $l); } }
if (!function_exists('mb_strlen')) { function mb_strlen($s) { return strlen($s); } }

function xl_col(int $i): string {
    $s = ''; $i++;
    while ($i > 0) { $m = ($i - 1) % 26; $s = chr(65 + $m) . $s; $i = intdiv($i - 1, 26); }
    return $s;
}
function xl_esc($v): string {
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string)$v);
    return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function xl_sheet_name(string $n, array &$used): string {
    $n = preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $n);
    $n = trim(mb_substr($n, 0, 31)) ?: 'Sheet';
    $base = $n; $i = 2;
    while (in_array(strtolower($n), $used, true)) { $n = mb_substr($base, 0, 28) . ' ' . $i++; }
    $used[] = strtolower($n);
    return $n;
}
function xl_widths(array $headers, array $rows): array {
    $w = [];
    foreach ($headers as $i => $h) $w[$i] = mb_strlen((string)$h) + 3;
    foreach (array_slice($rows, 0, 300) as $r) {
        foreach ($r as $i => $v) {
            $len = is_float($v) ? 12 : mb_strlen((string)$v) + 2;
            if (!isset($w[$i]) || $len > $w[$i]) $w[$i] = $len;
        }
    }
    foreach ($w as $i => $x) $w[$i] = max(8, min(50, $x));
    return $w;
}

function xl_sheet_xml(array $sheet): string {
    $headers = $sheet['headers']; $rows = $sheet['rows']; $money = $sheet['money'] ?? [];
    $nc = max(1, count($headers)); $nr = count($rows) + 1;
    $x  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $x .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
    $x .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>';
    $x .= '<cols>';
    foreach (xl_widths($headers, $rows) as $i => $wd) $x .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $wd . '" customWidth="1"/>';
    $x .= '</cols><sheetData><row r="1">';
    foreach ($headers as $i => $h) $x .= '<c r="' . xl_col($i) . '1" s="1" t="inlineStr"><is><t>' . xl_esc($h) . '</t></is></c>';
    $x .= '</row>';
    foreach ($rows as $ri => $r) {
        $rn = $ri + 2;
        $x .= '<row r="' . $rn . '">';
        foreach ($r as $ci => $v) {
            $ref = xl_col($ci) . $rn;
            if ($v === null || $v === '') continue;
            if (is_int($v) || is_float($v)) {
                $x .= '<c r="' . $ref . '"' . (in_array($ci, $money, true) ? ' s="2"' : '') . '><v>' . $v . '</v></c>';
            } else {
                $x .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . xl_esc($v) . '</t></is></c>';
            }
        }
        $x .= '</row>';
    }
    $x .= '</sheetData><autoFilter ref="A1:' . xl_col($nc - 1) . $nr . '"/></worksheet>';
    return $x;
}

function build_xlsx_file(array $sheets): string {
    $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new Exception('Cannot create zip');
    $used = []; $names = []; $ct = ''; $rels = ''; $sheetsXml = ''; $n = 0;
    foreach ($sheets as $title => $sheet) {
        $n++;
        $name = xl_sheet_name($title, $used);
        $zip->addFromString("xl/worksheets/sheet$n.xml", xl_sheet_xml($sheet));
        $ct .= '<Override PartName="/xl/worksheets/sheet' . $n . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        $rels .= '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $n . '.xml"/>';
        $sheetsXml .= '<sheet name="' . xl_esc($name) . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
    }
    $rels .= '<Relationship Id="rId' . ($n + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
    $hdr = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $zip->addFromString('[Content_Types].xml', $hdr . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' . $ct . '</Types>');
    $zip->addFromString('_rels/.rels', $hdr . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
    $zip->addFromString('xl/workbook.xml', $hdr . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>' . $sheetsXml . '</sheets></workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels', $hdr . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels . '</Relationships>');
    $zip->addFromString('xl/styles.xml', $hdr . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0F172A"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/><xf numFmtId="2" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>');
    $zip->close();
    return $tmp;
}

function build_spreadsheetml(array $sheets): string {
    $used = [];
    $x = '<?xml version="1.0" encoding="UTF-8"?><Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
    foreach ($sheets as $title => $sheet) {
        $x .= '<Worksheet ss:Name="' . xl_esc(xl_sheet_name($title, $used)) . '"><Table><Row>';
        foreach ($sheet['headers'] as $h) $x .= '<Cell><Data ss:Type="String">' . xl_esc($h) . '</Data></Cell>';
        $x .= '</Row>';
        foreach ($sheet['rows'] as $r) {
            $x .= '<Row>';
            foreach ($r as $v) {
                if ($v === null || $v === '') { $x .= '<Cell/>'; continue; }
                $x .= (is_int($v) || is_float($v))
                    ? '<Cell><Data ss:Type="Number">' . $v . '</Data></Cell>'
                    : '<Cell><Data ss:Type="String">' . xl_esc($v) . '</Data></Cell>';
            }
            $x .= '</Row>';
        }
        $x .= '</Table></Worksheet>';
    }
    return $x . '</Workbook>';
}

function send_workbook(string $baseName, array $sheets): void {
    while (ob_get_level()) ob_end_clean();
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    if (class_exists('ZipArchive')) {
        $tmp = build_xlsx_file($sheets);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $baseName . '.xlsx"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        @unlink($tmp);
    } else {
        $xml = build_spreadsheetml($sheets);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $baseName . '.xls"');
        header('Content-Length: ' . strlen($xml));
        echo $xml;
    }
    exit;
}
