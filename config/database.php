<?php
/**
 * Database Connection & Initialization Manager
 */

require_once __DIR__ . '/config.php';

class DatabaseManager {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                // Try connecting to the specific database
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // If database doesn't exist, connect to MySQL server and trigger migration
                if ($e->getCode() == 1049 || str_contains($e->getMessage(), 'Unknown database')) {
                    self::initializeDatabase();
                    return self::getConnection();
                }
                throw $e;
            }
        }
        return self::$instance;
    }

    public static function initializeDatabase(): void {
        try {
            $rootDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
            $rootPdo = new PDO($rootDsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            
            // Now run migration schema
            require_once __DIR__ . '/migrate.php';
            runMigration();
        } catch (PDOException $ex) {
            die("Database initialization failed: " . $ex->getMessage());
        }
    }
}
