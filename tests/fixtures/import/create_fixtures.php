<?php

declare(strict_types=1);

require __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Valid import file with 2 rows
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->fromArray(['namespace', 'group', 'default', 'en'], null, 'A1');
$sheet->fromArray(['*', 'admin', 'new-key', 'new value'], null, 'A2');
$sheet->fromArray(['*', 'group', 'key', 'updated english'], null, 'A3');

$writer = new Xlsx($spreadsheet);
$writer->save(__DIR__ . '/translations_valid.xlsx');

// Only-missing import file with 1 new key
$spreadsheet2 = new Spreadsheet();
$sheet2 = $spreadsheet2->getActiveSheet();
$sheet2->fromArray(['namespace', 'group', 'default', 'en'], null, 'A1');
$sheet2->fromArray(['*', 'fresh', 'fresh-key', 'fresh value'], null, 'A2');

$writer2 = new Xlsx($spreadsheet2);
$writer2->save(__DIR__ . '/translations_only_missing.xlsx');

// Invalid import file (missing required headers)
$spreadsheet3 = new Spreadsheet();
$sheet3 = $spreadsheet3->getActiveSheet();
$sheet3->fromArray(['wrong', 'headers', 'only'], null, 'A1');
$sheet3->fromArray(['a', 'b', 'c'], null, 'A2');

$writer3 = new Xlsx($spreadsheet3);
$writer3->save(__DIR__ . '/translations_invalid.xlsx');

// No-conflict import file (existing key with same value + new key)
$spreadsheet4 = new Spreadsheet();
$sheet4 = $spreadsheet4->getActiveSheet();
$sheet4->fromArray(['namespace', 'group', 'default', 'en'], null, 'A1');
// same as existing
$sheet4->fromArray(['*', 'group', 'key', 'english'], null, 'A2');
$sheet4->fromArray(['*', 'admin', 'brand-new', 'brand new value'], null, 'A3');

$writer4 = new Xlsx($spreadsheet4);
$writer4->save(__DIR__ . '/translations_no_conflict.xlsx');

echo "Fixtures created successfully\n";
