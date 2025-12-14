<?php
require_once 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

// --- 1) Coleta e validação básica dos campos de formulário ---

$id_osc     = $_POST['id_osc']     ?? null;
$id_projeto = $_POST['id_projeto'] ?? null;
$categoria  = $_POST['categoria']  ?? null; // INSTITUCIONAL / CERTIDAO / CONTABIL
$subtipo    = $_POST['subtipo']    ?? null; // ESTATUTO, ATA, CND_FEDERAL, BALANCO_PATRIMONIAL, DRE, etc.
$anoRef     = $_POST['ano_referencia'] ?? null; // opcional (YEAR)

// valida id_osc
if (!$id_osc || !ctype_digit((string)$id_osc)) {
    echo json_encode(["status" => "erro", "mensagem" => "ID da OSC inválido ou não informado."]);
    exit;
}

// normaliza id_projeto
if ($id_projeto === '' || $id_projeto === null) {
    $id_projeto = null;
} elseif (!ctype_digit((string)$id_projeto)) {
    echo json_encode(["status" => "erro", "mensagem" => "ID do projeto inválido."]);
    exit;
} else {
    $id_projeto = (int)$id_projeto;
}

$id_osc = (int)$id_osc;

// valida categoria
$categoriasPermitidas = ['INSTITUCIONAL', 'CERTIDAO', 'CONTABIL'];
if (!$categoria || !in_array($categoria, $categoriasPermitidas, true)) {
    echo json_encode(["status" => "erro", "mensagem" => "Categoria inválida ou não informada."]);
    exit;
}

// valida subtipo
if (!$subtipo) {
    echo json_encode(["status" => "erro", "mensagem" => "Subtipo do documento não informado."]);
    exit;
}

// trata ano de referência (obrigatório só para CONTABIL/BALANCO_PATRIMONIAL e CONTABIL/DRE)
$anoRefNormalizado = null;
if ($anoRef !== null && $anoRef !== '') {
    if (!ctype_digit((string)$anoRef) || (int)$anoRef < 1900 || (int)$anoRef > 2100) {
        echo json_encode(["status" => "erro", "mensagem" => "Ano de referência inválido."]);
        exit;
    }
    $anoRefNormalizado = (int)$anoRef;
}

if ($categoria === 'CONTABIL' && in_array($subtipo, ['BALANCO_PATRIMONIAL', 'DRE'], true)) {
    if ($anoRefNormalizado === null) {
        echo json_encode([
            "status"   => "erro",
            "mensagem" => "Ano de referência é obrigatório para documentos contábeis (Balanço Patrimonial / DRE)."
        ]);
        exit;
    }
}

// --- 2) Validação e processamento do arquivo ---

if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["status" => "erro", "mensagem" => "Erro no upload do arquivo."]);
    exit;
}

$arquivo = $_FILES['arquivo'];

// extensões permitidas (ajuste à vontade)
$extensoesPermitidas = [
    'pdf', 'doc', 'docx', 'xls', 'xlsx',
    'odt', 'ods', 'csv', 'txt', 'rtf'
];

$nomeOriginal = $arquivo['name'];
$ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

if (!in_array($ext, $extensoesPermitidas, true)) {
    echo json_encode([
        "status"   => "erro",
        "mensagem" => "Tipo de arquivo não permitido. Envie um dos tipos: " . implode(', ', $extensoesPermitidas)
    ]);
    exit;
}

// monta diretório destino (segue sua lógica de pastas)
$basePath = __DIR__ . "/assets/oscs/osc-$id_osc/";

if ($id_projeto) {
    $pastaDestino = $basePath . "projetos/projeto-$id_projeto/documentos/";
} else {
    $pastaDestino = $basePath . "documentos/";
}

if (!is_dir($pastaDestino)) {
    echo json_encode([
        "status"   => "erro",
        "mensagem" => "Diretório de destino não encontrado. Verifique o cadastro da OSC/projeto."
    ]);
    exit;
}

// gera nome único pro arquivo
$nomeArquivo = uniqid() . "-" . preg_replace('/[^A-Za-z0-9._-]/', '_', $nomeOriginal);
$caminhoCompletoFs = $pastaDestino . $nomeArquivo;

// para salvar no banco, é melhor armazenar o caminho relativo (sem __DIR__)
$caminhoRelativo = "assets/oscs/osc-$id_osc/" .
    ($id_projeto ? "projetos/projeto-$id_projeto/documentos/" : "documentos/") .
    $nomeArquivo;

if (!move_uploaded_file($arquivo['tmp_name'], $caminhoCompletoFs)) {
    echo json_encode(["status" => "erro", "mensagem" => "Erro ao mover o arquivo."]);
    exit;
}

// --- 3) Inserção no banco (tabela documento) ---

if ($id_projeto) {
    $sql = "INSERT INTO documento (osc_id, projeto_id, categoria, subtipo, ano_referencia, documento)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode([
            "status"   => "erro",
            "mensagem" => "Erro ao preparar SQL (projeto).",
            "erro_sql" => $conn->error
        ]);
        exit;
    }

    $stmt->bind_param(
        "iissis",
        $id_osc,
        $id_projeto,
        $categoria,
        $subtipo,
        $anoRefNormalizado,
        $caminhoRelativo
    );
} else {
    $sql = "INSERT INTO documento (osc_id, categoria, subtipo, ano_referencia, documento)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode([
            "status"   => "erro",
            "mensagem" => "Erro ao preparar SQL (sem projeto).",
            "erro_sql" => $conn->error
        ]);
        exit;
    }

    $stmt->bind_param(
        "issis",
        $id_osc,
        $categoria,
        $subtipo,
        $anoRefNormalizado,
        $caminhoRelativo
    );
}


if ($stmt->execute()) {
    echo json_encode([
        "status"        => "ok",
        "mensagem"      => "Arquivo enviado com sucesso!",
        "id_documento"  => $stmt->insert_id,
        "osc_id"        => $id_osc,
        "projeto_id"    => $id_projeto,
        "categoria"     => $categoria,
        "subtipo"       => $subtipo,
        "ano_referencia"=> $anoRefNormalizado,
        "caminho"       => $caminhoRelativo
    ]);
} else {
    echo json_encode([
        "status"   => "erro",
        "mensagem" => "Erro ao salvar no banco.",
        "erro_sql" => $stmt->error
    ]);
}
