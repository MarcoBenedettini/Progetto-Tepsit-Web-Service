<?php

require_once __DIR__ . '/../config.php';

class Database {

    private static ?PDO $db = null;

    public static function get(): PDO {
        if (self::$db === null) {
            $dsn      = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            self::$db = new PDO($dsn, DB_USER, DB_PASS);
            self::$db->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
            self::$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
        return self::$db;
    }
}