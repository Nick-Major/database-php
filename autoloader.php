<?php
declare(strict_types=1);

spl_autoload_register(function (string $class_name) {
    $file = str_replace('\\', DIRECTORY_SEPARATOR, $class_name) . '.php';
    
    if (file_exists($file)) {
        echo "Файл {$file} найден:" . PHP_EOL;
        require_once $file;
    } else {
        echo "Файл {$file} не найден!" . PHP_EOL;
    }

});
