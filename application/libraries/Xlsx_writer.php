<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Simple XLSX Writer Library
 * Generates native .xlsx files using PHP ZipArchive
 */
class Xlsx_writer
{
    private $rows = [];
    private $sheetName = 'Sheet1';
    
    /**
     * Add a row of data
     * @param array $data Row data
     * @param string $style Style: 'header', 'center', or null
     */
    public function addRow($data, $style = null)
    {
        $this->rows[] = [
            'data' => $data,
            'style' => $style
        ];
        return $this;
    }
    
    /**
     * Set sheet name
     */
    public function setSheetName($name)
    {
        $this->sheetName = $name;
        return $this;
    }
    
    /**
     * Clear all rows
     */
    public function clear()
    {
        $this->rows = [];
        return $this;
    }
    
    /**
     * Generate column letter from index (0 = A, 1 = B, etc.)
     */
    private function getColumnLetter($index)
    {
        $letter = '';
        while ($index >= 0) {
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = intval($index / 26) - 1;
        }
        return $letter;
    }
    
    /**
     * Escape XML special characters
     */
    private function xmlEscape($str)
    {
        return htmlspecialchars((string)$str, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Generate and download the XLSX file
     */
    public function download($filename)
    {
        // Create temp file
        $cacheDir = APPPATH . 'cache/';
        if (!is_dir($cacheDir) || !is_writable($cacheDir)) {
            $cacheDir = '/tmp/';
        }
        $tempFile = $cacheDir . 'xlsx_' . uniqid() . '.xlsx';
        
        $zip = new ZipArchive();
        $result = $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($result !== true) {
            die('Cannot create XLSX file. Error code: ' . $result);
        }
        
        // Add all required files
        $zip->addFromString('[Content_Types].xml', $this->getContentTypes());
        $zip->addFromString('_rels/.rels', $this->getRels());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->getWorkbookRels());
        $zip->addFromString('xl/workbook.xml', $this->getWorkbook());
        $zip->addFromString('xl/styles.xml', $this->getStyles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->getSheetXml());
        
        $zip->close();
        
        // Verify file was created
        if (!file_exists($tempFile) || filesize($tempFile) == 0) {
            die('Failed to create XLSX file');
        }
        
        // Output file
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tempFile));
        header('Cache-Control: max-age=0');
        header('Pragma: public');
        
        // Clean output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        readfile($tempFile);
        @unlink($tempFile);
        exit;
    }
    
    private function getContentTypes()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>';
    }
    
    private function getRels()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';
    }
    
    private function getWorkbookRels()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';
    }
    
    private function getWorkbook()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<sheets>
<sheet name="' . $this->xmlEscape($this->sheetName) . '" sheetId="1" r:id="rId1"/>
</sheets>
</workbook>';
    }
    
    private function getStyles()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<fonts count="2">
<font><sz val="11"/><name val="Calibri"/></font>
<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
</fonts>
<fills count="3">
<fill><patternFill patternType="none"/></fill>
<fill><patternFill patternType="gray125"/></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FF2C3E50"/><bgColor indexed="64"/></patternFill></fill>
</fills>
<borders count="2">
<border><left/><right/><top/><bottom/><diagonal/></border>
<border>
<left style="thin"><color auto="1"/></left>
<right style="thin"><color auto="1"/></right>
<top style="thin"><color auto="1"/></top>
<bottom style="thin"><color auto="1"/></bottom>
<diagonal/>
</border>
</borders>
<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
<cellXfs count="4">
<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>
<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center"/></xf>
<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center"/></xf>
</cellXfs>
</styleSheet>';
    }
    
    private function getSheetXml()
    {
        $rowsXml = '';
        $rowNum = 1;
        $maxCol = 0;
        
        foreach ($this->rows as $row) {
            $cellsXml = '';
            $colNum = 0;
            
            foreach ($row['data'] as $value) {
                $colLetter = $this->getColumnLetter($colNum);
                $cellRef = $colLetter . $rowNum;
                
                // Determine style: 2 = header (dark bg), 3 = center, 1 = border only
                $styleId = 1;
                if ($row['style'] === 'header') {
                    $styleId = 2;
                } elseif ($row['style'] === 'center') {
                    $styleId = 3;
                }
                
                // Check if numeric
                if (is_numeric($value) && $value !== '') {
                    $cellsXml .= '<c r="' . $cellRef . '" s="' . $styleId . '"><v>' . $value . '</v></c>';
                } else {
                    // Inline string
                    $cellsXml .= '<c r="' . $cellRef . '" s="' . $styleId . '" t="inlineStr"><is><t>' . $this->xmlEscape($value) . '</t></is></c>';
                }
                
                $colNum++;
            }
            
            $maxCol = max($maxCol, $colNum - 1);
            $rowsXml .= '<row r="' . $rowNum . '">' . $cellsXml . '</row>';
            $rowNum++;
        }
        
        // Calculate dimension
        $dimension = 'A1';
        if ($rowNum > 1 && $maxCol >= 0) {
            $dimension = 'A1:' . $this->getColumnLetter($maxCol) . ($rowNum - 1);
        }
        
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<dimension ref="' . $dimension . '"/>
<sheetData>' . $rowsXml . '</sheetData>
</worksheet>';
    }
}
