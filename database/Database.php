<?php

class Database
{
    private static ?Database $instance = null;
    private PDO $connection;

    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
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
            error_log('Error de conexión BD: ' . $e->getMessage());

            $mensaje = APP_ENV === 'local'
                ? 'No se pudo conectar a la base de datos: ' . $e->getMessage()
                : 'No se pudo conectar a la base de datos.';

            http_response_code(500);
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            die(json_encode(['ok' => false, 'error' => $mensaje], JSON_UNESCAPED_UNICODE));
        }
    }

    public static function getInstance(): static
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    private function __clone() {}

    public function __wakeup()
    {
        throw new \Exception('No se puede deserializar un Singleton.');
    }
}