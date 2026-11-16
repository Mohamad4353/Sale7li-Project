<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a CSV download. Rows are written one at a time so a large export never
 * builds the whole file in memory. Every cell is passed through fputcsv (RFC
 * 4180 quoting) and first defused against spreadsheet formula injection; a
 * UTF-8 BOM is emitted so Excel opens Arabic content correctly.
 */
final class CsvExport
{
    /**
     * @param  list<string>  $headings
     * @param  iterable<int, list<string|int|float|null>>  $rows
     */
    public static function download(string $filename, array $headings, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, array_map(self::defuse(...), $headings));
            foreach ($rows as $row) {
                fputcsv($out, array_map(self::defuse(...), $row));
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Prefix a lone apostrophe when a value would otherwise be interpreted as a
     * formula by Excel / Sheets / LibreOffice (=, +, -, @, or a leading control
     * character). fputcsv still handles the surrounding quoting.
     */
    private static function defuse(string|int|float|null $value): string
    {
        $value = (string) ($value ?? '');

        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
