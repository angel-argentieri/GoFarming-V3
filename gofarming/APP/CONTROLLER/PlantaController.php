<?php

require_once dirname(__DIR__) . '/MODEL/PlantaModel.php';
require_once dirname(__DIR__) . '/MODEL/RegaModel.php';
require_once dirname(__DIR__) . '/VIEW/JsonView.php';

class PlantaController {
    private $modelPlanta;
    private $modelRega;
    private $view;

    public function __construct($db) {
        $this->modelPlanta = new PlantaModel($db);
        $this->modelRega = new RegaModel($db);
        $this->view = new JsonView();
    }

    public function listar() {
        session_start();
        if (empty($_SESSION['id_usuario'])) {
            $this->view->send(['error' => 'Não autenticado.'], 401);
            return;
        }
        $plantas = $this->modelPlanta->buscarPorUsuario($_SESSION['id_usuario']);
        $this->view->send($plantas);
    }

    public function identificar() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['imagem'])) {
            $this->view->send(['error' => 'Imagem não recebida.'], 400);
            return;
        }

        $resposta = $this->chamarPlantId($data['imagem']);

        // Se houver erro retornado pelo cURL ou pela API da Plant.id
        if (isset($resposta['error'])) {
            $this->view->send(['error' => $resposta['error']], 400);
            return;
        }

        if (!$resposta) {
            $this->view->send(['error' => 'Erro ao identificar a planta (resposta vazia).'], 500);
            return;
        }

        $sugestoes = $resposta['result']['classification']['suggestions'] ?? [];

        if (empty($sugestoes)) {
            $this->view->send(['error' => 'Nenhuma planta identificada na imagem.'], 400);
            return;
        }

        $melhor = $sugestoes[0];
        // Busca o nome comum em details.common_names, common_names direto ou usa o nome científico como fallback
        $nome = $melhor['details']['common_names'][0] 
            ?? $melhor['common_names'][0] 
            ?? $melhor['name'];

        $especie = $melhor['name'];
        $confianca = round(($melhor['probability'] ?? 0) * 100);
        $access_token = $resposta['access_token'] ?? null;

        $frequencia = $this->perguntarGemini($especie);

        $this->view->send([
            'nome' => $nome,
            'especie' => $especie,
            'confianca' => $confianca,
            'frequencia_rega' => $frequencia,
            'access_token' => $access_token
        ]);
    }

    public function salvar() {
        session_start();
        if (empty($_SESSION['id_usuario'])) {
            $this->view->send(['error' => 'Não autenticado.'], 401);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['nome']) || !isset($data['especie'])) {
            $this->view->send(['error' => 'Dados incompletos.'], 400);
            return;
        }

        $id_planta = $this->modelPlanta->criar(
            $_SESSION['id_usuario'],
            $data['nome'],
            $data['especie'],
            $data['foto_url'] ?? null,
            $data['frequencia_rega'] ?? null,
            $data['access_token'] ?? null
        );

        $frequencia_numero = $this->extrairNumeroFrequencia($data['frequencia_rega'] ?? '');
        $this->modelRega->criarProximasRegas($id_planta, $frequencia_numero);

        $this->view->send(['message' => 'Planta salva no jardim!', 'id' => $id_planta], 201);
    }

    public function deletar() {
        session_start();
        if (empty($_SESSION['id_usuario'])) {
            $this->view->send(['error' => 'Não autenticado.'], 401);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['id'])) {
            $this->view->send(['error' => 'ID não informado.'], 400);
            return;
        }

        // Confere se a planta é do usuário logado antes de deletar,
        // pra ninguém remover planta de outra pessoa só sabendo o ID.
        $planta = $this->modelPlanta->buscarPorId($data['id']);
        if (!$planta || (int) $planta['id_usuario'] !== (int) $_SESSION['id_usuario']) {
            $this->view->send(['error' => 'Planta não encontrada.'], 404);
            return;
        }

        $this->modelPlanta->deletar($data['id']);
        $this->view->send(['message' => 'Planta removida.']);
    }

    private function chamarPlantId($base64) {
        if (strpos($base64, ',') !== false) {
            $base64 = explode(',', $base64)[1];
        }

        $apiKey = defined('PLANT_ID_KEY') ? PLANT_ID_KEY : '';

        if (empty($apiKey)) {
            return ['error' => 'A constante PLANT_ID_KEY está vazia ou não foi definida nas configurações.'];
        }

        // Na v3 da API, o body JSON recebe unicamente o array de imagens
        $body = json_encode([
            'images' => ['data:image/jpeg;base64,' . $base64]
        ]);

        // Os parâmetros 'details' e 'language' são passados na URL de consulta
        $url = 'https://api.plant.id/v3/identification?details=common_names&language=pt';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Api-Key: ' . $apiKey,
            'Content-Type: application/json'
        ]);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $respostaRaw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $errorMsg = curl_error($ch);
            curl_close($ch);
            return ['error' => 'Falha de conexão cURL: ' . $errorMsg];
        }

        curl_close($ch);

        $json = json_decode($respostaRaw, true);

        if ($httpCode >= 400 || !$json) {
            $detalhe = $json['message'] ?? $respostaRaw;
            return ['error' => "Erro Plant.id (HTTP {$httpCode}): {$detalhe}"];
        }

        return $json;
    }

    private function perguntarGemini($especie) {
        if (!defined('GEMINI_KEY') || !GEMINI_KEY) {
            return 'Frequência não disponível (chave Gemini não configurada)';
        }

        $pergunta = "A planta é {$especie}. Quantas vezes por semana ela deve ser regada? Responda em uma frase curta e objetiva em português.";

        $body = json_encode([
            'contents' => [['parts' => [['text' => $pergunta]]]]
        ]);

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-goog-api-key: ' . GEMINI_KEY
        ]);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $resposta = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($resposta, true);
        return $json['candidates'][0]['content']['parts'][0]['text'] ?? 'Regar 1 a 2 vezes por semana.';
    }

    private function extrairNumeroFrequencia($texto) {
        preg_match('/\d+/', $texto, $matches);
        $numero = isset($matches[0]) ? (int)$matches[0] : 2;
        
        if ($numero < 1) $numero = 1;
        if ($numero > 7) $numero = 7;
        return $numero;
    }
}