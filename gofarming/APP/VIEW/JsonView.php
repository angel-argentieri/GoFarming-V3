<?php

class JsonView {
    public function send($data, $status = 200) {
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
}
