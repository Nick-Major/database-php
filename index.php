<?php

if (file_exists('eshop.db')) {
    unlink('eshop.db');
}

try {
    $pdo = new PDO('sqlite:eshop.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка: " . $e->getMessage() . PHP_EOL);
}

$pdo->exec("
    CREATE TABLE shop (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(100),
        address TEXT
    )    
");

$pdo->exec("
    CREATE TABLE client (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        phone VARCHAR(20),
        name VARCHAR(100)
    )    
");

$pdo->exec("
    CREATE TABLE product (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name VARCHAR(100),
        price REAL,
        count INTEGER,
        shop_id INTEGER,
        FOREIGN KEY (shop_id) REFERENCES shop(id)
    )    
");

$pdo->exec("
    CREATE TABLE orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        created_at TIMESTAMP,
        shop_id INTEGER,
        client_id INTEGER,
        FOREIGN KEY (shop_id) REFERENCES shop(id),
        FOREIGN KEY (client_id) REFERENCES client(id)
    )
");

$pdo->exec("
    CREATE TABLE order_product (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER,
        order_id INTEGER,
        price REAL,
        FOREIGN KEY (product_id) REFERENCES product(id),
        FOREIGN KEY (order_id) REFERENCES orders(id)
    )    
");

$pdo->exec("INSERT INTO shop (name, address) VALUES
    ('ВкусВилл','Большой Кисловский пер., 4, стр. 1'),
    ('Пятёрочка','ул. Арбат, 24'),
    ('Перекрёсток','ул. Новый Арбат, 15'),
    ('Eurospar','Смоленская площадь, 3'),
    ('Магнит','Тверская ул., 6, стр. 1')
");

$pdo->exec("INSERT INTO client (phone, name) VALUES
    ('+79657778521', 'Валерия Проходимова'),
    ('+79221235879', 'Вячеслав Дураков'),
    ('+79991235487', 'Гульнара Сигматуллина'),
    ('+79056335812', 'Чингиз Петров'),
    ('+79115468891', 'Марат Башарович')
");

$pdo->exec("INSERT INTO product (name, price, count, shop_id) VALUES
    ('Молоко', 95.50, 30, 1),
    ('Хлеб', 60.00, 45, 2),
    ('Творог', 120.70, 33, 3),
    ('Курица', 450.00, 17, 4),
    ('Свинина', 420.30, 11, 5)
");

$pdo->exec("INSERT INTO orders (created_at, shop_id, client_id) VALUES
    ('2024-01-15 10:00:00', 1, 1),
    ('2024-01-15 11:30:00', 2, 2),
    ('2024-01-15 12:45:00', 3, 3),
    ('2024-01-15 14:20:00', 4, 4),
    ('2024-01-15 16:00:00', 5, 5)
");

$pdo->exec("INSERT INTO order_product (product_id, order_id, price) VALUES
    (1, 1, 95.50),
    (2, 2, 60.00),
    (3, 3, 120.70),
    (4, 4, 450.00),
    (5, 5, 420.30)
");

echo "База данных создана и заполнена!\n";

$tables = ['shop', 'client', 'product', 'orders', 'order_product'];
foreach ($tables as $table) {
    $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    echo "Таблица $table: $count записей\n";
}
