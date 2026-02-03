<?php
$TIPOS_PERMITIDOS = ['OSC_TECH_ADMIN', 'OSC_MASTER'];
$RESPOSTA_JSON    = true;

require 'autenticacao.php';
require 'conexao.php';

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

// tipo técnico (PLANO_TRABALHO, CND, DECRETO, OUTRO, OUTRO_INSTITUCIONAL etc.)
$tipo = isset($_POST['tipo']) ? strtoupper(trim((string)$_POST['tipo'])) : '';

// campos textuais
$descricao = isset($_POST['descricao']) ? trim((string)$_POST['descricao']) : '';
$link      = isset($_POST['link'])      ? trim((string)$_POST['link'])      : '';

// normalização do subtipo para regras de CONTÁBIL e, se quiser, OUTRO/DECRETO também
$subtipoUpper = strtoupper((string)$subtipo);

// flags de regra:
$isOutro   = (strpos($tipo, 'OUTRO') === 0 || strpos($subtipoUpper, 'OUTRO') === 0);
$isDecreto = (strpos($tipo, 'DECRETO') === 0 || strpos($subtipoUpper, 'DECRETO') === 0);

// aplica regras só UMA vez:
// descrição só vale para OUTRO / OUTRO_INSTITUCIONAL (e afins)
if (!$isOutro || $descricao === '') {
    $descricao = null;
}

// link só vale para DECRETO (ou derivados)
if (!$isDecreto || $link === '') {
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

// --------------------------------------------------------
// ATUALIZA METADADOS
// --------------------------------------------------------
$id_documento_raw = $_POST['id_documento'] ?? null;
if ($id_documento_raw !== null && $id_documento_raw !== '') {

    if (!ctype_digit((string)$id_documento_raw)) {
        echo json_encode(["status" => "erro", "mensagem" => "ID do documento inválido."]);
        exit;
    }
    $id_documento = (int)$id_documento_raw;

    $temArquivo = isset($_FILES['arquivo'])
        && is_array($_FILES['arquivo'])
        && ($_FILES['arquivo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    if ($temArquivo) {
        echo json_encode([
            "status"   => "erro",
            "mensagem" => "Para substituir o arquivo, utilize o fluxo padrão de upload/substituição."
        ]);
        exit;
    }

    $stmt = $conn->prepare("SELECT categoria, subtipo FROM documento WHERE id_documento = ? AND osc_id = ? LIMIT 1");
    if (!$stmt) {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao preparar consulta do documento.", "erro_sql" => $conn->error]);
        exit;
    }
    $stmt->bind_param("ii", $id_documento, $id_osc);
    $stmt->execute();
    $docDb = $stmt->get_result()->fetch_assoc();
    if (!$docDb) {
        echo json_encode(["status" => "erro", "mensagem" => "Documento não encontrado para esta OSC."]);
        exit;
    }

    $categoriaDb = strtoupper((string)$docDb['categoria']);
    $subtipoDb   = strtoupper((string)$docDb['subtipo']);

    $descricao_in = array_key_exists('descricao', $_POST) ? trim((string)$_POST['descricao']) : null;
    $ano_in_raw   = array_key_exists('ano_referencia', $_POST) ? trim((string)$_POST['ano_referencia']) : null;

    // ano
    $anoVaiAtualizar = false;
    $anoRefNormalizado = null;
    if ($ano_in_raw !== null) {
        $ano_in_raw = trim($ano_in_raw);
        if ($ano_in_raw === '') {
            echo json_encode(["status" => "erro", "mensagem" => "Ano de referência não pode ficar vazio."]);
            exit;
        }
        if (!ctype_digit((string)$ano_in_raw) || (int)$ano_in_raw < 1900) {
            echo json_encode(["status" => "erro", "mensagem" => "Ano de referência inválido."]);
            exit;
        }
        $anoRefNormalizado = (int)$ano_in_raw;
        $anoVaiAtualizar = true;
    }

    // regra:
    if ($categoriaDb === 'CONTABIL' && in_array($subtipoDb, ['BALANCO_PATRIMONIAL', 'DRE'], true)) {
        if (!$anoVaiAtualizar) {
            echo json_encode([
                "status"   => "erro",
                "mensagem" => "Ano de referência é obrigatório para documentos contábeis (Balanço Patrimonial / DRE)."
            ]);
            exit;
        }
    }

    // descrição:
    $descVaiAtualizar = false;
    $descricaoFinal = null;
    if ($descricao_in !== null) {
        $descricao_in = trim($descricao_in);
        if ($descricao_in !== '' && strpos($subtipoDb, 'OUTRO') === 0) {
            $descricaoFinal = $descricao_in;
            $descVaiAtualizar = true;
        }
    }

    if (!$anoVaiAtualizar && !$descVaiAtualizar) {
        echo json_encode(["status" => "erro", "mensagem" => "Nenhuma alteração de metadados foi enviada."]);
        exit;
    }

    $sets = [];
    $types = "";
    $vals = [];

    if ($descVaiAtualizar) {
        $sets[] = "descricao = ?";
        $types .= "s";
        $vals[] = $descricaoFinal;
    }
    if ($anoVaiAtualizar) {
        $sets[] = "ano_referencia = ?";
        $types .= "i";
        $vals[] = $anoRefNormalizado;
    }

    $sql = "UPDATE documento SET " . implode(", ", $sets) . " WHERE id_documento = ? AND osc_id = ? LIMIT 1";
    $types .= "ii";
    $vals[] = $id_documento;
    $vals[] = $id_osc;

    $stmtUp = $conn->prepare($sql);
    if (!$stmtUp) {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao preparar UPDATE do documento.", "erro_sql" => $conn->error]);
        exit;
    }
    $stmtUp->bind_param($types, ...$vals);

    if (!$stmtUp->execute()) {
        echo json_encode(["status" => "erro", "mensagem" => "Erro ao atualizar metadados do documento.", "erro_sql" => $stmtUp->error]);
        exit;
    }

    echo json_encode([
        "status"         => "ok",
        "mensagem"       => "Metadados do documento atualizados com sucesso!",
        "id_documento"   => $id_documento,
        "osc_id"         => $id_osc,
        "categoria"      => $categoriaDb,
        "subtipo"        => $subtipoDb,
        "ano_referencia" => $anoVaiAtualizar ? $anoRefNormalizado : null,
        "descricao"      => $descVaiAtualizar ? $descricaoFinal : null
    ]);
    exit;
}

// ----------------------------------------------------
// MODO UPDATE
// ----------------------------------------------------
$id_documento_raw = $_POST['id_documento'] ?? null;
if ($id_documento_raw !== null && $id_documento_raw !== '') {
    if (!ctype_digit((string)$id_documento_raw)) {
        echo json_encode(["status" => "erro", "msg" => "ID do documento inválido."]);
        exit;
    }
    $id_documento = (int)$id_documento_raw;

    // Carrega o documento existente
    $stmtDoc = $conn->prepare("SELECT id_documento, osc_id, projeto_id, categoria, subtipo, documento, link, descricao, ano_referencia
                               FROM documento
                               WHERE id_documento = ? LIMIT 1");
    $stmtDoc->bind_param("i", $id_documento);
    $stmtDoc->execute();
    $docDb = $stmtDoc->get_result()->fetch_assoc();

    if (!$docDb) {
        echo json_encode(["status" => "erro", "msg" => "Documento não encontrado."]);
        exit;
    }
    if ((int)$docDb['osc_id'] !== $id_osc) {
        echo json_encode(["status" => "erro", "msg" => "Documento não pertence a esta OSC."]);
        exit;
    }

    $catDb = strtoupper((string)($docDb['categoria'] ?? ''));
    $subDb = strtoupper((string)($docDb['subtipo'] ?? ''));

    $isOutro = (strpos($subDb, 'OUTRO') === 0);
    $isAnoRef = ($catDb === 'CONTABIL' && ($subDb === 'BALANCO_PATRIMONIAL' || $subDb === 'DRE'));

    // Atualiza somente os campos recebidos; os demais permanecem
    $newDescricao = $docDb['descricao'];
    if (array_key_exists('descricao', $_POST)) {
        $val = trim((string)($_POST['descricao'] ?? ''));
        if ($isOutro) {
            if ($val === '') {
                echo json_encode(["status" => "erro", "msg" => "Informe a descrição do documento."]);
                exit;
            }
            $newDescricao = $val;
        } else {
            $newDescricao = null;
        }
    }

    $newAno = $docDb['ano_referencia'];
    if (array_key_exists('ano_referencia', $_POST)) {
        $val = trim((string)($_POST['ano_referencia'] ?? ''));
        if ($val === '') {
            $newAno = null;
        } else {
            if (!preg_match('/^\d{4}$/', $val)) {
                echo json_encode(["status" => "erro", "msg" => "Ano de referência inválido. Use 4 dígitos (ex.: 2025)."]);
                exit;
            }
            $newAno = $val;
        }
        if ($isAnoRef && !$newAno) {
            echo json_encode(["status" => "erro", "msg" => "Informe o ano de referência."]);
            exit;
        }
    }

    $newLink = $docDb['link'];
    if (array_key_exists('link', $_POST)) {
        $val = trim((string)($_POST['link'] ?? ''));
        $newLink = ($val !== '') ? $val : null;
    }

    // Arquivo é opcional no UPDATE
    $temArquivo = (isset($_FILES['arquivo']) && is_array($_FILES['arquivo']) &&
                  (int)($_FILES['arquivo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE);

    $novoRel = $docDb['documento'];

    if ($temArquivo) {
        $arquivo = $_FILES['arquivo'];

        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(["status" => "erro", "msg" => "Falha no upload do arquivo."]);
            exit;
        }

        // Diretório (mesma lógica do INSERT, mas usando o projeto do próprio documento)
        $basePath = __DIR__ . "/assets/oscs/osc-" . $id_osc;
        $projIdDb = $docDb['projeto_id'] ?? null;
        if ($projIdDb !== null && $projIdDb !== '') {
            $basePath .= "/projetos/projeto-" . (int)$projIdDb;
        }

        $dirDocs = $basePath . "/documentos";
        if (!is_dir($dirDocs)) {
            mkdir($dirDocs, 0777, true);
        }

        $novoNome = preg_replace('/[^a-zA-Z0-9\.\-_]/', '-', $arquivo['name']);
        $novoNome = strtolower($novoNome);
        $novoNome = pathinfo($novoNome, PATHINFO_FILENAME) . "-" . uniqid() . "." . $ext;

        $destAbs = $dirDocs . "/" . $novoNome;

        if (!move_uploaded_file($arquivo['tmp_name'], $destAbs)) {
            echo json_encode(["status" => "erro", "msg" => "Não foi possível salvar o arquivo enviado."]);
            exit;
        }

        $novoRel = str_replace(__DIR__ . "/", "", $destAbs);

        // opcional: tenta remover o arquivo antigo (se existir)
        $oldRel = $docDb['documento'] ?? '';
        if ($oldRel) {
            $oldAbs = __DIR__ . "/" . ltrim($oldRel, '/');
            if (is_file($oldAbs) && strpos(realpath($oldAbs), realpath(__DIR__ . "/assets/oscs/")) === 0) {
                @unlink($oldAbs);
            }
        }
    }

    if ($temArquivo) {
        $stmtUp = $conn->prepare("UPDATE documento
                                  SET ano_referencia = ?, documento = ?, link = ?, descricao = ?
                                  WHERE id_documento = ? AND osc_id = ? LIMIT 1");
        $stmtUp->bind_param("ssssii", $newAno, $novoRel, $newLink, $newDescricao, $id_documento, $id_osc);
    } else {
        $stmtUp = $conn->prepare("UPDATE documento
                                  SET ano_referencia = ?, link = ?, descricao = ?
                                  WHERE id_documento = ? AND osc_id = ? LIMIT 1");
        $stmtUp->bind_param("sssii", $newAno, $newLink, $newDescricao, $id_documento, $id_osc);
    }

    if (!$stmtUp->execute()) {
        echo json_encode(["status" => "erro", "msg" => "Erro ao atualizar documento."]);
        exit;
    }

    echo json_encode([
        "status" => "ok",
        "msg" => "Documento atualizado com sucesso.",
        "id_documento" => $id_documento,
        "documento" => $novoRel,
        "descricao" => $newDescricao,
        "ano_referencia" => $newAno
    ]);
    exit;
}


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
if ($categoria === 'CONTABIL' && in_array($subtipoUpper, ['BALANCO_PATRIMONIAL', 'DRE'], true)) {
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
// (mantendo sua lógica original: se não existir, erro)
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

    $idUnico     = uniqid();
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
    if (!$isDecreto) {
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
