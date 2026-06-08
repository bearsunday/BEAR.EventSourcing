-- EC-CUBE for BEAR.Sunday Database Schema
-- Compatible with MySQL 8.x and PostgreSQL 12+

-- Master tables
CREATE TABLE IF NOT EXISTS mtb_pref (
    id INT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sort_no INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS mtb_sex (
    id INT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sort_no INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS mtb_customer_status (
    id INT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sort_no INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS mtb_product_status (
    id INT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sort_no INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS mtb_order_status (
    id INT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sort_no INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS mtb_order_item_type (
    id INT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sort_no INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS mtb_tax_type (
    id INT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sort_no INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS mtb_tax_display_type (
    id INT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sort_no INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS mtb_sale_type (
    id INT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sort_no INT NOT NULL DEFAULT 0
);

-- Category
CREATE TABLE IF NOT EXISTS category (
    id INT PRIMARY KEY AUTO_INCREMENT,
    parent_id INT NULL,
    name VARCHAR(255) NOT NULL,
    level INT NOT NULL DEFAULT 1,
    sort_no INT NOT NULL DEFAULT 0,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL,
    FOREIGN KEY (parent_id) REFERENCES category(id) ON DELETE SET NULL,
    INDEX idx_parent (parent_id),
    INDEX idx_sort (sort_no)
);

-- Product
CREATE TABLE IF NOT EXISTS product (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    product_status_id INT NOT NULL,
    note TEXT NULL,
    description_list TEXT NULL,
    description_detail TEXT NULL,
    search_word TEXT NULL,
    free_area TEXT NULL,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL,
    FOREIGN KEY (product_status_id) REFERENCES mtb_product_status(id),
    INDEX idx_status (product_status_id),
    INDEX idx_name (name),
    INDEX idx_update (update_date)
);

-- Product Category relation
CREATE TABLE IF NOT EXISTS product_category (
    product_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (product_id, category_id),
    FOREIGN KEY (product_id) REFERENCES product(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES category(id) ON DELETE CASCADE
);

-- Class Name (規格)
CREATE TABLE IF NOT EXISTS class_name (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    backend_name VARCHAR(255) NOT NULL DEFAULT '',
    sort_no INT NOT NULL DEFAULT 0,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL
);

-- Class Category (規格分類)
CREATE TABLE IF NOT EXISTS class_category (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class_name_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    backend_name VARCHAR(255) NOT NULL DEFAULT '',
    sort_no INT NOT NULL DEFAULT 0,
    visible TINYINT(1) NOT NULL DEFAULT 1,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL,
    FOREIGN KEY (class_name_id) REFERENCES class_name(id) ON DELETE CASCADE
);

-- Product Class (商品規格)
CREATE TABLE IF NOT EXISTS product_class (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    sale_type_id INT NOT NULL DEFAULT 1,
    class_category_id1 INT NULL,
    class_category_id2 INT NULL,
    code VARCHAR(255) NOT NULL DEFAULT '',
    stock INT NULL,
    stock_unlimited TINYINT(1) NOT NULL DEFAULT 0,
    price01 DECIMAL(12, 2) NULL,
    price02 DECIMAL(12, 2) NULL,
    delivery_fee DECIMAL(12, 2) NULL,
    visible TINYINT(1) NOT NULL DEFAULT 1,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL,
    FOREIGN KEY (product_id) REFERENCES product(id) ON DELETE CASCADE,
    FOREIGN KEY (sale_type_id) REFERENCES mtb_sale_type(id),
    FOREIGN KEY (class_category_id1) REFERENCES class_category(id) ON DELETE SET NULL,
    FOREIGN KEY (class_category_id2) REFERENCES class_category(id) ON DELETE SET NULL,
    INDEX idx_product (product_id),
    INDEX idx_code (code)
);

-- Product Image
CREATE TABLE IF NOT EXISTS product_image (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    sort_no INT NOT NULL DEFAULT 0,
    create_date DATETIME NOT NULL,
    FOREIGN KEY (product_id) REFERENCES product(id) ON DELETE CASCADE,
    INDEX idx_product (product_id)
);

-- Product Stock
CREATE TABLE IF NOT EXISTS product_stock (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_class_id INT NOT NULL UNIQUE,
    stock INT NULL,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL,
    FOREIGN KEY (product_class_id) REFERENCES product_class(id) ON DELETE CASCADE
);

-- Tag
CREATE TABLE IF NOT EXISTS tag (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    sort_no INT NOT NULL DEFAULT 0,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL
);

-- Product Tag relation
CREATE TABLE IF NOT EXISTS product_tag (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    tag_id INT NOT NULL,
    create_date DATETIME NOT NULL,
    FOREIGN KEY (product_id) REFERENCES product(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tag(id) ON DELETE CASCADE,
    UNIQUE KEY uk_product_tag (product_id, tag_id)
);

-- Customer
CREATE TABLE IF NOT EXISTS customer (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_status_id INT NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    salt VARCHAR(255) NULL,
    name01 VARCHAR(255) NOT NULL,
    name02 VARCHAR(255) NOT NULL,
    kana01 VARCHAR(255) NULL,
    kana02 VARCHAR(255) NULL,
    company_name VARCHAR(255) NULL,
    postal_code VARCHAR(10) NULL,
    pref_id INT NULL,
    addr01 VARCHAR(255) NULL,
    addr02 VARCHAR(255) NULL,
    phone_number VARCHAR(20) NULL,
    birth DATE NULL,
    sex_id INT NULL,
    point INT NOT NULL DEFAULT 0,
    secret_key VARCHAR(255) NULL,
    reset_key VARCHAR(255) NULL,
    reset_expire DATETIME NULL,
    first_buy_date DATETIME NULL,
    last_buy_date DATETIME NULL,
    buy_times INT NOT NULL DEFAULT 0,
    buy_total DECIMAL(12, 2) NOT NULL DEFAULT 0,
    note TEXT NULL,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL,
    FOREIGN KEY (customer_status_id) REFERENCES mtb_customer_status(id),
    FOREIGN KEY (pref_id) REFERENCES mtb_pref(id),
    FOREIGN KEY (sex_id) REFERENCES mtb_sex(id),
    INDEX idx_email (email),
    INDEX idx_status (customer_status_id)
);

-- Customer Address
CREATE TABLE IF NOT EXISTS customer_address (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    name01 VARCHAR(255) NOT NULL,
    name02 VARCHAR(255) NOT NULL,
    kana01 VARCHAR(255) NULL,
    kana02 VARCHAR(255) NULL,
    company_name VARCHAR(255) NULL,
    postal_code VARCHAR(10) NULL,
    pref_id INT NULL,
    addr01 VARCHAR(255) NULL,
    addr02 VARCHAR(255) NULL,
    phone_number VARCHAR(20) NULL,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL,
    FOREIGN KEY (customer_id) REFERENCES customer(id) ON DELETE CASCADE,
    FOREIGN KEY (pref_id) REFERENCES mtb_pref(id)
);

-- Payment
CREATE TABLE IF NOT EXISTS payment (
    id INT PRIMARY KEY AUTO_INCREMENT,
    method VARCHAR(255) NOT NULL,
    charge DECIMAL(12, 2) NOT NULL DEFAULT 0,
    rule_min DECIMAL(12, 2) NULL,
    rule_max DECIMAL(12, 2) NULL,
    sort_no INT NOT NULL DEFAULT 0,
    visible TINYINT(1) NOT NULL DEFAULT 1,
    method_class VARCHAR(255) NULL,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL
);

-- Delivery
CREATE TABLE IF NOT EXISTS delivery (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sale_type_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    service_name VARCHAR(255) NULL,
    description TEXT NULL,
    confirm_url VARCHAR(255) NULL,
    sort_no INT NOT NULL DEFAULT 0,
    visible TINYINT(1) NOT NULL DEFAULT 1,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL,
    FOREIGN KEY (sale_type_id) REFERENCES mtb_sale_type(id)
);

-- Delivery Fee
CREATE TABLE IF NOT EXISTS delivery_fee (
    id INT PRIMARY KEY AUTO_INCREMENT,
    delivery_id INT NOT NULL,
    pref_id INT NOT NULL,
    fee DECIMAL(12, 2) NOT NULL DEFAULT 0,
    FOREIGN KEY (delivery_id) REFERENCES delivery(id) ON DELETE CASCADE,
    FOREIGN KEY (pref_id) REFERENCES mtb_pref(id),
    UNIQUE KEY uk_delivery_pref (delivery_id, pref_id)
);

-- Delivery Time
CREATE TABLE IF NOT EXISTS delivery_time (
    id INT PRIMARY KEY AUTO_INCREMENT,
    delivery_id INT NOT NULL,
    delivery_time VARCHAR(255) NOT NULL,
    sort_no INT NOT NULL DEFAULT 0,
    visible TINYINT(1) NOT NULL DEFAULT 1,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL,
    FOREIGN KEY (delivery_id) REFERENCES delivery(id) ON DELETE CASCADE
);

-- Payment Option (Payment-Delivery relation)
CREATE TABLE IF NOT EXISTS payment_option (
    delivery_id INT NOT NULL,
    payment_id INT NOT NULL,
    PRIMARY KEY (delivery_id, payment_id),
    FOREIGN KEY (delivery_id) REFERENCES delivery(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_id) REFERENCES payment(id) ON DELETE CASCADE
);

-- Cart
CREATE TABLE IF NOT EXISTS cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cart_key VARCHAR(255) NULL,
    customer_id INT NULL,
    pre_order_id VARCHAR(255) NULL,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL,
    FOREIGN KEY (customer_id) REFERENCES customer(id) ON DELETE SET NULL,
    INDEX idx_cart_key (cart_key),
    INDEX idx_customer (customer_id)
);

-- Cart Item
CREATE TABLE IF NOT EXISTS cart_item (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cart_id INT NOT NULL,
    product_class_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(12, 2) NOT NULL,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL,
    FOREIGN KEY (cart_id) REFERENCES cart(id) ON DELETE CASCADE,
    FOREIGN KEY (product_class_id) REFERENCES product_class(id)
);

-- Order
CREATE TABLE IF NOT EXISTS `order` (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pre_order_id VARCHAR(255) NULL,
    customer_id INT NULL,
    order_status_id INT NOT NULL,
    payment_id INT NULL,
    order_no VARCHAR(255) NOT NULL UNIQUE,
    message TEXT NULL,
    name01 VARCHAR(255) NOT NULL,
    name02 VARCHAR(255) NOT NULL,
    kana01 VARCHAR(255) NULL,
    kana02 VARCHAR(255) NULL,
    company_name VARCHAR(255) NULL,
    email VARCHAR(255) NULL,
    phone_number VARCHAR(20) NULL,
    postal_code VARCHAR(10) NULL,
    pref_id INT NULL,
    addr01 VARCHAR(255) NULL,
    addr02 VARCHAR(255) NULL,
    subtotal DECIMAL(12, 2) NOT NULL DEFAULT 0,
    discount DECIMAL(12, 2) NOT NULL DEFAULT 0,
    delivery_fee_total DECIMAL(12, 2) NOT NULL DEFAULT 0,
    charge DECIMAL(12, 2) NOT NULL DEFAULT 0,
    tax DECIMAL(12, 2) NOT NULL DEFAULT 0,
    total DECIMAL(12, 2) NOT NULL DEFAULT 0,
    payment_total DECIMAL(12, 2) NOT NULL DEFAULT 0,
    payment_date DATETIME NULL,
    order_date DATETIME NULL,
    note TEXT NULL,
    add_point INT NOT NULL DEFAULT 0,
    use_point INT NOT NULL DEFAULT 0,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL,
    FOREIGN KEY (customer_id) REFERENCES customer(id) ON DELETE SET NULL,
    FOREIGN KEY (order_status_id) REFERENCES mtb_order_status(id),
    FOREIGN KEY (payment_id) REFERENCES payment(id),
    FOREIGN KEY (pref_id) REFERENCES mtb_pref(id),
    INDEX idx_order_no (order_no),
    INDEX idx_customer (customer_id),
    INDEX idx_status (order_status_id),
    INDEX idx_order_date (order_date)
);

-- Shipping
CREATE TABLE IF NOT EXISTS shipping (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    delivery_id INT NULL,
    name01 VARCHAR(255) NOT NULL,
    name02 VARCHAR(255) NOT NULL,
    kana01 VARCHAR(255) NULL,
    kana02 VARCHAR(255) NULL,
    company_name VARCHAR(255) NULL,
    phone_number VARCHAR(20) NULL,
    postal_code VARCHAR(10) NULL,
    pref_id INT NULL,
    addr01 VARCHAR(255) NULL,
    addr02 VARCHAR(255) NULL,
    shipping_delivery_date DATE NULL,
    shipping_delivery_time VARCHAR(255) NULL,
    shipping_date DATETIME NULL,
    tracking_number VARCHAR(255) NULL,
    note TEXT NULL,
    sort_no INT NOT NULL DEFAULT 0,
    mail_send_flg TINYINT(1) NOT NULL DEFAULT 0,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL,
    FOREIGN KEY (order_id) REFERENCES `order`(id) ON DELETE CASCADE,
    FOREIGN KEY (delivery_id) REFERENCES delivery(id),
    FOREIGN KEY (pref_id) REFERENCES mtb_pref(id)
);

-- Order Item
CREATE TABLE IF NOT EXISTS order_item (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NULL,
    product_class_id INT NULL,
    shipping_id INT NULL,
    order_item_type_id INT NOT NULL DEFAULT 1,
    tax_type_id INT NULL,
    tax_display_type_id INT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_code VARCHAR(255) NULL,
    class_name1 VARCHAR(255) NULL,
    class_name2 VARCHAR(255) NULL,
    class_category_name1 VARCHAR(255) NULL,
    class_category_name2 VARCHAR(255) NULL,
    price DECIMAL(12, 2) NOT NULL,
    quantity INT NOT NULL,
    tax DECIMAL(12, 2) NOT NULL DEFAULT 0,
    tax_rate DECIMAL(5, 2) NOT NULL DEFAULT 0,
    tax_rule_id INT NOT NULL DEFAULT 0,
    point_rate INT NOT NULL DEFAULT 0,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL,
    FOREIGN KEY (order_id) REFERENCES `order`(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES product(id) ON DELETE SET NULL,
    FOREIGN KEY (product_class_id) REFERENCES product_class(id) ON DELETE SET NULL,
    FOREIGN KEY (shipping_id) REFERENCES shipping(id) ON DELETE SET NULL,
    FOREIGN KEY (order_item_type_id) REFERENCES mtb_order_item_type(id),
    INDEX idx_order (order_id)
);

-- Event Store (for Event Sourcing)
CREATE TABLE IF NOT EXISTS event_store (
    id VARCHAR(36) PRIMARY KEY,
    timestamp DATETIME(6) NOT NULL,
    uri VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    params JSON,
    result JSON,
    INDEX idx_timestamp (timestamp),
    INDEX idx_uri (uri),
    INDEX idx_method (method)
);

-- Insert master data
INSERT INTO mtb_pref (id, name, sort_no) VALUES
(1, '北海道', 1), (2, '青森県', 2), (3, '岩手県', 3), (4, '宮城県', 4),
(5, '秋田県', 5), (6, '山形県', 6), (7, '福島県', 7), (8, '茨城県', 8),
(9, '栃木県', 9), (10, '群馬県', 10), (11, '埼玉県', 11), (12, '千葉県', 12),
(13, '東京都', 13), (14, '神奈川県', 14), (15, '新潟県', 15), (16, '富山県', 16),
(17, '石川県', 17), (18, '福井県', 18), (19, '山梨県', 19), (20, '長野県', 20),
(21, '岐阜県', 21), (22, '静岡県', 22), (23, '愛知県', 23), (24, '三重県', 24),
(25, '滋賀県', 25), (26, '京都府', 26), (27, '大阪府', 27), (28, '兵庫県', 28),
(29, '奈良県', 29), (30, '和歌山県', 30), (31, '鳥取県', 31), (32, '島根県', 32),
(33, '岡山県', 33), (34, '広島県', 34), (35, '山口県', 35), (36, '徳島県', 36),
(37, '香川県', 37), (38, '愛媛県', 38), (39, '高知県', 39), (40, '福岡県', 40),
(41, '佐賀県', 41), (42, '長崎県', 42), (43, '熊本県', 43), (44, '大分県', 44),
(45, '宮崎県', 45), (46, '鹿児島県', 46), (47, '沖縄県', 47)
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO mtb_sex (id, name, sort_no) VALUES
(1, '男性', 1), (2, '女性', 2), (3, 'その他', 3)
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO mtb_customer_status (id, name, sort_no) VALUES
(1, '仮会員', 1), (2, '本会員', 2), (3, '退会', 3)
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO mtb_product_status (id, name, sort_no) VALUES
(1, '公開', 1), (2, '非公開', 2), (3, '廃止', 3)
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO mtb_order_status (id, name, sort_no) VALUES
(1, '新規受付', 1), (3, '注文取消し', 2), (4, '対応中', 3),
(5, '発送済み', 4), (6, '入金済み', 5), (7, '決済処理中', 6),
(8, '購入処理中', 7), (9, '返品', 8)
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO mtb_order_item_type (id, name, sort_no) VALUES
(1, '商品', 1), (2, '送料', 2), (3, '手数料', 3),
(4, '値引き', 4), (5, '税', 5), (6, 'ポイント', 6)
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO mtb_tax_type (id, name, sort_no) VALUES
(1, '課税', 1), (2, '不課税', 2), (3, '免税', 3)
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO mtb_tax_display_type (id, name, sort_no) VALUES
(1, '税込価格', 1), (2, '税抜価格', 2)
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO mtb_sale_type (id, name, sort_no) VALUES
(1, '通常', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Additional Master Tables for Phase 2

-- Authority (admin roles)
CREATE TABLE IF NOT EXISTS mtb_authority (
    id INT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sort_no INT NOT NULL DEFAULT 0
);

INSERT INTO mtb_authority (id, name, sort_no) VALUES
(0, 'システム管理者', 0), (1, '店舗オーナー', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Work status (for members)
CREATE TABLE IF NOT EXISTS mtb_work (
    id INT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sort_no INT NOT NULL DEFAULT 0
);

INSERT INTO mtb_work (id, name, sort_no) VALUES
(0, '非稼働', 0), (1, '稼働', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Review Status
CREATE TABLE IF NOT EXISTS mtb_review_status (
    id INT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sort_no INT NOT NULL DEFAULT 0
);

INSERT INTO mtb_review_status (id, name, sort_no) VALUES
(1, '承認待ち', 1), (2, '承認済み', 2), (3, '非承認', 3)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Page Type
CREATE TABLE IF NOT EXISTS mtb_page_type (
    id INT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sort_no INT NOT NULL DEFAULT 0
);

INSERT INTO mtb_page_type (id, name, sort_no) VALUES
(1, 'TOPページ', 1), (2, '商品一覧ページ', 2), (3, '商品詳細ページ', 3),
(4, 'MYページ', 4), (5, 'その他', 5)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Coupon Type
CREATE TABLE IF NOT EXISTS mtb_coupon_type (
    id INT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sort_no INT NOT NULL DEFAULT 0
);

INSERT INTO mtb_coupon_type (id, name, sort_no) VALUES
(1, '値引き額', 1), (2, '値引率', 2)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Auth Token (JWT token storage)
CREATE TABLE IF NOT EXISTS auth_token (
    id INT PRIMARY KEY AUTO_INCREMENT,
    token VARCHAR(500) NOT NULL UNIQUE,
    user_type VARCHAR(20) NOT NULL,
    user_id INT NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_token (token),
    INDEX idx_user (user_type, user_id),
    INDEX idx_expires (expires_at)
);

-- Member (admin users)
CREATE TABLE IF NOT EXISTS member (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    department VARCHAR(255) NULL,
    login_id VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    salt VARCHAR(255) NULL,
    authority_id INT NOT NULL DEFAULT 1,
    work_id INT NOT NULL DEFAULT 1,
    sort_no INT NOT NULL DEFAULT 0,
    two_factor_auth_key VARCHAR(255) NULL,
    two_factor_auth_enabled TINYINT(1) NOT NULL DEFAULT 0,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL,
    login_date DATETIME NULL,
    FOREIGN KEY (authority_id) REFERENCES mtb_authority(id),
    FOREIGN KEY (work_id) REFERENCES mtb_work(id),
    INDEX idx_login_id (login_id),
    INDEX idx_work (work_id)
);

-- Customer Favorite Product (wishlist)
CREATE TABLE IF NOT EXISTS customer_favorite_product (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    create_date DATETIME NOT NULL,
    FOREIGN KEY (customer_id) REFERENCES customer(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES product(id) ON DELETE CASCADE,
    UNIQUE KEY uk_customer_product (customer_id, product_id),
    INDEX idx_customer (customer_id)
);

-- Review
CREATE TABLE IF NOT EXISTS review (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    customer_id INT NULL,
    reviewer_name VARCHAR(255) NULL,
    reviewer_url VARCHAR(255) NULL,
    sex_id INT NULL,
    recommend_level INT NOT NULL DEFAULT 5,
    title VARCHAR(255) NOT NULL,
    comment TEXT NOT NULL,
    status_id INT NOT NULL DEFAULT 1,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL,
    FOREIGN KEY (product_id) REFERENCES product(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customer(id) ON DELETE SET NULL,
    FOREIGN KEY (sex_id) REFERENCES mtb_sex(id),
    FOREIGN KEY (status_id) REFERENCES mtb_review_status(id),
    INDEX idx_product (product_id),
    INDEX idx_customer (customer_id),
    INDEX idx_status (status_id)
);

-- Coupon
CREATE TABLE IF NOT EXISTS coupon (
    id INT PRIMARY KEY AUTO_INCREMENT,
    coupon_cd VARCHAR(255) NOT NULL UNIQUE,
    coupon_name VARCHAR(255) NOT NULL,
    coupon_type_id INT NOT NULL DEFAULT 1,
    discount_price DECIMAL(12, 2) NOT NULL DEFAULT 0,
    discount_rate DECIMAL(5, 2) NOT NULL DEFAULT 0,
    coupon_lower_limit DECIMAL(12, 2) NULL,
    coupon_use_time INT NULL,
    coupon_member TINYINT(1) NOT NULL DEFAULT 0,
    visible TINYINT(1) NOT NULL DEFAULT 1,
    enable_flag TINYINT(1) NOT NULL DEFAULT 1,
    available_from_date DATETIME NULL,
    available_to_date DATETIME NULL,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL,
    FOREIGN KEY (coupon_type_id) REFERENCES mtb_coupon_type(id),
    INDEX idx_coupon_cd (coupon_cd),
    INDEX idx_visible (visible),
    INDEX idx_enable (enable_flag)
);

-- Coupon Order (coupon usage tracking)
CREATE TABLE IF NOT EXISTS coupon_order (
    id INT PRIMARY KEY AUTO_INCREMENT,
    coupon_id INT NOT NULL,
    order_id INT NOT NULL,
    customer_id INT NULL,
    discount DECIMAL(12, 2) NOT NULL DEFAULT 0,
    visible TINYINT(1) NOT NULL DEFAULT 1,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL,
    FOREIGN KEY (coupon_id) REFERENCES coupon(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES `order`(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customer(id) ON DELETE SET NULL,
    INDEX idx_coupon (coupon_id),
    INDEX idx_order (order_id)
);

-- News
CREATE TABLE IF NOT EXISTS news (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    comment TEXT NULL,
    url VARCHAR(255) NULL,
    link_method TINYINT(1) NOT NULL DEFAULT 0,
    publish_date DATE NOT NULL,
    visible TINYINT(1) NOT NULL DEFAULT 1,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL,
    INDEX idx_publish (publish_date),
    INDEX idx_visible (visible)
);

-- Layout
CREATE TABLE IF NOT EXISTS layout (
    id INT PRIMARY KEY AUTO_INCREMENT,
    device_type_id INT NOT NULL DEFAULT 10,
    name VARCHAR(255) NOT NULL,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL
);

-- Block
CREATE TABLE IF NOT EXISTS block (
    id INT PRIMARY KEY AUTO_INCREMENT,
    device_type_id INT NOT NULL DEFAULT 10,
    name VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    use_controller TINYINT(1) NOT NULL DEFAULT 0,
    deletable TINYINT(1) NOT NULL DEFAULT 1,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL,
    INDEX idx_file_name (file_name)
);

-- Block Position
CREATE TABLE IF NOT EXISTS block_position (
    section INT NOT NULL,
    block_id INT NOT NULL,
    layout_id INT NOT NULL,
    block_row INT NOT NULL DEFAULT 0,
    PRIMARY KEY (section, block_id, layout_id),
    FOREIGN KEY (block_id) REFERENCES block(id) ON DELETE CASCADE,
    FOREIGN KEY (layout_id) REFERENCES layout(id) ON DELETE CASCADE
);

-- Page
CREATE TABLE IF NOT EXISTS page (
    id INT PRIMARY KEY AUTO_INCREMENT,
    master_page_id INT NULL,
    page_type_id INT NOT NULL DEFAULT 5,
    name VARCHAR(255) NOT NULL,
    url VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NULL,
    edit_type INT NOT NULL DEFAULT 0,
    author VARCHAR(255) NULL,
    description VARCHAR(255) NULL,
    keyword VARCHAR(255) NULL,
    meta_robots VARCHAR(255) NULL,
    meta_tags TEXT NULL,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL,
    FOREIGN KEY (master_page_id) REFERENCES page(id) ON DELETE SET NULL,
    FOREIGN KEY (page_type_id) REFERENCES mtb_page_type(id),
    INDEX idx_url (url)
);

-- Page Layout
CREATE TABLE IF NOT EXISTS page_layout (
    page_id INT NOT NULL,
    layout_id INT NOT NULL,
    sort_no INT NOT NULL DEFAULT 0,
    PRIMARY KEY (page_id, layout_id),
    FOREIGN KEY (page_id) REFERENCES page(id) ON DELETE CASCADE,
    FOREIGN KEY (layout_id) REFERENCES layout(id) ON DELETE CASCADE
);

-- Tax Rule
CREATE TABLE IF NOT EXISTS tax_rule (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_class_id INT NULL,
    pref_id INT NULL,
    tax_rate DECIMAL(5, 2) NOT NULL DEFAULT 10.00,
    calc_rule INT NOT NULL DEFAULT 1,
    tax_adjust DECIMAL(12, 2) NOT NULL DEFAULT 0,
    apply_date DATETIME NOT NULL,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL,
    FOREIGN KEY (product_class_id) REFERENCES product_class(id) ON DELETE CASCADE,
    FOREIGN KEY (pref_id) REFERENCES mtb_pref(id),
    INDEX idx_product_class (product_class_id),
    INDEX idx_pref (pref_id),
    INDEX idx_apply (apply_date)
);

-- Insert default tax rule (10%)
INSERT INTO tax_rule (id, product_class_id, pref_id, tax_rate, calc_rule, apply_date, create_date, update_date)
VALUES (1, NULL, NULL, 10.00, 1, '2019-10-01 00:00:00', NOW(), NOW())
ON DUPLICATE KEY UPDATE tax_rate = VALUES(tax_rate);

-- Mail Template
CREATE TABLE IF NOT EXISTS mail_template (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    mail_subject VARCHAR(255) NULL,
    mail_header TEXT NULL,
    mail_footer TEXT NULL,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL,
    INDEX idx_name (name)
);

-- Mail History
CREATE TABLE IF NOT EXISTS mail_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NULL,
    template_id INT NULL,
    send_date DATETIME NOT NULL,
    mail_subject VARCHAR(255) NOT NULL,
    mail_body TEXT NOT NULL,
    mail_from VARCHAR(255) NULL,
    mail_to VARCHAR(255) NOT NULL,
    mail_cc VARCHAR(255) NULL,
    mail_bcc VARCHAR(255) NULL,
    mail_html_body TEXT NULL,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL,
    FOREIGN KEY (order_id) REFERENCES `order`(id) ON DELETE SET NULL,
    FOREIGN KEY (template_id) REFERENCES mail_template(id) ON DELETE SET NULL,
    INDEX idx_order (order_id),
    INDEX idx_send_date (send_date)
);

-- Insert default mail templates
INSERT INTO mail_template (id, name, file_name, mail_subject, create_date, update_date) VALUES
(1, '会員登録(仮)', 'entry_confirm.twig', '【{{ BaseInfo.shop_name }}】会員登録のご確認', NOW(), NOW()),
(2, '会員登録(本)', 'entry_complete.twig', '【{{ BaseInfo.shop_name }}】会員登録が完了しました', NOW(), NOW()),
(3, '注文受付', 'order.twig', '【{{ BaseInfo.shop_name }}】ご注文ありがとうございます', NOW(), NOW()),
(4, '発送通知', 'shipping_notify.twig', '【{{ BaseInfo.shop_name }}】商品を発送しました', NOW(), NOW()),
(5, 'パスワード再発行', 'forgot_password.twig', '【{{ BaseInfo.shop_name }}】パスワード再設定のお知らせ', NOW(), NOW()),
(6, 'お問い合わせ', 'contact.twig', '【{{ BaseInfo.shop_name }}】お問い合わせを受け付けました', NOW(), NOW())
ON DUPLICATE KEY UPDATE mail_subject = VALUES(mail_subject);

-- Base Info (shop settings)
CREATE TABLE IF NOT EXISTS base_info (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_name VARCHAR(255) NULL,
    company_kana VARCHAR(255) NULL,
    shop_name VARCHAR(255) NOT NULL,
    shop_kana VARCHAR(255) NULL,
    shop_name_eng VARCHAR(255) NULL,
    postal_code VARCHAR(10) NULL,
    pref_id INT NULL,
    addr01 VARCHAR(255) NULL,
    addr02 VARCHAR(255) NULL,
    phone_number VARCHAR(20) NULL,
    business_hour VARCHAR(255) NULL,
    email01 VARCHAR(255) NULL,
    email02 VARCHAR(255) NULL,
    email03 VARCHAR(255) NULL,
    email04 VARCHAR(255) NULL,
    good_traded VARCHAR(255) NULL,
    message TEXT NULL,
    delivery_free_amount DECIMAL(12, 2) NULL,
    delivery_free_quantity INT NULL,
    option_mypage_order_status_display TINYINT(1) NOT NULL DEFAULT 1,
    option_nostock_hidden TINYINT(1) NOT NULL DEFAULT 0,
    option_favorite_product TINYINT(1) NOT NULL DEFAULT 1,
    option_product_delivery_fee TINYINT(1) NOT NULL DEFAULT 0,
    option_product_tax_rule TINYINT(1) NOT NULL DEFAULT 0,
    option_customer_activate TINYINT(1) NOT NULL DEFAULT 1,
    option_remember_me TINYINT(1) NOT NULL DEFAULT 0,
    authentication_key VARCHAR(255) NULL,
    php_path VARCHAR(255) NULL,
    create_date DATETIME NOT NULL,
    update_date DATETIME NOT NULL,
    FOREIGN KEY (pref_id) REFERENCES mtb_pref(id)
);

-- Insert default base info
INSERT INTO base_info (id, shop_name, email01, create_date, update_date)
VALUES (1, 'EC-CUBE SHOP', 'admin@example.com', NOW(), NOW())
ON DUPLICATE KEY UPDATE shop_name = VALUES(shop_name);
