<?php

define('PLANT_ID_KEY', '3qPbDUmVBuoXULHJyHHIX1jrN4TnxPciutckYp8oLuR4TTmhVG');

define('GEMINI_KEY', '');

class Database {
    private $host = 'localhost';
    private $dbname = 'GoFarmingBD';
    private $username = 'root';
    private $password = '12345678';
    private $pdo;

    public function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Falha na conexão com o banco.', 'detail' => $e->getMessage()]);
            exit;
        }
    }

    public function getConnection() {
        return $this->pdo;
    }
}