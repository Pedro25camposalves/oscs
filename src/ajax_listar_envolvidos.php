<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $oscId = 0;
    if (isset($_GET['osc_id'])) {
        $oscId = (int) $_GET['osc_id'];
    } elseif (isset($_POST['osc_id'])) {
        $oscId = (int) $_POST['osc_id'];
    }

    if ($oscId <= 0) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $sql = "
        SELECT
            id,
            nome,
            telefone,
            email,
            foto,
            funcao
        FROM envolvido_osc
        WHERE osc_id = ?
        ORDER BY nome
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Erro ao preparar consulta: ' . $conn->error);
    }

    $stmt->bind_param('i', $oscId);
    $stmt->execute();
    $result = $stmt->get_result();

    $lista = [];
    while ($row = $result->fetch_assoc()) {
        $lista[] = [
            'id'       => (int)$row['id'],
            'nome'     => $row['nome'],
            'telefone' => $row['telefone'],
            'email'    => $row['email'],
            'foto'     => $row['foto'],
            'funcao'   => $row['funcao'],
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
