<?php

require_once 'inc/excel/PHPExcel.php';

function readExcel($fname, $jsOutput) {
    set_time_limit(120);
    
    $objPHPExcel = PHPExcel_IOFactory::load($fname);
    $sheet = $objPHPExcel->getActiveSheet();
    
    // search for a row that starts with "Centro"
    $startRow = 1;
    while ($sheet->getCell('A' . $startRow)->getValue() !== 'Centro') {
        $startRow ++;
    }

    // search for the last row = the first one after startRow with empty first cell
    $endRow = $startRow;
    while (strlen($sheet->getCell('A' . $endRow)->getValue()) > 0) {
        $endRow ++;
    }
    
    // search for the last col == the first one in startRow with an empty header
    $endCol = 0;
    while (strlen($sheet->getCell(chr(ord('A') + $endCol) . $startRow)->getValue()) > 0) {
        $endCol ++;
    }
    error_log('read excel ' . $fname . ': data at rows '
        . $startRow . ' to ' . $endRow . ', ' . $endCol . ' cols');

    // build a 2D array with all data-values;
    // also store all person-names in a map
    $d = array();
    $people = array();
    for ($i = $startRow+1; $i < $endRow; $i++) {
        $row = array();
        $isoValue = "";
        for ($j = 0; $j < $endCol; $j++) {
            $value = $sheet->getCell(chr(ord('A') + $j) . $i)->getValue();
            $isoValue = mb_convert_encoding($value, "ISO-8859-1");
            $row[] = $isoValue;
        }
        $d[] = $row;
        if ($isoValue !== "") {
            $people[$isoValue] = true;
        }
    }
    
    $f = fopen($jsOutput, 'w');    
    fwrite($f, 
        'var data=' . json_encode($d, JSON_PRETTY_PRINT) . ";\n" .
        'var metadata="' . $fname . "\";\n" .
        'var personNames=' . json_encode($people, JSON_PRETTY_PRINT) . ";\n");
    fclose($f);
    
    set_time_limit(30);
}
