<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');
if (session_status() === PHP_SESSION_NONE) session_start();
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$base = dirname(__DIR__);

// TESTE 1: Se aparecer essa mensagem, o index.php está rodando!
// die(json_encode(['error' => 'TESTE 1: INDEX ALCANÇADO']));

require_once $base . '/CONFIG/db.php';
require_once $base . '/APP/CONTROLLER/AuthController.php';

// TESTE 2: Descomente a linha abaixo para testar se o erro vem do PlantaController
// die(json_encode(['error' => 'TESTE 2: PASSOU DO AUTH']));

require_once $base . '/APP/CONTROLLER/PlantaController.php';
require_once $base . '/APP/CONTROLLER/RegaController.php';
require_once $base . '/APP/CONTROLLER/IAController.php';
require_once $base . '/APP/CONTROLLER/NotificacaoController.php';