<?php
/**
 * Shared pagination bar. Set before requiring this partial:
 * @var int $page current page (1-based)
 * @var int $totalPages
 * @var int $total total matching rows (across all pages)
 * @var int $perPage rows per page
 * @var string $baseUrl e.g. '/products'
 * @var array $queryParams current filter/search params, WITHOUT 'page'
 */
if ($total === 0) {
    return;
}

$rangeStart = ($page - 1) * $perPage + 1;
$rangeEnd = min($page * $perPage, $total);
?>
<div class="pagination-bar">
    <span>Showing <?= $rangeStart ?> to <?= $rangeEnd ?> of <?= $total ?> entries</span>
    <?php if ($totalPages > 1): ?>
        <div class="pagination-links">
            <?php
            $linkFor = static function (int $p) use ($baseUrl, $queryParams): string {
                $params = $queryParams;
                $params['page'] = $p;
                return htmlspecialchars($baseUrl . '?' . http_build_query($params));
            };
            ?>
            <?php if ($page > 1): ?>
                <a href="<?= $linkFor($page - 1) ?>" title="Previous page">&laquo;</a>
            <?php endif; ?>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <?php if ($p === $page): ?>
                    <span class="current"><?= $p ?></span>
                <?php else: ?>
                    <a href="<?= $linkFor($p) ?>"><?= $p ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="<?= $linkFor($page + 1) ?>" title="Next page">&raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
