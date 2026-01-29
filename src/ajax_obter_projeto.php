<?php
$TIPOS_PERMITIDOS = ['OSC_MASTER'];
$RESPOSTA_JSON = true;

require 'autenticacao.php';
require 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

function json_fail(string $msg, int $http = 400): void {
    http_response_code($http);
    echo json_encode(['success' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

$usuarioId = $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;
if (!$usuarioId) json_fail('Sessão inválida. Faça login novamente.', 401);

$stmt = $conn->prepare("SELECT osc_id FROM usuario WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();
$oscId = (int)($u['osc_id'] ?? 0);
if (!$oscId) json_fail('Este usuário não possui OSC vinculada. Contate o administrador do sistema.', 403);
$projetoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($projetoId <= 0) json_fail('Projeto inválido.');

$stmt = $conn->prepare("SELECT id, osc_id, nome, email, telefone, logo, img_descricao, descricao, depoimento, data_inicio, data_fim, status
                        FROM projeto WHERE id = ? AND osc_id = ? LIMIT 1");
$stmt->bind_param("ii", $projetoId, $oscId);
$stmt->execute();
$projeto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$projeto) json_fail('Projeto não encontrado ou não pertence à sua OSC.', 404);

// Endereços do projeto
$enderecos = [];
$stmt = $conn->prepare("
    SELECT
      e.id AS endereco_id,
      e.descricao,
      e.cep, e.cidade, e.logradouro, e.numero, e.complemento, e.bairro,
      ep.principal
    FROM endereco_projeto ep
    INNER JOIN endereco e ON e.id = ep.endereco_id
    WHERE ep.projeto_id = ?
    ORDER BY ep.principal DESC, e.id DESC
");
$stmt->bind_param("i", $projetoId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $enderecos[] = $row;
$stmt->close();

// Envolvidos do projeto (via envolvido_projeto + envolvido_osc)
$envolvidos = [];
$stmt = $conn->prepare("
    SELECT
      eo.id AS envolvido_id,
      eo.nome, eo.telefone, eo.email, eo.foto,
      ep.funcao AS funcao_projeto,
      ep.data_inicio, ep.data_fim, ep.salario, ep.ativo
    FROM envolvido_projeto ep
    INNER JOIN envolvido_osc eo ON eo.id = ep.envolvido_osc_id
    WHERE ep.projeto_id = ?
    ORDER BY eo.nome ASC
");
$stmt->bind_param("i", $projetoId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $envolvidos[] = [
        'id' => (int)$row['envolvido_id'],
        'nome' => $row['nome'],
        'telefone' => $row['telefone'],
        'email' => $row['email'],
        'foto' => $row['foto'],
        'funcao' => $row['funcao_projeto'],
        'data_inicio' => $row['data_inicio'],
        'data_fim' => $row['data_fim'],
        'salario' => $row['salario'],
        'ativo' => $row['ativo'],
    ];
}
$stmt->close();

// Documentos do projeto (documento.osc_id + documento.projeto_id)
$documentos = []; // documentos[categoria][subtipo] = doc ou array
$stmt = $conn->prepare("
    SELECT id_documento, categoria, subtipo, documento, descricao, link, ano_referencia, data_upload
    FROM documento
    WHERE osc_id = ? AND projeto_id = ?
    ORDER BY categoria ASC, subtipo ASC, ano_referencia DESC, id_documento DESC
");
$stmt->bind_param("ii", $oscId, $projetoId);
$stmt->execute();
$res = $stmt->get_result();

function is_repeatable_doc(string $categoria, string $subtipo): bool {
    // Regra conservadora:
    // - qualquer OUTRO* pode repetir
    // - CONTABIL (DRE/BALANCO_PATRIMONIAL) normalmente repete por ano
    if (preg_match('/^OUTRO/i', $subtipo)) return true;
    if ($categoria === 'CONTABIL' && ($subtipo === 'DRE' || $subtipo === 'BALANCO_PATRIMONIAL')) return true;
    return false;
}

while ($d = $res->fetch_assoc()) {
    $cat = $d['categoria'] ?? 'OUTROS';
    $sub = $d['subtipo'] ?? 'OUTRO';

    if (!isset($documentos[$cat])) $documentos[$cat] = [];

    $item = [
        'id_documento' => (int)$d['id_documento'],
        'categoria' => $cat,
        'subtipo' => $sub,
        'ano_referencia' => $d['ano_referencia'],
        'descricao' => $d['descricao'],
        'link' => $d['link'],
        'documento' => $d['documento'],
        'url' => $d['documento'],
        'nome' => $d['documento'] ? basename($d['documento']) : null,
        'data_upload' => $d['data_upload'],
    ];

    if (is_repeatable_doc($cat, $sub)) {
        if (!isset($documentos[$cat][$sub]) || !is_array($documentos[$cat][$sub])) $documentos[$cat][$sub] = [];
        $documentos[$cat][$sub][] = $item;
    } else {
        $documentos[$cat][$sub] = $item;
    }
}
$stmt->close();

echo json_encode([
    'success' => true,
    'data' => [
        'projeto' => [
            'id' => (int)$projeto['id'],
            'osc_id' => (int)$projeto['osc_id'],
        ],
        'id' => (int)$projeto['id'],
        'nome' => $projeto['nome'],
        'email' => $projeto['email'],
        'telefone' => $projeto['telefone'],
        'logo' => $projeto['logo'],
        'img_descricao' => $projeto['img_descricao'],
        'descricao' => $projeto['descricao'],
        'depoimento' => $projeto['depoimento'],
        'data_inicio' => $projeto['data_inicio'],
        'data_fim' => $projeto['data_fim'],
        'status' => $projeto['status'],
        'enderecos' => $enderecos,
        'envolvidos' => $envolvidos,
        'documentos' => $documentos,
    ]
], JSON_UNESCAPED_UNICODE);
