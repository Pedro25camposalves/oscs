<?php
$TIPOS_PERMITIDOS = ['OSC_TECH_ADMIN', 'OSC_MASTER'];
$RESPOSTA_JSON    = true;

require 'autenticacao.php';
require_once 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_SESSION)) session_start();

    $tipoUsuario = $_SESSION['tipo'] ?? null;
    $usuarioId   = $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;

    if (!$tipoUsuario) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Sessão inválida.']);
        exit;
    }

    // Query base: traz OSC + email do responsável (OSC_MASTER) + banner principal
    $sqlBase = "
        SELECT
            o.id,
            o.nome,
            o.sigla,
            o.cnpj,
            (
                SELECT u.email
                FROM usuario_osc uo
                INNER JOIN usuario u ON u.id = uo.usuario_id
                WHERE uo.osc_id = o.id
                  AND u.tipo = 'OSC_MASTER'
                ORDER BY uo.data_criacao ASC, uo.id ASC
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

    // OSC_MASTER: só as OSCs vinculadas ao usuário logado
    if ($tipoUsuario === 'OSC_MASTER') {
        if (!$usuarioId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Usuário não identificado na sessão.']);
            exit;
        }

        $sql = $sqlBase . "
            WHERE EXISTS (
                SELECT 1
                FROM usuario_osc uo2
                WHERE uo2.osc_id = o.id
                  AND uo2.usuario_id = ?
            )
            ORDER BY o.nome
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $usuarioId);
        $stmt->execute();
        $result = $stmt->get_result();

    } else {
        // OSC_TECH_ADMIN: todas
        $sql = $sqlBase . " ORDER BY o.nome";
        $result = $conn->query($sql);
    }

    $lista = [];
    while ($row = $result->fetch_assoc()) {
        $lista[] = [
            'id'               => (int)$row['id'],
            'nome'             => $row['nome'] ?? '',
            'sigla'             => $row['sigla'] ?? '',
            'cnpj'             => $row['cnpj'] ?? '',
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
