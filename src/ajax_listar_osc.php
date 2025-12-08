<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'conexao.php';

try {
    $sql = "SELECT id, nome, cnpj FROM osc ORDER BY nome";
    $result = $conn->query($sql);

    $lista = [];
    while ($row = $result->fetch_assoc()) {
        $lista[] = [
            'id'   => (int)$row['id'],
            'nome' => $row['nome'],
            'cnpj' => $row['cnpj'],
        ];
    }

    echo json_encode(['success' => true, 'data' => $lista]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
