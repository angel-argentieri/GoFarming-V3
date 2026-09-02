<?php

require_once dirname(__DIR__) . '/MODEL/RegaModel.php';
require_once dirname(__DIR__) . '/VIEW/JsonView.php';

class RegaController {
    private $model;
    private $view;

    public function __construct($db) {
        $this->model = new RegaModel($db);
        $this->view = new JsonView();
    }

    public function regar() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['id_rega'])) {
            $this->view->send(['error' => 'ID da rega não informado.'], 400);
        }

        $this->model->marcarComoRegada($data['id_rega']);
        $this->view->send(['message' => 'Rega registrada!']);
    }
}
