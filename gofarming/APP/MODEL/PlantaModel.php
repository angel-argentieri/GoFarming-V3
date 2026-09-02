<?php

class PlantaModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function buscarPorUsuario($id_usuario) {
        $stmt = $this->db->prepare("
            SELECT p.*, 
                r.status AS rega_hoje,
                r.id AS rega_id
            FROM Plantas p
            LEFT JOIN Regas r ON r.id_planta = p.id 
                AND r.data_prevista = CURDATE()
            WHERE p.id_usuario = :id_usuario
            ORDER BY p.criada_em DESC
        ");
        $stmt->bindValue(':id_usuario', $id_usuario);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function buscarPorId($id) {
        $stmt = $this->db->prepare("SELECT * FROM Plantas WHERE id = :id");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function criar($id_usuario, $nome, $especie, $foto_url, $frequencia_rega, $access_token) {
        $stmt = $this->db->prepare("
            INSERT INTO Plantas (id_usuario, nome, especie, foto_url, frequencia_rega, access_token_plantid)
            VALUES (:id_usuario, :nome, :especie, :foto_url, :frequencia_rega, :access_token)
        ");
        $stmt->bindValue(':id_usuario', $id_usuario);
        $stmt->bindValue(':nome', $nome);
        $stmt->bindValue(':especie', $especie);
        $stmt->bindValue(':foto_url', $foto_url);
        $stmt->bindValue(':frequencia_rega', $frequencia_rega);
        $stmt->bindValue(':access_token', $access_token);
        $stmt->execute();
        return $this->db->lastInsertId();
    }

    public function deletar($id) {
        $stmt = $this->db->prepare("DELETE FROM Plantas WHERE id = :id");
        $stmt->bindValue(':id', $id);
        return $stmt->execute();
    }
}
