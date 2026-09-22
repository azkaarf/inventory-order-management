CREATE TABLE IF NOT EXISTS user (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Sales', 'WarehouseStaff') NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS category (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS warehouse (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    location VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS product (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sku VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(200) NOT NULL,
    category_id INT NOT NULL,
    unit VARCHAR(30) NOT NULL,
    buy_price DECIMAL(15,2) NOT NULL,
    sell_price DECIMAL(15,2) NOT NULL,
    reorder_point INT NOT NULL DEFAULT 0,
    image_path VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_buy_price_nonneg CHECK (buy_price >= 0),
    CONSTRAINT chk_sell_price_nonneg CHECK (sell_price >= 0),
    CONSTRAINT chk_reorder_point_nonneg CHECK (reorder_point >= 0),
    CONSTRAINT fk_product_category FOREIGN KEY (category_id) REFERENCES category(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS product_stock (
    product_id INT NOT NULL,
    warehouse_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (product_id, warehouse_id),
    CONSTRAINT chk_quantity_nonneg CHECK (quantity >= 0),
    CONSTRAINT fk_product_stock_product FOREIGN KEY (product_id) REFERENCES product(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_product_stock_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouse(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS supplier (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    contact VARCHAR(150) NULL,
    address VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customer (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    contact VARCHAR(150) NULL,
    address VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS purchase_order (
    id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_id INT NOT NULL,
    warehouse_id INT NOT NULL,
    status ENUM('Draft', 'Ordered', 'PartiallyReceived', 'Received', 'Cancelled') NOT NULL DEFAULT 'Draft',
    order_date DATE NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_purchase_order_supplier FOREIGN KEY (supplier_id) REFERENCES supplier(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_purchase_order_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouse(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_purchase_order_created_by FOREIGN KEY (created_by) REFERENCES user(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_purchase_order_status (status),
    INDEX idx_purchase_order_order_date (order_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS purchase_order_item (
    id INT PRIMARY KEY AUTO_INCREMENT,
    purchase_order_id INT NOT NULL,
    product_id INT NOT NULL,
    qty_ordered INT NOT NULL,
    qty_received INT NOT NULL DEFAULT 0,
    buy_price DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_qty_ordered_positive CHECK (qty_ordered > 0),
    CONSTRAINT chk_qty_received_nonneg CHECK (qty_received >= 0),
    CONSTRAINT fk_po_item_purchase_order FOREIGN KEY (purchase_order_id) REFERENCES purchase_order(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_po_item_product FOREIGN KEY (product_id) REFERENCES product(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS stock_ledger (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    warehouse_id INT NOT NULL,
    movement_type ENUM('Receipt', 'Issue', 'Adjustment') NOT NULL,
    quantity INT NOT NULL,
    reference_type VARCHAR(20) NULL,
    reference_id INT NULL,
    performed_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_stock_ledger_quantity_positive CHECK (quantity > 0),
    CONSTRAINT fk_stock_ledger_product FOREIGN KEY (product_id) REFERENCES product(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_stock_ledger_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouse(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_stock_ledger_performed_by FOREIGN KEY (performed_by) REFERENCES user(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_stock_ledger_created_at (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sales_order (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    warehouse_id INT NOT NULL,
    status ENUM('Draft', 'PendingApproval', 'Approved', 'Fulfilled', 'Cancelled') NOT NULL DEFAULT 'Draft',
    created_by INT NOT NULL,
    approved_by INT NULL,
    order_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sales_order_customer FOREIGN KEY (customer_id) REFERENCES customer(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_sales_order_warehouse FOREIGN KEY (warehouse_id) REFERENCES warehouse(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_sales_order_created_by FOREIGN KEY (created_by) REFERENCES user(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_sales_order_approved_by FOREIGN KEY (approved_by) REFERENCES user(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_sales_order_status (status),
    INDEX idx_sales_order_order_date (order_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sales_order_item (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sales_order_id INT NOT NULL,
    product_id INT NOT NULL,
    qty INT NOT NULL,
    sell_price DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_so_qty_positive CHECK (qty > 0),
    CONSTRAINT fk_so_item_sales_order FOREIGN KEY (sales_order_id) REFERENCES sales_order(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_so_item_product FOREIGN KEY (product_id) REFERENCES product(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT INTO user (name, email, password_hash, role, is_active) VALUES
    ('Admin Demo', 'admin@demo.test', '$2b$10$v3dFCnUz0.Zvj4QzqqUIM.cJ5Gjj9rmTmqfjEl0C.E7CUVYePP7WK', 'Admin', 1),
    ('Sales Demo', 'sales1@demo.test', '$2b$10$UeVLD0qPUMMjy48LM7yH6uz/n50BJG1wL4BPN1Odp.8dmscKFcZ.a', 'Sales', 1),
    ('Warehouse Demo', 'gudang1@demo.test', '$2b$10$LTOqX2u01mV7Q18JzYiB2uYTvCN10JgvfFvGuM5c6IAt3TCtJMpD6', 'WarehouseStaff', 1),
    ('Sales Demo 2', 'sales2@demo.test', '$2b$10$KdgECWnyiQZQEFtiDAAUxuvTmdoxvbItuLtAVOZS47E2L8ICmKXOC', 'Sales', 1),
    ('Warehouse Demo 2', 'gudang2@demo.test', '$2b$10$T.o11PVx63mQW0x5vLT2uOekHrYEkdplzeLcgdtJ/tV/4nyOiduyO', 'WarehouseStaff', 1)
ON DUPLICATE KEY UPDATE email = email;

INSERT INTO category (name, description) VALUES
    ('Skincare', 'Facial cleansers, serums, moisturizers, and sun protection'),
    ('Makeup', 'Face, eye, and lip makeup products'),
    ('Hair Care', 'Shampoo, conditioner, and hair treatment products'),
    ('Body Care', 'Body lotions, washes, scrubs, and hand/foot care'),
    ('Fragrance', 'Perfumes, body mists, and scented products');

INSERT INTO warehouse (name, location, is_active) VALUES
    ('Jakarta Central Warehouse', 'Jakarta', 1),
    ('Surabaya Branch Warehouse', 'Surabaya', 1);

INSERT INTO product (sku, name, category_id, unit, buy_price, sell_price, reorder_point, image_path, is_active) VALUES
    ('SKC-001', 'Hydrating Facial Cleanser', 1, 'pcs', 28000.00, 39000.0, 5, NULL, 1),
    ('SKC-002', 'Vitamin C Brightening Serum', 1, 'pcs', 65000.00, 91000.0, 10, NULL, 1),
    ('SKC-003', 'Niacinamide Toner', 1, 'pcs', 42000.00, 59000.0, 15, NULL, 1),
    ('SKC-004', 'Daily Moisturizing Cream', 1, 'pcs', 55000.00, 77000.0, 20, NULL, 1),
    ('SKC-005', 'Sunscreen SPF 50 PA+++', 1, 'pcs', 48000.00, 67000.0, 5, NULL, 1),
    ('SKC-006', 'Retinol Night Cream', 1, 'pcs', 78000.00, 109000.0, 10, NULL, 1),
    ('MUP-001', 'Matte Liquid Foundation', 2, 'pcs', 62000.00, 87000.0, 15, NULL, 1),
    ('MUP-002', 'Full Coverage Concealer', 2, 'pcs', 45000.00, 63000.0, 20, NULL, 1),
    ('MUP-003', 'Silky Compact Powder', 2, 'pcs', 38000.00, 53000.0, 5, NULL, 1),
    ('MUP-004', 'Velvet Matte Lipstick', 2, 'pcs', 35000.00, 49000.0, 10, NULL, 1),
    ('MUP-005', 'Waterproof Mascara', 2, 'pcs', 40000.00, 56000.0, 15, NULL, 1),
    ('MUP-006', 'Eyeshadow Palette - Nude Edition', 2, 'pcs', 95000.00, 133000.0, 20, NULL, 1),
    ('HAI-001', 'Anti-Hair Fall Shampoo', 3, 'pcs', 32000.00, 45000.0, 5, NULL, 1),
    ('HAI-002', 'Keratin Smooth Conditioner', 3, 'pcs', 34000.00, 47500.0, 10, NULL, 1),
    ('HAI-003', 'Argan Hair Serum', 3, 'pcs', 58000.00, 81000.0, 15, NULL, 1),
    ('HAI-004', 'Deep Repair Hair Mask', 3, 'pcs', 46000.00, 64500.0, 20, NULL, 1),
    ('HAI-005', 'Dry Shampoo Spray', 3, 'pcs', 39000.00, 54500.0, 5, NULL, 1),
    ('HAI-006', 'Nourishing Hair Oil', 3, 'pcs', 44000.00, 61500.0, 10, NULL, 1),
    ('BOD-001', 'Whitening Body Lotion', 4, 'pcs', 36000.00, 50500.0, 15, NULL, 1),
    ('BOD-002', 'Refreshing Body Wash', 4, 'pcs', 30000.00, 42000.0, 20, NULL, 1),
    ('BOD-003', 'Coffee Body Scrub', 4, 'pcs', 41000.00, 57500.0, 5, NULL, 1),
    ('BOD-004', 'Intensive Hand Cream', 4, 'pcs', 22000.00, 31000.0, 10, NULL, 1),
    ('BOD-005', 'Body Butter - Shea & Cocoa', 4, 'pcs', 52000.00, 73000.0, 15, NULL, 1),
    ('BOD-006', 'Foot Cream', 4, 'pcs', 26000.00, 36500.0, 20, NULL, 1),
    ('FRG-001', 'Eau de Parfum - Floral Bloom', 5, 'pcs', 110000.00, 154000.0, 5, NULL, 1),
    ('FRG-002', 'Eau de Parfum - Citrus Fresh', 5, 'pcs', 110000.00, 154000.0, 10, NULL, 1),
    ('FRG-003', 'Body Mist - Vanilla Musk', 5, 'pcs', 48000.00, 67000.0, 15, NULL, 1),
    ('FRG-004', 'Perfume Rollerball - Sweet Pea', 5, 'pcs', 35000.00, 49000.0, 20, NULL, 1),
    ('FRG-005', 'Cologne Spray - Woody Amber', 5, 'pcs', 115000.00, 161000.0, 5, NULL, 1),
    ('FRG-006', 'Hair Mist - Cherry Blossom', 5, 'pcs', 42000.00, 59000.0, 10, NULL, 1);

INSERT INTO product_stock (product_id, warehouse_id, quantity) VALUES
    (1, 1, 20), (1, 2, 13), (2, 1, 25), (2, 2, 18), (3, 1, 11), (3, 2, 13),
    (4, 1, 35), (4, 2, 28), (5, 1, 20), (5, 2, 13), (6, 1, 6), (6, 2, 8),
    (7, 1, 30), (7, 2, 23), (8, 1, 35), (8, 2, 28), (9, 1, 1), (9, 2, 3),
    (10, 1, 25), (10, 2, 18), (11, 1, 30), (11, 2, 23), (12, 1, 16), (12, 2, 18),
    (13, 1, 20), (13, 2, 13), (14, 1, 25), (14, 2, 18), (15, 1, 11), (15, 2, 13),
    (16, 1, 35), (16, 2, 28), (17, 1, 20), (17, 2, 13), (18, 1, 6), (18, 2, 8),
    (19, 1, 30), (19, 2, 23), (20, 1, 35), (20, 2, 28), (21, 1, 1), (21, 2, 3),
    (22, 1, 25), (22, 2, 18), (23, 1, 30), (23, 2, 23), (24, 1, 16), (24, 2, 18),
    (25, 1, 20), (25, 2, 13), (26, 1, 25), (26, 2, 18), (27, 1, 11), (27, 2, 13),
    (28, 1, 35), (28, 2, 28), (29, 1, 20), (29, 2, 13), (30, 1, 6), (30, 2, 8);

INSERT INTO supplier (name, contact, address) VALUES
    ('PT Kosmetika Cantik Abadi', '021-1234567', 'Jakarta'),
    ('CV Sumber Kecantikan', '021-7654321', 'Bekasi');

INSERT INTO customer (name, contact, address) VALUES
    ('Klinik Kecantikan Bella', '0812-3456789', 'Jakarta'),
    ('Toko Kosmetik Cantika', '0813-9876543', 'Tangerang');

INSERT INTO purchase_order (supplier_id, warehouse_id, status, order_date, created_by) VALUES
    (2, 2, 'Draft', '2026-01-08', 1),
    (1, 1, 'Ordered', '2026-01-11', 1),
    (2, 2, 'PartiallyReceived', '2026-01-14', 3),
    (1, 1, 'Received', '2026-01-17', 1),
    (2, 2, 'Cancelled', '2026-01-20', 1),
    (1, 1, 'Draft', '2026-01-23', 3),
    (2, 2, 'Ordered', '2026-01-26', 1),
    (1, 1, 'PartiallyReceived', '2026-01-29', 1),
    (2, 2, 'Received', '2026-02-01', 3),
    (1, 1, 'Cancelled', '2026-02-04', 1),
    (2, 2, 'Ordered', '2026-02-07', 1),
    (1, 1, 'Received', '2026-02-10', 3);

INSERT INTO purchase_order_item (purchase_order_id, product_id, qty_ordered, qty_received, buy_price) VALUES
    ((SELECT id FROM purchase_order ORDER BY id LIMIT 1 OFFSET 0), 1, 13, 0, (SELECT buy_price FROM product WHERE id = 1)),
    ((SELECT id FROM purchase_order ORDER BY id LIMIT 1 OFFSET 1), 3, 16, 0, (SELECT buy_price FROM product WHERE id = 3)),
    ((SELECT id FROM purchase_order ORDER BY id LIMIT 1 OFFSET 2), 5, 19, 9, (SELECT buy_price FROM product WHERE id = 5)),
    ((SELECT id FROM purchase_order ORDER BY id LIMIT 1 OFFSET 3), 7, 22, 22, (SELECT buy_price FROM product WHERE id = 7)),
    ((SELECT id FROM purchase_order ORDER BY id LIMIT 1 OFFSET 4), 9, 10, 0, (SELECT buy_price FROM product WHERE id = 9)),
    ((SELECT id FROM purchase_order ORDER BY id LIMIT 1 OFFSET 5), 11, 13, 0, (SELECT buy_price FROM product WHERE id = 11)),
    ((SELECT id FROM purchase_order ORDER BY id LIMIT 1 OFFSET 6), 13, 16, 0, (SELECT buy_price FROM product WHERE id = 13)),
    ((SELECT id FROM purchase_order ORDER BY id LIMIT 1 OFFSET 7), 15, 19, 9, (SELECT buy_price FROM product WHERE id = 15)),
    ((SELECT id FROM purchase_order ORDER BY id LIMIT 1 OFFSET 8), 17, 22, 22, (SELECT buy_price FROM product WHERE id = 17)),
    ((SELECT id FROM purchase_order ORDER BY id LIMIT 1 OFFSET 9), 19, 10, 0, (SELECT buy_price FROM product WHERE id = 19)),
    ((SELECT id FROM purchase_order ORDER BY id LIMIT 1 OFFSET 10), 21, 13, 0, (SELECT buy_price FROM product WHERE id = 21)),
    ((SELECT id FROM purchase_order ORDER BY id LIMIT 1 OFFSET 11), 23, 16, 16, (SELECT buy_price FROM product WHERE id = 23));

INSERT INTO sales_order (customer_id, warehouse_id, status, created_by, approved_by, order_date) VALUES
    (2, 2, 'Draft', 1, NULL, '2026-02-16'),
    (1, 1, 'PendingApproval', 2, NULL, '2026-02-18'),
    (2, 2, 'Approved', 2, 1, '2026-02-20'),
    (1, 1, 'Fulfilled', 2, 1, '2026-02-22'),
    (2, 2, 'Cancelled', 2, NULL, '2026-02-24'),
    (1, 1, 'Draft', 2, NULL, '2026-02-26'),
    (2, 2, 'PendingApproval', 2, NULL, '2026-02-28'),
    (1, 1, 'Approved', 2, 1, '2026-03-02'),
    (2, 2, 'Fulfilled', 2, 1, '2026-03-04'),
    (1, 1, 'Cancelled', 2, NULL, '2026-03-06'),
    (2, 2, 'Approved', 2, 1, '2026-03-08'),
    (1, 1, 'Fulfilled', 2, 1, '2026-03-10'),
    (2, 2, 'PendingApproval', 2, NULL, '2026-03-12');

INSERT INTO sales_order_item (sales_order_id, product_id, qty, sell_price) VALUES
    ((SELECT id FROM sales_order ORDER BY id LIMIT 1 OFFSET 0), 2, 7, (SELECT sell_price FROM product WHERE id = 2)),
    ((SELECT id FROM sales_order ORDER BY id LIMIT 1 OFFSET 1), 4, 9, (SELECT sell_price FROM product WHERE id = 4)),
    ((SELECT id FROM sales_order ORDER BY id LIMIT 1 OFFSET 2), 6, 11, (SELECT sell_price FROM product WHERE id = 6)),
    ((SELECT id FROM sales_order ORDER BY id LIMIT 1 OFFSET 3), 8, 13, (SELECT sell_price FROM product WHERE id = 8)),
    ((SELECT id FROM sales_order ORDER BY id LIMIT 1 OFFSET 4), 10, 15, (SELECT sell_price FROM product WHERE id = 10)),
    ((SELECT id FROM sales_order ORDER BY id LIMIT 1 OFFSET 5), 12, 5, (SELECT sell_price FROM product WHERE id = 12)),
    ((SELECT id FROM sales_order ORDER BY id LIMIT 1 OFFSET 6), 14, 7, (SELECT sell_price FROM product WHERE id = 14)),
    ((SELECT id FROM sales_order ORDER BY id LIMIT 1 OFFSET 7), 16, 9, (SELECT sell_price FROM product WHERE id = 16)),
    ((SELECT id FROM sales_order ORDER BY id LIMIT 1 OFFSET 8), 18, 11, (SELECT sell_price FROM product WHERE id = 18)),
    ((SELECT id FROM sales_order ORDER BY id LIMIT 1 OFFSET 9), 20, 13, (SELECT sell_price FROM product WHERE id = 20)),
    ((SELECT id FROM sales_order ORDER BY id LIMIT 1 OFFSET 10), 22, 15, (SELECT sell_price FROM product WHERE id = 22)),
    ((SELECT id FROM sales_order ORDER BY id LIMIT 1 OFFSET 11), 24, 5, (SELECT sell_price FROM product WHERE id = 24)),
    ((SELECT id FROM sales_order ORDER BY id LIMIT 1 OFFSET 12), 26, 7, (SELECT sell_price FROM product WHERE id = 26));