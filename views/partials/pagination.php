<?php
/**
 * Shared pagination links. Set before requiring this partial:
 * @var int $page current page (1-based)
 * @var int $totalPages
 * @var string $baseUrl e.g. '/products'
 * @var array $queryParams current filter/search params, WITHOUT 'page'
 */
if ($totalPages <= 1) {
    return;
}
?>
<p>
    Page:
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <?php
        $params = $queryParams;
        $params['page'] = $p;
        $url = $baseUrl . '?' . http_build_query($params);
        ?>
        <?php if ($p === $page): ?>
            <strong>[<?= $p ?>]</strong>
        <?php else: ?>
            <a href="<?= htmlspecialchars($url) ?>"><?= $p ?></a>
        <?php endif; ?>
    <?php endfor; ?>
</p>
