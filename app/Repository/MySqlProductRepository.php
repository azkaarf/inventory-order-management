<?php

namespace App\Repository;

use App\Entity\Product;
use App\Support\Database;

final class MySqlProductRepository implements ProductRepositoryInterface
{
    public function findAll(): array
    {
        $stmt = Database::connection()->query(
            'SELECT id, sku, name, category_id, unit, buy_price, sell_price, reorder_point, image_path, is_active
             FROM product ORDER BY name'
        );

        return array_map([$this, 'toEntity'], $stmt->fetchAll());
    }

    public function findById(int $id): ?Product
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, sku, name, category_id, unit, buy_price, sell_price, reorder_point, image_path, is_active
             FROM product WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? $this->toEntity($row) : null;
    }

    public function findBySku(string $sku): ?Product
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, sku, name, category_id, unit, buy_price, sell_price, reorder_point, image_path, is_active
             FROM product WHERE sku = ? LIMIT 1'
        );
        $stmt->execute([$sku]);
        $row = $stmt->fetch();

        return $row ? $this->toEntity($row) : null;
    }

    public function skuExists(string $sku, ?int $excludeId = null): bool
    {
        if ($excludeId === null) {
            $stmt = Database::connection()->prepare('SELECT 1 FROM product WHERE sku = ? LIMIT 1');
            $stmt->execute([$sku]);
        } else {
            $stmt = Database::connection()->prepare('SELECT 1 FROM product WHERE sku = ? AND id != ? LIMIT 1');
            $stmt->execute([$sku, $excludeId]);
        }

        return (bool) $stmt->fetchColumn();
    }

    public function create(
        string $sku,
        string $name,
        int $categoryId,
        string $unit,
        float $buyPrice,
        float $sellPrice,
        int $reorderPoint,
        ?string $imagePath,
    ): Product {
        $stmt = Database::connection()->prepare(
            'INSERT INTO product (sku, name, category_id, unit, buy_price, sell_price, reorder_point, image_path, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)'
        );
        $stmt->execute([$sku, $name, $categoryId, $unit, $buyPrice, $sellPrice, $reorderPoint, $imagePath]);
        $id = (int) Database::connection()->lastInsertId();

        return new Product($id, $sku, $name, $categoryId, $unit, $buyPrice, $sellPrice, $reorderPoint, $imagePath, true);
    }

    public function update(
        int $id,
        string $name,
        int $categoryId,
        string $unit,
        float $buyPrice,
        float $sellPrice,
        int $reorderPoint,
        ?string $imagePath,
    ): void {
        $stmt = Database::connection()->prepare(
            'UPDATE product SET name = ?, category_id = ?, unit = ?, buy_price = ?, sell_price = ?,
                reorder_point = ?, image_path = ? WHERE id = ?'
        );
        $stmt->execute([$name, $categoryId, $unit, $buyPrice, $sellPrice, $reorderPoint, $imagePath, $id]);
    }

    public function setActive(int $id, bool $isActive): void
    {
        $stmt = Database::connection()->prepare('UPDATE product SET is_active = ? WHERE id = ?');
        $stmt->execute([$isActive ? 1 : 0, $id]);
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public function search(?string $q, ?int $categoryId, ?string $stockStatus, int $page, int $perPage): array
    {
        $conditions = [];
        $params = [];

        if ($q !== null && $q !== '') {
            $conditions[] = '(p.name LIKE ? OR p.sku LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }

        if ($categoryId !== null) {
            $conditions[] = 'p.category_id = ?';
            $params[] = $categoryId;
        }

        $where = $conditions === [] ? '' : ('WHERE ' . implode(' AND ', $conditions));

        $having = '';
        if ($stockStatus === 'low') {
            $having = 'HAVING total_stock < p.reorder_point';
        } elseif ($stockStatus === 'normal') {
            $having = 'HAVING total_stock >= p.reorder_point';
        }

        // Heredoc: perlu interpolasi $where dan $having.
        $baseQuery = <<<SQL

            SELECT p.id, p.sku, p.name, c.name AS category_name, p.unit, p.sell_price, p.reorder_point, p.is_active,
                   COALESCE(SUM(ps.quantity), 0) AS total_stock
            FROM product p
            JOIN category c ON c.id = p.category_id
            LEFT JOIN product_stock ps ON ps.product_id = p.id
            $where
            GROUP BY p.id
            $having
            SQL;

        $countStmt = Database::connection()->prepare("SELECT COUNT(*) FROM ($baseQuery) AS counted");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $listStmt = Database::connection()->prepare($baseQuery . " ORDER BY p.name LIMIT {$perPage} OFFSET {$offset}");
        $listStmt->execute($params);

        return [
            'items' => $listStmt->fetchAll(),
            'total' => $total,
        ];
    }

    private function toEntity(array $row): Product
    {
        return new Product(
            id: (int) $row['id'],
            sku: $row['sku'],
            name: $row['name'],
            categoryId: (int) $row['category_id'],
            unit: $row['unit'],
            buyPrice: (float) $row['buy_price'],
            sellPrice: (float) $row['sell_price'],
            reorderPoint: (int) $row['reorder_point'],
            imagePath: $row['image_path'],
            isActive: (bool) $row['is_active'],
        );
    }
}
