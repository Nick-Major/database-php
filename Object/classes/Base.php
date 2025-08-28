<?php
declare(strict_types=1);

namespace Object\classes;

use Object\interfaces\DatabaseWrapper;

class Base implements DatabaseWrapper
{
    public object $pdo;
    public string $table;
    public array $allowedColumns;

    public function __construct(object $pdo, string $table, array $allowedColumns)
    {
        $this->pdo = $pdo;
        $this->table = $table;
        $this->allowedColumns = $allowedColumns;
    }

    public function insert(array $tableColumns, array $values): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'inserted_id' => null,
            'affected_rows' => 0,
            'data' => null
        ];

        try {
            // Проверяем наличие столбцов
            foreach($tableColumns as $column) {
                if (!in_array($column, $this->allowedColumns)) {
                    throw new \InvalidArgumentException("Столбец '{$column}' не существует в таблице '{$this->table}'!");
                }
            }

            // Проверяем совпадение размеров массивов
            if (count($tableColumns) !== count($values)) {
                throw new \InvalidArgumentException("Массивы должны быть одинаковой длины!");
            }

            // Создаем ассоциативный массив
            $data = array_combine($tableColumns, $values);

            // Формируем и выполняем запрос
            $columns = implode(', ', $tableColumns);
            $placeholders = ':' . implode(', :', $tableColumns);

            $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
            $stmt = $this->pdo->prepare($sql);

            $queryExecuted = $stmt->execute($data);

            if ($queryExecuted) {
                $insertedId = (int)$this->pdo->lastInsertId();
                $insertedRow = $this->findRecord($insertedId);

                $result = [
                    'success' => true,
                    'message' => 'Запись успешно добавлена',
                    'inserted_id' => $insertedId,
                    'affected_rows' => $stmt->rowCount(),
                    'data' => $insertedRow['data']
                ];

                echo "ДОБАВЛЕНА ЗАПИСЬ:\n";
                echo "Таблица: {$this->table}\n";
                echo "ID: {$insertedId}\n";
                echo "Данные: " . json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                echo "\n";
            } else {
                $result['message'] = 'Не удалось добавить запись';
                echo "Не удалось добавить запись в таблицу {$this->table}\n";
            }
        } catch (\PDOException | \InvalidArgumentException $e) {
            error_log("Ошибка: " . $e->getMessage());
            $result = [
                'success' => false,
                'message' => 'Ошибка при добавлении записи',
                'error' => $e->getMessage()
            ];
            echo "Ошибка при добавлении: " . $e->getMessage() . "\n";
        }
        
        return $result;
    }

    public function update(int $id, array $values): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'affected_rows' => 0,
            'id' => $id,
            'data' => null
        ];   

        try {
            // 1. Проверяем существование ID
            $stmt = $this->pdo->query("SELECT id FROM {$this->table}");
            $existingIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            if (!in_array($id, $existingIds)) {
                throw new \InvalidArgumentException("Идентификатора '{$id}' не существует в таблице '{$this->table}'!");
            }

            // 2. Формируем SET часть запроса
            $setParts = [];
            $params = [':id' => $id];

            foreach($values as $column => $value) {
                if (!in_array($column, $this->allowedColumns)) {
                    throw new \InvalidArgumentException("Столбец '{$column}' не существует в таблице '{$this->table}'!");
                } else {
                    $setParts[] = "{$column} = :{$column}";
                    $params[":{$column}"] = $value;
                }
            }

            // 3. Подготавливаем SQL запрос
            $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts) . " WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);

            // 4. Выполняем запрос с параметрами
            $queryExecuted = $stmt->execute($params);

            // 5. Возвращаем результат
            if ($queryExecuted) {
                $updatedRow = $this->findRecord($id);

                $result = [
                    'success' => true,
                    'message' => 'Запись успешно обновлена',
                    'affected_rows' => $stmt->rowCount(),
                    'id' => $id,
                    'data' => $updatedRow['data']
                ];

                echo "ОБНОВЛЕНА ЗАПИСЬ:\n";
                echo "Таблица: {$this->table}\n";
                echo "ID: {$id}\n";
                echo "Данные: " . json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                echo "\n";

            } else {
                $result['message'] = 'Не удалось обновить запись';
                echo "Не удалось обновить запись в таблице {$this->table}\n";
            }

        } catch (\PDOException | \InvalidArgumentException $e) {
            error_log("Ошибка при обновлении: " . $e->getMessage());

            $result = [
                'success' => false,
                'message' => 'Ошибка при обновлении записи',
                'error' => $e->getMessage()
            ];
            echo "Ошибка при обновлении: " . $e->getMessage() . "\n";
        }

        return $result;
    }

    private function findRecord(int $id): array
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);

            $data = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($data) {
                return [
                    'success' => true,
                    'message' => 'Запись найдена',
                    'data' => $data
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "Запись с ID {$id} не найдена"
                ];
            }

        } catch (\PDOException $e) {
            error_log("Ошибка поиска записи ID {$id}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ошибка базы данных при поиске',
                'error' => $e->getMessage()
            ];
        }
    }

    public function find(int $id): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'data' => null
        ];

        try {
            $findResult = $this->findRecord($id);

            if ($findResult['success'] === true) {
                    $result = [
                    'success' => true,
                    'message' => 'Запись найдена',
                    'data' => $findResult['data']
                ];

                echo "НАЙДЕНА ЗАПИСЬ:\n";
                echo "Таблица: {$this->table}\n";
                echo "ID: {$id}\n";
                echo "Данные: " . json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                echo "\n";
            } else {
                $result['message'] = $findResult['message'];
                echo "Запись с ID {$id} не найдена в таблице {$this->table}\n";
            }

        } catch (\PDOException $e) {
            error_log("Ошибка поиска: " . $e->getMessage());

            $result = [
                'success' => false,
                'message' => 'Ошибка при поиске записи',
                'error' => $e->getMessage()
            ];
            echo "Ошибка поиска: " . $e->getMessage() . "\n";
        }

        return $result;
    }

    public function delete(int $id): bool
    {
        try {
            // Валидация ID
            if ($id <= 0) {
                throw new \InvalidArgumentException("ID должен быть положительным числом");
            }

            $recordExists = $this->findRecord($id);
            if ($recordExists['success'] === false) {
                echo "Запись с ID {$id} не существует\n";
                return false;
            }

            $sql = "DELETE FROM {$this->table} WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();
            
            // Проверяем, была ли удалена хотя бы одна запись
            $wasDeleted = $stmt->rowCount() > 0;

            if ($wasDeleted) {
                echo "УДАЛЕНА ЗАПИСЬ:\n";
                echo "Таблица: {$this->table}\n";
                echo "ID: {$id}\n";
                echo "Данные: " . json_encode($recordExists['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE . "\n");
                echo "\n";
            } else {
                echo "Запись с ID {$id} не была удалена!";
            }

            return $wasDeleted;
            
        } catch (\PDOException $e) {
            error_log("Ошибка удаления ID {$id}: " . $e->getMessage());
            return false;
        }
    }
}
