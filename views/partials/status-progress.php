<?php
/**
 * Shared "status breakdown" progress bar list.
 * Set before requiring this partial:
 * @var string[] $statuses status labels in display order
 * @var array<string,int> $counts status => count map
 */
use App\Support\StatusBadge;

$progressTotal = array_sum($counts);
?>
<div class="progress-list">
    <?php foreach ($statuses as $s): ?>
        <?php
        $count = $counts[$s] ?? 0;
        $pct = $progressTotal > 0 ? round($count / $progressTotal * 100) : 0;
        $color = StatusBadge::colorFor($s);
        ?>
        <div class="progress-row">
            <span class="progress-row-label"><?= StatusBadge::render($s) ?></span>
            <span class="progress-track">
                <span class="progress-bar progress-bar-<?= $color ?>" style="width: <?= $pct ?>%"></span>
            </span>
            <span class="progress-row-count"><?= $count ?></span>
        </div>
    <?php endforeach; ?>
</div>
