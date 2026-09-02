<?php

require_once dirname(__DIR__) . '/MODEL/UsuarioModel.php';
require_once dirname(__DIR__) . '/VIEW/JsonView.php';

class AuthController {
    private $model;
    private $view;

    public function __construct($db) {
        $this->model = new UsuarioModel($db);
        $this->view = new JsonView();
    }

    public function login() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            $this->view->send(['error' => 'Preencha email e senha.'], 400);
        }

        $usuario = $this->model->buscarPorEmail($data['email']);

        if (!$usuario || !password_verify($data['password'], $usuario['senha'])) {
            $this->view->send(['error' => 'Email ou senha incorretos.'], 401);
        }

        session_start();
        $_SESSION['id_usuario'] = $usuario['id'];
        $_SESSION['nome'] = $usuario['nome'];

        $this->view->send(['message' => 'Login realizado.', 'nome' => $usuario['nome']]);
    }

    public function cadastro() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['nome']) || !isset($data['email']) || !isset($data['password'])) {
            $this->view->send(['error' => 'Preencha todos os campos.'], 400);
        }

        $existe = $this->model->buscarPorEmail($data['email']);

        if ($existe) {
            $this->view->send(['error' => 'Email já cadastrado.'], 409);
        }

        $this->model->criar($data['nome'], $data['email'], $data['password']);
        $this->view->send(['message' => 'Cadastro realizado.'], 201);
    }

    public function logout() {
        session_start();
        session_destroy();
        $this->view->send(['message' => 'Logout realizado.']);
    }
}
