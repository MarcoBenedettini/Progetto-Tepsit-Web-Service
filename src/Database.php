<?php

class Database {

    private static ?PDO $db = null;

    public static function get(): PDO {
        if (self::$db === null) {
            $path     = __DIR__ . '/../meal_planner.db';
            self::$db = new PDO('sqlite:' . $path);
            self::$db->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
            self::$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
        return self::$db;
    }
}
