<?php
/**
 * Database Singleton Helper
 */

require_once __DIR__ . '/../config/database.php';

class Database {
    public static function pdo(): PDO {
        return DatabaseManager::getConnection();
    }

    public static function query(string $sql, array $params = []): PDOStatement {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchAll(string $sql, array $params = []): array {
        return self::query($sql, $params)->fetchAll();
    }

    public static function fetchOne(string $sql, array $params = []): ?array {
        $res = self::query($sql, $params)->fetch();
        return $res ?: null;
    }

    public static function insert(string $table, array $data): int {
        $keys = array_keys($data);
        $fields = implode('`, `', $keys);
        $placeholders = implode(', ', array_fill(0, count($keys), '?'));
        $sql = "INSERT INTO `{$table}` (`{$fields}`) VALUES ({$placeholders})";
        self::query($sql, array_values($data));
        return (int)self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int {
        $fields = [];
        $values = [];
        foreach ($data as $key => $val) {
            $fields[] = "`{$key}` = ?";
            $values[] = $val;
        }
        $sql = "UPDATE `{$table}` SET " . implode(', ', $fields) . " WHERE {$where}";
        $stmt = self::query($sql, array_merge($values, $whereParams));
        return $stmt->rowCount();
    }

    public static function delete(string $table, string $where, array $whereParams = []): int {
        $sql = "DELETE FROM `{$table}` WHERE {$where}";
        $stmt = self::query($sql, $whereParams);
        return $stmt->rowCount();
    }

    public static function logActivity(?int $userId, string $action, string $module, ?string $details = null): void {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            self::insert('activity_logs', [
                'user_id' => $userId,
                'action' => $action,
                'module' => $module,
                'details' => $details,
                'ip_address' => $ip
            ]);
        } catch (Exception $e) {
            // Silently skip logging failure to avoid breaking transactions
        }
    }
}
