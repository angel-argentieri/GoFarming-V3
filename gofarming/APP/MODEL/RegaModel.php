<?php

class RegaModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function criarProximasRegas($id_planta, $frequencia_por_semana) {
        // Gera regas pra próximos 30 dias com base na frequência semanal
        $intervalo = round(7 / $frequencia_por_semana);
        $data = new DateTime();

        for ($i = 0; $i < 30; $i += $intervalo) {
            $data_prevista = (clone $data)->modify("+{$i} days")->format('Y-m-d');

            $stmt = $this->db->prepare("
                INSERT INTO Regas (id_planta, data_prevista)
                VALUES (:id_planta, :data_prevista)
            ");
            $stmt->bindValue(':id_planta', $id_planta);
            $stmt->bindValue(':data_prevista', $data_prevista);
            $stmt->execute();
        }
    }

    public function marcarComoRegada($id_rega) {
        $stmt = $this->db->prepare("
            UPDATE Regas SET status = 'concluida', data_regada = NOW()
            WHERE id = :id
        ");
        $stmt->bindValue(':id', $id_rega);
        return $stmt->execute();
    }

    public function buscarPendentesHoje() {
        // Usada pelo cron às 20h
        $stmt = $this->db->prepare("
            SELECT r.id, r.id_planta, p.nome AS nome_planta, u.id AS id_usuario, u.email, u.nome AS nome_usuario
            FROM Regas r
            JOIN Plantas p ON p.id = r.id_planta
            JOIN Usuarios u ON u.id = p.id_usuario
            WHERE r.data_prevista = CURDATE()
            AND r.status = 'pendente'
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}