<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

// --------------------------------------------------------
// ENTRADAS
// --------------------------------------------------------

// id da OSC (obrigatório)
$id_osc_raw = $_POST['id_osc'] ?? null;

// aceita tanto "id_projeto" (nome antigo) quanto "projeto_id" (nome novo do JS)
$id_projeto_raw = $_POST['id_projeto'] ?? ($_POST['projeto_id'] ?? null);

// categoria e subtipo
$categoria_raw = $_POST['categoria'] ?? null;
$subtipo       = $_POST['subtipo']   ?? null;

// ano de referência (opcional)
$anoRef = $_POST['ano_referencia'] ?? null;

$descricao = isset($_POST['descricao']) ? trim((string)$_POST['descricao']) : '';
if ($descricao === '') $descricao = null;

$link = isset($_POST['link']) ? trim((string)$_POST['link']) : '';
if ($link === '') $link = null;

// tipo técnico (PLANO_TRABALHO, CND, DECRETO, OUTRO etc.)
$tipo = isset($_POST['tipo']) ? strtoupper(trim((string)$_POST['tipo'])) : '';

// descrição – só vale se for OUTRO
$descricao = isset($_POST['descricao']) ? trim((string)$_POST['descricao']) : '';
if ($tipo !== 'OUTRO' || $descricao === '') {
    $descricao = null;
}

// link – só vale se for DECRETO
$link = isset($_POST['link']) ? trim((string)$_POST['link']) : '';
if ($tipo !== 'DECRETO' || $link === '') {
    $link = null;
}

// --------------------------------------------------------
// VALIDAÇÕES BÁSICAS
// --------------------------------------------------------
if (!$id_osc_raw || !ctype_digit((string)$id_osc_raw)) {
    echo json_encode(["status" => "erro", "mensagem" => "ID da OSC inválido ou não informado."]);
    exit;
}
$id_osc = (int)$id_osc_raw;

// projeto pode ser nulo
if ($id_projeto_raw === '' || $id_projeto_raw === null) {
    $id_projeto = null;
} elseif (!ctype_digit((string)$id_projeto_raw)) {
    echo json_encode(["status" => "erro", "mensagem" => "ID do projeto inválido."]);
    exit;
} else {
    $id_projeto = (int)$id_projeto_raw;
}

// normaliza categoria (trim + maiúscula)
$categoria = strtoupper(trim((string)$categoria_raw));

// conjuntos de categorias permitidas
$categoriasProjeto = ['EXECUCAO', 'ESPECIFICOS', 'CONTABIL'];      // documentos do projeto
$categoriasOsc     = ['INSTITUCIONAL', 'CERTIDAO', 'CONTABIL'];    // documentos gerais da OSC

// escolhe o conjunto certo conforme tenha projeto ou não
$categoriasPermitidas = $id_projeto ? $categoriasProjeto : $categoriasOsc;

if ($categoria === '' || !in_array($categoria, $categoriasPermitidas, true)) {
    echo json_encode(["status" => "erro", "mensagem" => "Categoria inválida ou não informada."]);
    exit;
}

if (!$subtipo) {
    echo json_encode(["status" => "erro", "mensagem" => "Subtipo do documento não informado."]);
    exit;
}

// --------------------------------------------------------
// ANO DE REFERÊNCIA
// --------------------------------------------------------
$anoRefNormalizado = null;
if ($anoRef !== null && $anoRef !== '') {
    if (!ctype_digit((string)$anoRef) || (int)$anoRef < 1900) {
        echo json_encode(["status" => "erro", "mensagem" => "Ano de referência inválido."]);
        exit;
    }
    $anoRefNormalizado = (int)$anoRef;
}

// regra específica para CONTÁBIL (Balanço / DRE)
if ($categoria === 'CONTABIL' && in_array($subtipo, ['BALANCO_PATRIMONIAL', 'DRE'], true)) {
    if ($anoRefNormalizado === null) {
        echo json_encode([
            "status"   => "erro",
            "mensagem" => "Ano de referência é obrigatório para documentos contábeis (Balanço Patrimonial / DRE)."
        ]);
        exit;
    }
}

// --------------------------------------------------------
// DEFINIÇÃO DAS PASTAS
// --------------------------------------------------------
$basePath = __DIR__ . "/assets/oscs/osc-$id_osc/";

if ($id_projeto) {
    // documento vinculado a PROJETO
    $pastaDestino = $basePath . "projetos/projeto-$id_projeto/documentos/";
} else {
    // documento geral da OSC
    $pastaDestino = $basePath . "documentos/";
}

if (!is_dir($pastaDestino)) {
    echo json_encode([
        "status"   => "erro",
        "mensagem" => "Diretório de destino não encontrado. Verifique o cadastro da OSC/projeto."
    ]);
    exit;
}

$caminhoRelativo = null;

// --------------------------------------------------------
// UPLOAD DO ARQUIVO (AGORA PODE SER OPCIONAL PARA ALGUNS TIPOS)
// --------------------------------------------------------
$temArquivo = isset($_FILES['arquivo'])
    && is_array($_FILES['arquivo'])
    && ($_FILES['arquivo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

if ($temArquivo) {
    $arquivo = $_FILES['arquivo'];

    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(["status" => "erro", "mensagem" => "Erro no upload do arquivo."]);
        exit;
    }

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

    // NORMALIZAÇÃO DO NOME DO ARQUIVO
    $base = pathinfo($nomeOriginal, PATHINFO_FILENAME);

    // tira acentos se possível
    if (function_exists('iconv')) {
        $base = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base);
    }

    $base = strtolower($base);
    $base = preg_replace('/[^a-z0-9]+/', '-', $base);
    $base = trim($base, '-');
    $base = preg_replace('/-+/', '-', $base);

    if ($base === '') $base = 'arquivo';

    $idUnico = uniqid();
    $nomeArquivo = $ext ? "{$base}-{$idUnico}.{$ext}" : "{$base}-{$idUnico}";

    $caminhoCompletoFs = $pastaDestino . $nomeArquivo;

    $caminhoRelativo = "assets/oscs/osc-$id_osc/" .
        ($id_projeto ? "projetos/projeto-$id_projeto/documentos/" : "documentos/") .
        $nomeArquivo;

    if (!move_uploaded_file($arquivo['tmp_name'], $caminhoCompletoFs)) {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao mover o arquivo."]);
        exit;
    }
} else {
    // NÃO TEM ARQUIVO -> só aceitamos em casos específicos (ex: DECRETO com link)
    $subtipoUpper = strtoupper((string)$subtipo);

    if ($subtipoUpper !== 'DECRETO') {
        echo json_encode([
            "status"   => "erro",
            "mensagem" => "Arquivo é obrigatório para este tipo de documento."
        ]);
        exit;
    }

    if ($link === null) {
        echo json_encode([
            "status"   => "erro",
            "mensagem" => "Link é obrigatório para documentos do tipo Decreto/Portaria."
        ]);
        exit;
    }

    // aqui tudo bem: documento ficará sem arquivo físico, só com o link
}

// --------------------------------------------------------
// GRAVAÇÃO NO BANCO
// --------------------------------------------------------
if ($id_projeto) {
    $sql = "INSERT INTO documento (osc_id, projeto_id, categoria, subtipo, ano_referencia, documento, link, descricao)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
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
        "iississs",
        $id_osc,
        $id_projeto,
        $categoria,
        $subtipo,
        $anoRefNormalizado,
        $caminhoRelativo,
        $link,
        $descricao
    );
} else {
    $sql = "INSERT INTO documento (osc_id, categoria, subtipo, ano_referencia, documento, link, descricao)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
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
        "ississs",
        $id_osc,
        $categoria,
        $subtipo,
        $anoRefNormalizado,
        $caminhoRelativo,
        $link,
        $descricao
    );
}

if ($stmt->execute()) {
    echo json_encode([
        "status"         => "ok",
        "mensagem"       => "Documento salvo com sucesso!",
        "id_documento"   => $stmt->insert_id,
        "osc_id"         => $id_osc,
        "projeto_id"     => $id_projeto,
        "categoria"      => $categoria,
        "subtipo"        => $subtipo,
        "ano_referencia" => $anoRefNormalizado,
        "caminho"        => $caminhoRelativo,
        "link"           => $link,
        "descricao"      => $descricao
    ]);
} else {
    echo json_encode([
        "status"   => "erro",
        "mensagem" => "Erro ao salvar no banco.",
        "erro_sql" => $stmt->error
    ]);
}
