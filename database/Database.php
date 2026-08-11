<?php

class Database
{
    private static ?Database $instance = null;
    private PDO $connection;

    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        } catch (PDOException $e) {
            // loggear el error, no mostrarlo
            error_log('Error de conexión BD: ' . $e->getMessage());
            die(json_encode(['error' => 'No se pudo conectar a la base de datos.']));
        }
    }

    /** Unica instancia de Database */
    public static function getInstance(): static
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /** Objeto PDO para ejecutar consultas */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /** Evita que se clone o serialice la instancia */
    private function __clone() {}
    public function __wakeup() {}
}
