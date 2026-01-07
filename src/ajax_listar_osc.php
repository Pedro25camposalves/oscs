<?php
$TIPOS_PERMITIDOS = ['OSC_TECH_ADMIN', 'OSC_MASTER'];
$RESPOSTA_JSON    = true;

require 'autenticacao.php';
require_once 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $tipoUsuario = $_SESSION['tipo'] ?? null;
    $usuarioId   = $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;

    if (!$tipoUsuario) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Sessão inválida.']);
        exit;
    }

    // Query base: traz OSC + e-mail do responsável (OSC_MASTER) + banner principal
    $sqlBase = "
        SELECT
            o.id,
            o.nome,
            o.sigla,
            o.cnpj,
            (
                SELECT u.email
                FROM usuario u
                WHERE u.osc_id = o.id
                  AND u.tipo   = 'OSC_MASTER'
                ORDER BY u.data_criacao ASC, u.id ASC
                LIMIT 1
            ) AS email_responsavel,
            (
                SELECT tw.banner1
                FROM template_web tw
                WHERE tw.osc_id = o.id
                ORDER BY tw.id ASC
                LIMIT 1
            ) AS banner1
        FROM osc o
    ";

    // Se for OSC_MASTER, lista apenas a OSC vinculada a ele
    if ($tipoUsuario === 'OSC_MASTER') {
        if (!$usuarioId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Usuário não identificado na sessão.']);
            exit;
        }

        // Busca as OSC esse usuário master controla
        $stmt = $conn->prepare("
            SELECT osc_id
            FROM usuario
            WHERE id = ? AND tipo = 'OSC_MASTER'
            LIMIT 1
        ");
        $stmt->bind_param("i", $usuarioId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $oscIdVinculada = (int)($row['osc_id'] ?? 0);
        if ($oscIdVinculada <= 0) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Usuário não possui OSC vinculada.']);
            exit;
        }

        $sql = $sqlBase . "
            WHERE o.id = ?
            ORDER BY o.nome
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $oscIdVinculada);
        $stmt->execute();
        $result = $stmt->get_result();

    } else {
        // OSC_TECH_ADMIN: vê todas as OSCs
        $sql = $sqlBase . " ORDER BY o.nome";
        $result = $conn->query($sql);
    }

    $lista = [];
    while ($row = $result->fetch_assoc()) {
        $lista[] = [
            'id'               => (int)$row['id'],
            'nome'             => $row['nome']  ?? '',
            'sigla'            => $row['sigla'] ?? '',
            'cnpj'             => $row['cnpj']  ?? '',
            'emailResponsavel' => $row['email_responsavel'] ?? '',
            'banner1'          => $row['banner1'] ?? ''
        ];
    }

    echo json_encode(['success' => true, 'data' => $lista], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}