<?php

namespace App\Support;

/**
 * Shared CSV streaming helper. Extracted from ReportController so the new
 * per-table exports (Products/PO/SO) don't each carry their own copy of the
 * same 8-line method.
 */
final class CsvResponse
{
    public static function stream(string $filename, callable $writer): void
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        $writer($out);
        fclose($out);
    }
}
