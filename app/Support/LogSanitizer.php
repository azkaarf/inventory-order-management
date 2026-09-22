<?php

namespace App\Support;

/**
 * Bab 6.3: hilangkan CR/LF sebelum sebuah nilai masuk ke log, supaya nilai
 * itu (mis. pesan exception yang bisa saja berasal dari input eksternal)
 * nggak bisa menyuntikkan baris log palsu (log poisoning/log forging).
 */
final class LogSanitizer
{
    public static function clean(string $value): string
    {
        return str_replace(["\r", "\n"], ' ', $value);
    }
}
