<?php

require_once __DIR__ . '/../MODEL/PlantaModel.php';
require_once __DIR__ . '/../VIEW/JsonView.php';

class IAController {
    private $modelPlanta;
    private $view;
    private $apiKey;

    public function __construct($db) {
        $this->apiKey = defined('GEMINI_KEY') ? trim(GEMINI_KEY) : '';
        $this->modelPlanta = new PlantaModel($db);
        $this->view = new JsonView();
    }

    public function chat() {
        // TESTE DO CARIMBO: Força o PHP a responder na hora para confirmar a execução deste arquivo
        die(json_encode(['error' => 'CHEGOU NO IACONTROLLER CERTO']));

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['id_planta']) || empty($data['pergunta'])) {
                $this->view->send(['error' => 'Dados incompletos.'], 400);
                return;
            }

            $planta = $this->modelPlanta->buscarPorId($data['id_planta']);

            if (!$planta) {
                $this->view->send(['error' => 'Planta não encontrada.'], 404);
                return;
            }

            $nome = $planta['nome'] ?? 'Planta';
            $especie = $planta['especie'] ?? '';

            $perguntaCompleta = "Você é um especialista em jardinagem. "
                . "A planta é {$nome} ({$especie}). "
                . "Responda em português de forma direta e curta: {$data['pergunta']}";

            $body = [
                'contents' => [
                    ['parts' => [['text' => $perguntaCompleta]]]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 500
                ]
            ];

            $resultado = $this->executarCurlGemini($body);

            if (isset($resultado['error'])) {
                $this->view->send(['error' => $resultado['error']]);
                return;
            }

            $this->view->send(['resposta' => $resultado['texto']]);

        } catch (Throwable $e) {
            $this->view->send(['error' => 'Erro interno (PHP/MySQL): ' . $e->getMessage()]);
        }
    }

    public function obterCuidados() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['nome_planta'])) {
            $this->view->send(['error' => 'Nome da planta não informado.'], 400);
            return;
        }

        $prompt = "Responda com um JSON: {\"frequencia_rega\": \"A cada X dias\"}. Planta: {$data['nome_planta']}";

        $body = [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ]
        ];

        $resultado = $this->executarCurlGemini($body);

        if (isset($resultado['texto'])) {
            $jsonLimpo = trim(str_replace(['```json', '```'], '', $resultado['texto']));
            $dadosCuidados = json_decode($jsonLimpo, true);

            if (!empty($dadosCuidados['frequencia_rega'])) {
                $this->view->send($dadosCuidados);
                return;
            }
        }

        $this->view->send(['frequencia_rega' => 'A cada 2 a 3 dias']);
    }

    private function executarCurlGemini($body) {
        if (empty($this->apiKey) || strpos($this->apiKey, 'COLOQUE') === 0) {
            return ['error' => 'GEMINI_KEY não configurada em CONFIG/db.php. Gere uma chave em https://aistudio.google.com/apikey.'];
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . urlencode($this->apiKey);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $resposta = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['error' => 'Erro de conexão cURL: ' . $curlError];
        }

        $json = json_decode($resposta, true);

        if (!is_array($json)) {
            return ['error' => 'Resposta inválida da API.'];
        }

        if (isset($json['error']['message'])) {
            return ['error' => 'Erro Google API: ' . $json['error']['message']];
        }

        if (empty($json['candidates'])) {
            if (!empty($json['promptFeedback']['blockReason'])) {
                return ['error' => 'Resposta bloqueada por segurança.'];
            }
            return ['error' => 'Sem resposta do modelo.'];
        }

        $finishReason = $json['candidates'][0]['finishReason'] ?? '';

        if ($finishReason === 'SAFETY') {
            return ['error' => 'Resposta bloqueada por segurança.'];
        }

        $texto = '';
        $parts = $json['candidates'][0]['content']['parts'] ?? [];
        foreach ($parts as $part) {
            if (isset($part['text'])) {
                $texto .= $part['text'];
            }
        }

        if (empty(trim($texto))) {
            return ['error' => 'Sem resposta de texto do modelo.'];
        }

        return ['texto' => trim($texto)];
    }
}