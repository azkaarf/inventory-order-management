<?php
/** @var \App\Entity\SalesOrder $salesOrder */
/** @var \App\Entity\Product[] $products */
/** @var string[] $addItemErrors */
/** @var string[] $issueErrors */
/** @var array $oldItem */
$pageTitle = 'Sales Order #' . $salesOrder->id;
require __DIR__ . '/../layout/header.php';
$error = $_GET['error'] ?? null;
$currentUser = $_SESSION['user'];
$isAdmin = $currentUser['role'] === 'Admin';
$isSales = $currentUser['role'] === 'Sales';
$isWarehouse = $currentUser['role'] === 'WarehouseStaff';
$ownsOrder = $salesOrder->createdBy === (int) $currentUser['id'];
?>
<h1>Sales Order #<?= $salesOrder->id ?></h1>
<p><a href="/sales-orders">&larr; Back</a></p>

<?php if ($error === 'cannot-submit'): ?>
    <div class="alert-error">Cannot submit for approval — add at least one item first.</div>
<?php elseif ($error === 'cannot-approve'): ?>
    <div class="alert-error">Cannot approve — either this order isn't pending, or you created it yourself (a creator cannot approve their own order).</div>
<?php elseif ($error === 'cannot-cancel'): ?>
    <div class="alert-error">This sales order can no longer be cancelled.</div>
<?php endif; ?>

<table>
    <tr><th>Customer</th><td><?= htmlspecialchars($salesOrder->customerName) ?></td></tr>
    <tr><th>Warehouse</th><td><?= htmlspecialchars($salesOrder->warehouseName) ?></td></tr>
    <tr><th>Order Date</th><td><?= htmlspecialchars($salesOrder->orderDate) ?></td></tr>
    <tr><th>Status</th><td><?= \App\Support\StatusBadge::render($salesOrder->status) ?></td></tr>
    <tr><th>Created By</th><td><?= htmlspecialchars($salesOrder->createdByName) ?></td></tr>
    <?php if ($salesOrder->approvedByName): ?>
        <tr><th>Approved By</th><td><?= htmlspecialchars($salesOrder->approvedByName) ?></td></tr>
    <?php endif; ?>
</table>

<p>
    <?php if ($salesOrder->status === 'Draft' && ($isAdmin || ($isSales && $ownsOrder))): ?>
        <form method="POST" action="/sales-orders/submit" style="display:inline">
                <?= \App\Support\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $salesOrder->id ?>">
            <button type="submit" title="Submit for Approval">📨</button>
        </form>
    <?php endif; ?>

    <?php if ($salesOrder->status === 'PendingApproval' && $isAdmin && !$ownsOrder): ?>
        <form method="POST" action="/sales-orders/approve" style="display:inline">
                <?= \App\Support\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $salesOrder->id ?>">
            <button type="submit" title="Approve">✅</button>
        </form>
        <form method="POST" action="/sales-orders/reject" style="display:inline" onsubmit="return confirm('Reject this sales order?');">
                <?= \App\Support\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $salesOrder->id ?>">
            <button type="submit" title="Reject">❌</button>
        </form>
    <?php endif; ?>

    <?php if ($salesOrder->status === 'PendingApproval' && $isAdmin && $ownsOrder): ?>
        <span class="hint">You created this order, so you can't approve/reject it yourself.</span>
    <?php endif; ?>

    <?php if ($salesOrder->status === 'Approved' && ($isAdmin || $isWarehouse)): ?>
        <form method="POST" action="/sales-orders/issue" style="display:inline">
                <?= \App\Support\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $salesOrder->id ?>">
            <button type="submit" title="Process Goods Issue">📤</button>
        </form>
    <?php endif; ?>

    <?php if (!in_array($salesOrder->status, ['Fulfilled', 'Cancelled'], true) && ($isAdmin || ($isSales && $ownsOrder))): ?>
        <form method="POST" action="/sales-orders/cancel" style="display:inline" onsubmit="return confirm('Cancel this sales order?');">
                <?= \App\Support\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $salesOrder->id ?>">
            <button type="submit" title="Cancel">✖️</button>
        </form>
    <?php endif; ?>
</p>

<h2>Items</h2>

<?php if (!empty($issueErrors)): ?>
    <div class="alert-error">
        <ul><?php foreach ($issueErrors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<table>
    <thead><tr><th>Product</th><th>Qty</th><th>Sell Price</th></tr></thead>
    <tbody>
    <?php if (empty($salesOrder->items)): ?>
        <tr><td colspan="3">No items yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($salesOrder->items as $item): ?>
        <tr>
            <td><?= htmlspecialchars($item->productName) ?></td>
            <td><?= $item->qty ?></td>
            <td><?= number_format($item->sellPrice, 0, '.', ',') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php if ($salesOrder->status === 'Draft' && ($isAdmin || ($isSales && $ownsOrder))): ?>
    <h2>Add Item</h2>

    <?php if (!empty($addItemErrors)): ?>
        <div class="alert-error">
            <ul><?php foreach ($addItemErrors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="/sales-orders/add-item" class="stacked">
                <?= \App\Support\Csrf::field() ?>
        <input type="hidden" name="sales_order_id" value="<?= $salesOrder->id ?>">
        <label>Product
            <select name="product_id" id="so-product-select" required>
                <option value="">-- select --</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= $product->id ?>" data-sku="<?= htmlspecialchars($product->sku) ?>" <?= (string) $product->id === $oldItem['product_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($product->sku . ' — ' . $product->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <div id="so-stock-info" class="hint"></div>
        <label>Quantity <input type="number" name="qty" min="1" value="<?= htmlspecialchars($oldItem['qty']) ?>" required></label>
        <button type="submit" title="Add Item">➕</button>
    </form>

    <script src="/assets/js/stock-availability.js?v=<?= @filemtime(__DIR__ . '/../../public/assets/js/stock-availability.js') ?: time() ?>"></script>
    <script>
    (() => {
        'use strict';

        const productSelect = document.getElementById('so-product-select');
        const stockInfo = document.getElementById('so-stock-info');
        if (!productSelect || !stockInfo) {
            return;
        }

        const { StockLookupError, formatAvailabilityText } = window.StockAvailability;

        // Bab 5.1 (Web Storage): cache jawaban per SKU selama sesi supaya
        // pilih-ulang produk yang sama nggak selalu hit network. TTL pendek
        // (30 detik) karena stok ini data yang benar-benar berubah (goods
        // receipt/issue), bukan data statis yang aman di-cache lama.
        const CACHE_TTL_MS = 30000;
        const cacheKey = (sku) => `stock-availability:${sku}`;

        const readCache = (sku) => {
            try {
                const raw = sessionStorage.getItem(cacheKey(sku));
                if (!raw) {
                    return null;
                }
                const { data, cachedAt } = JSON.parse(raw);
                return Date.now() - cachedAt > CACHE_TTL_MS ? null : data;
            } catch {
                // sessionStorage bisa nggak tersedia (private mode/quota penuh) -
                // gagal diam-diam, fallback ke network, jangan sampai fitur utama ikut mati.
                return null;
            }
        };

        const writeCache = (sku, data) => {
            try {
                sessionStorage.setItem(cacheKey(sku), JSON.stringify({ data, cachedAt: Date.now() }));
            } catch {
                // idem - abaikan saja kalau storage nggak bisa ditulis.
            }
        };

        // Bab 2.3 (recursion) + Bab 4.1 (setTimeout): retry sekali dengan
        // delay pendek kalau kegagalan kemungkinan cuma masalah jaringan
        // sesaat. 404 (produk memang nggak ada) sengaja TIDAK di-retry -
        // mengulang tidak akan mengubah hasil itu.
        const fetchAvailability = (sku, attemptsLeft = 2) =>
            fetch(`/api/products/${encodeURIComponent(sku)}/availability`)
                .then((response) => {
                    if (response.status === 404) {
                        throw new StockLookupError('Product not found.', 'NOT_FOUND');
                    }
                    if (!response.ok) {
                        throw new StockLookupError('Lookup failed.', 'NETWORK_ERROR');
                    }
                    return response.json();
                })
                .catch((error) => {
                    const isRetryable = !(error instanceof StockLookupError) || error.code === 'NETWORK_ERROR';
                    if (isRetryable && attemptsLeft > 1) {
                        return new Promise((resolve) => setTimeout(resolve, 400))
                            .then(() => fetchAvailability(sku, attemptsLeft - 1));
                    }
                    throw error;
                });

        productSelect.addEventListener('change', async () => {
            const selected = productSelect.options[productSelect.selectedIndex];
            const sku = selected ? selected.getAttribute('data-sku') : null;

            if (!sku) {
                stockInfo.textContent = '';
                return;
            }

            const cached = readCache(sku);
            if (cached) {
                stockInfo.textContent = formatAvailabilityText(cached);
                return;
            }

            stockInfo.textContent = 'Checking stock...';
            const startedAt = performance.now();

            try {
                const data = await fetchAvailability(sku);
                writeCache(sku, data);
                stockInfo.textContent = formatAvailabilityText(data);
            } catch (error) {
                stockInfo.textContent = error instanceof StockLookupError && error.code === 'NOT_FOUND'
                    ? 'This product could not be found.'
                    : 'Could not check stock for this product.';
            } finally {
                // Bab 12.2: bukti profiling sederhana - biar bisa dibuktikan
                // lookup ini nggak jadi hotspot, bukan sekadar diasumsikan cepat.
                console.debug(`stock availability lookup: ${(performance.now() - startedAt).toFixed(1)}ms`);
            }
        });
    })();
    </script>
<?php endif; ?>

<?php require __DIR__ . '/../layout/footer.php'; ?>
