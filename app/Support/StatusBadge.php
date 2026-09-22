<?php

namespace App\Support;

/**
 * Renders a status string as a small colored pill. Shared by the dashboard
 * and every list/detail page that shows a PO/SO/product status, so the same
 * status always looks the same color everywhere in the app.
 */
final class StatusBadge
{
    private const COLOR_MAP = [
        'Draft' => 'gray',
        'Ordered' => 'amber',
        'PendingApproval' => 'amber',
        'PartiallyReceived' => 'blue',
        'Approved' => 'blue',
        'Received' => 'green',
        'Fulfilled' => 'green',
        'Cancelled' => 'red',
        'Active' => 'green',
        'Inactive' => 'gray',
    ];

    public static function render(string $status): string
    {
        $color = self::COLOR_MAP[$status] ?? 'gray';

        return sprintf('<span class="badge badge-%s">%s</span>', $color, htmlspecialchars($status));
    }
}
