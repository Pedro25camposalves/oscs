<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'conexao.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'ID da OSC não informado.'
    ]);
    exit;
}

$id = (int) $_GET['id'];

try {
    $stmt = $conn->prepare("DELETE FROM osc WHERE id = ?");
    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        throw new Exception('Falha ao deletar OSC: ' . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        echo json_encode([
            'success' => false,
            'error'   => 'Nenhuma OSC encontrada com esse ID.'
        ]);
    } else {
        echo json_encode([
            'success' => true
        ]);
    }

    $stmt->close();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
