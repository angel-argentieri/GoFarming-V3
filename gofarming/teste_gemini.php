<?php
require_once __DIR__ . '/CONFIG/db.php';

$key = defined('GEMINI_KEY') ? trim(GEMINI_KEY) : '';

echo "<h2>Diagnóstico Técnico Gemini API</h2>";
echo "<p><b>Chave configurada:</b> " . (empty($key) ? "<span style='color:red'>VAZIA</span>" : htmlspecialchars(substr($key, 0, 12) . '...')) . "</p>";

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . urlencode($key);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode([
        'contents' => [['parts' => [['text' => 'Olá']]]]
    ]),
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
]);

$resposta = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$erroNo   = curl_errno($ch);
$erroMsg  = curl_error($ch);
curl_close($ch);

echo "<p><b>Código HTTP retornado:</b> " . ($httpCode ? $httpCode : "0 (Sem resposta da rede)") . "</p>";

if ($erroNo !== 0) {
    echo "<p style='color:red'><b>Erro de Conexão cURL #{$erroNo}:</b> {$erroMsg}</p>";
} else {
    echo "<p><b>Resposta Bruta da API:</b></p>";
    echo "<pre style='background:#f4f4f4; padding:10px; border:1px solid #ccc;'>" . htmlspecialchars($resposta) . "</pre>";
}