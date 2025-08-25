<?php
echo "SQLite3 доступен: " . (extension_loaded('sqlite3') ? 'Да' : 'Нет') . "\n";
echo "PDO_SQLITE доступен: " . (extension_loaded('pdo_sqlite') ? 'Да' : 'Нет') . "\n";
echo "PDO драйверы: ";
print_r(PDO::getAvailableDrivers());
?>