<?php

namespace App\Support;

/**
 * Bab 6.2: CSRF token melindungi state-changing browser request berbasis
 * session. Token disimpan di session (bukan di database), satu token per
 * session, diverifikasi lewat hash_equals() (timing-safe) supaya nggak bisa
 * ditebak lewat timing attack.
 */
final class Csrf
{
    private const SESSION_KEY = 'csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Dipanggil setelah login berhasil, supaya token dari sebelum-login
     * tidak ikut terbawa ke sesi yang sudah terautentikasi.
     */
    public static function regenerate(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::token()) . '">';
    }

    public static function verify(?string $submittedToken): bool
    {
        if (empty($_SESSION[self::SESSION_KEY]) || $submittedToken === null || $submittedToken === '') {
            return false;
        }

        return hash_equals($_SESSION[self::SESSION_KEY], $submittedToken);
    }
}
