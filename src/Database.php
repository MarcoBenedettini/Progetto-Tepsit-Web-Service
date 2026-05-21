<?php

require_once __DIR__ . '/../config.php';

class Database {
    // Proprietà statica che conserva l'unica istanza della connessione PDO
    private static $connection = null;

    // ottenere la connessione al database
    public static function get() {

        // Se la connessione non è ancora stata creata, la inizializza
        if (self::$connection === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

            // Crea l'oggetto PDO per connettersi al database
            // DB_USER e DB_PASS sono definiti in config.php
            self::$connection = new PDO($dsn, DB_USER, DB_PASS);

            // Imposta la modalità di errore: lancia eccezioni in caso di problemi
            self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
        return self::$connection;
    }
}