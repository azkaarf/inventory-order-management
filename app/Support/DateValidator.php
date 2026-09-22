<?php

namespace App\Support;

use DateTime;

/**
 * Extracted from three near-identical private isValidDate() methods that
 * had accumulated in PurchaseOrderService, SalesOrderService, and
 * DashboardService (see docs/quality/refactor-log.md, entry 2).
 */
final class DateValidator
{
    public static function isValid(string $date, string $format = 'Y-m-d'): bool
    {
        $d = DateTime::createFromFormat($format, $date);

        return $d !== false && $d->format($format) === $date;
    }
}
