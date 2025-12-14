<?php
$TIPOS_PERMITIDOS = ['OSC_TECH_ADMIN']; // só OscTech admin pode criar OSC
$RESPOSTA_JSON    = true;               // endpoint retorna JSON
require 'autenticacao.php';

include 'conexao.php';

function criarDiretoriosOsc(int $oscId): bool
{
    $baseDir = __DIR__ . '/assets/oscs';

    if (!is_dir($baseDir) && !mkdir($baseDir, 0777, true)) {
        return false;
    }

    $oscRoot = $baseDir . '/osc-' . $oscId;

    $dirs = [
        $oscRoot,
        $oscRoot . '/documentos',
        $oscRoot . '/imagens',
        $oscRoot . '/projetos',
        $oscRoot . '/envolvidos',
    ];

    foreach ($dirs as $dir) {
        if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
            return false;
        }
    }

    return true;
}

function moverArquivo(string $fieldName, string $imgDir, string $imgRelBase): ?string
{
    if (
        !isset($_FILES[$fieldName]) ||
        $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK
    ) {
        return null;
    }

    $originalName = basename($_FILES[$fieldName]['name']);
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    $ext = $ext ? ('.' . $ext) : '';

    $fileName = uniqid($fieldName . '_', true) . $ext;

    if (!is_dir($imgDir) && !mkdir($imgDir, 0777, true)) {
        return null;
    }

    $destFull = rtrim($imgDir, '/') . '/' . $fileName;

    if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $destFull)) {
        return rtrim($imgRelBase, '/') . '/' . $fileName;
    }

    return null;
}

// --- Lê os campos vindos via POST ---
$nomeOsc           = mysqli_real_escape_string($conn, $_POST['nomeOsc']             ?? '');
$email             = mysqli_real_escape_string($conn, $_POST['email']               ?? '');
$razaoSocial       = mysqli_real_escape_string($conn, $_POST['razaoSocial']         ?? '');
$nomeFantasia      = mysqli_real_escape_string($conn, $_POST['nomeFantasia']        ?? '');
$sigla             = mysqli_real_escape_string($conn, $_POST['sigla']               ?? '');
$situacaoCadastral = mysqli_real_escape_string($conn, $_POST['situacaoCadastral']   ?? '');
$anoCNPJ           = mysqli_real_escape_string($conn, $_POST['anoCNPJ']             ?? '');
$anoFundacao       = mysqli_real_escape_string($conn, $_POST['anoFundacao']         ?? '');
$responsavel       = mysqli_real_escape_string($conn, $_POST['responsavelLegal']    ?? '');
$missao            = mysqli_real_escape_string($conn, $_POST['missao']              ?? '');
$visao             = mysqli_real_escape_string($conn, $_POST['visao']               ?? '');
$valores           = mysqli_real_escape_string($conn, $_POST['valores']             ?? '');
$historia          = mysqli_real_escape_string($conn, $_POST['historia']            ?? '');
$oQueFaz           = mysqli_real_escape_string($conn, $_POST['oQueFaz']             ?? '');
$cnpj              = mysqli_real_escape_string($conn, $_POST['cnpj']                ?? '');
$telefone          = mysqli_real_escape_string($conn, $_POST['telefone']            ?? '');
$instagram         = mysqli_real_escape_string($conn, $_POST['instagram']           ?? '');
$status            = mysqli_real_escape_string($conn, $_POST['status']              ?? '');

// --- Insere a nova OSC ---
$sql_osc = "
    INSERT INTO osc (
        nome, razao_social, cnpj, telefone, email, nome_fantasia, sigla, situacao_cadastral,
        ano_cnpj, ano_fundacao, responsavel, missao, visao, valores, instagram, status, historia, oque_faz
    ) VALUES (
        '$nomeOsc', '$razaoSocial', '$cnpj', '$telefone', '$email', '$nomeFantasia', '$sigla', '$situacaoCadastral',
        '$anoCNPJ', '$anoFundacao', '$responsavel', '$missao', '$visao', '$valores', '$instagram', '$status', '$historia', '$oQueFaz'
    )";

if (!mysqli_query($conn, $sql_osc)) {
    echo json_encode(['success' => false, 'error' => 'Erro ao salvar OSC: ' . mysqli_error($conn)]);
    exit;
}

$osc_id = (int) mysqli_insert_id($conn);

if (!criarDiretoriosOsc($osc_id)) {
    echo json_encode([
        'success' => false,
        'error'   => 'OSC criada no banco, mas falha ao criar diretórios no servidor!'
    ]);
    exit;
}

$baseOscDir = __DIR__ . '/assets/oscs/osc-' . $osc_id;
$imgDir     = $baseOscDir . '/imagens/';
$imgRelBase = 'assets/oscs/osc-' . $osc_id . '/imagens/';

// --- Captura as atividades da OSC ---
$atividadesJson = $_POST['atividades'] ?? '[]';
$atividades = json_decode($atividadesJson, true);
if (!is_array($atividades)) {
    $atividades = [];
}

$atividades_ids = [];

foreach ($atividades as $atv) {
    $cnae    = mysqli_real_escape_string($conn, $atv['cnae']    ?? '');
    $area    = mysqli_real_escape_string($conn, $atv['area']    ?? '');
    $subarea = mysqli_real_escape_string($conn, $atv['subarea'] ?? '');

    if ($cnae === '' && $area === '') {
        continue;
    }

    $sql_atividade = "
        INSERT INTO osc_atividade (osc_id, cnae, area_atuacao, subarea)
        VALUES ('$osc_id', '$cnae', '$area', '$subarea')
    ";

    if (!mysqli_query($conn, $sql_atividade)) {
        echo json_encode([
            'success' => false,
            'error'   => 'Erro ao salvar as atividade da OSC: ' . mysqli_error($conn)
        ]);
        exit;
    }

    $atividadeId = (int) mysqli_insert_id($conn);
    $atividades_ids[] = $atividadeId;
}

// --- Captura o usuário responsável pela OSC (OSC_MASTER) ---
$usuarioNome  = mysqli_real_escape_string($conn, $_POST['usuario_nome']  ?? '');
$usuarioEmail = mysqli_real_escape_string($conn, $_POST['usuario_email'] ?? '');
$usuarioSenha = $_POST['usuario_senha'] ?? '';

if ($usuarioNome !== '' && $usuarioEmail !== '' && $usuarioSenha !== '') {
    $senhaHash = password_hash($usuarioSenha, PASSWORD_DEFAULT);

    // Insere o usuário da OSC
    $sqlUsuario = "
        INSERT INTO usuario (nome, email, senha, tipo, ativo)
        VALUES ('$usuarioNome', '$usuarioEmail', '$senhaHash', 'OSC_MASTER', 1)
    ";

    if (!mysqli_query($conn, $sqlUsuario)) {
        echo json_encode([
            'success' => false,
            'error'   => 'Erro ao criar usuário da OSC: ' . mysqli_error($conn)
        ]);
        exit;
    }

    $usuarioId = (int) mysqli_insert_id($conn);

    // Vincula usuário à OSC
    $sqlUsuarioOsc = "
        INSERT INTO usuario_osc (usuario_id, osc_id)
        VALUES ('$usuarioId', '$osc_id')
    ";

    if (!mysqli_query($conn, $sqlUsuarioOsc)) {
        echo json_encode([
            'success' => false,
            'error'   => 'Usuário criado, mas erro ao vincular à OSC: ' . mysqli_error($conn)
        ]);
        exit;
    }
}

// --- Captura os dados dos envolvidos ---
$envolvidosJson = $_POST['envolvidos'] ?? '[]';
$envolvidos = json_decode($envolvidosJson, true);

if (!is_array($envolvidos)) {
    $envolvidos = [];
}

$envolvidos_ids = [];

$envolvidosRootDir     = $baseOscDir . '/envolvidos/';
$envolvidosRootRelBase = 'assets/oscs/osc-' . $osc_id . '/envolvidos/';

$funcoesValidas = ['DIRETOR','COORDENADOR','FINANCEIRO','MARKETING','RH'];

// --- Salva os dados de cada envolvido ---
foreach ($envolvidos as $idx => $envolvido) {
    $nome      = mysqli_real_escape_string($conn, $envolvido['nome']      ?? '');
    $telefone  = mysqli_real_escape_string($conn, $envolvido['telefone']  ?? '');
    $emailEnv  = mysqli_real_escape_string($conn, $envolvido['email']     ?? '');

    $funcaoRaw = strtoupper(trim($envolvido['funcao'] ?? ''));

    if ($nome === '' && $funcaoRaw === '') {
        continue;
    }

    if (!in_array($funcaoRaw, $funcoesValidas, true)) {
        echo json_encode([
            'success' => false,
            'error'   => 'Função de envolvido inválida.'
        ]);
        exit;
    }

    $funcao = mysqli_real_escape_string($conn, $funcaoRaw);

    $sql_envolvido = "
        INSERT INTO envolvido_osc (osc_id, foto, nome, telefone, email, funcao)
        VALUES ('$osc_id', NULL, '$nome', '$telefone', '$emailEnv', '$funcao')
    ";

    if (!mysqli_query($conn, $sql_envolvido)) {
        echo json_encode([
            'success' => false,
            'error'   => 'Erro ao salvar envolvido_osc (envolvido): ' . mysqli_error($conn)
        ]);
        exit;
    }

    $envolvidoId = (int) mysqli_insert_id($conn);
    $envolvidos_ids[] = $envolvidoId;

    $envolvidoBaseDir = $envolvidosRootDir . 'envolvido-' . $envolvidoId . '/';
    $envolvidoDocsDir = $envolvidoBaseDir . 'documentos/';
    $envolvidoImgDir  = $envolvidoBaseDir . 'imagens/';

    $dirsEnvolvido = [
        $envolvidoBaseDir,
        $envolvidoDocsDir,
        $envolvidoImgDir,
    ];

    foreach ($dirsEnvolvido as $dir) {
        if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
            echo json_encode([
                'success' => false,
                'error'   => 'Erro ao criar diretórios do envolvido no servidor.'
            ]);
            exit;
        }
    }

    $fieldNameFoto       = 'fotoEnvolvido_' . $idx;
    $envolvidoImgRelBase = $envolvidosRootRelBase . 'envolvido-' . $envolvidoId . '/imagens/';

    $caminhoFotoRel = moverArquivo($fieldNameFoto, $envolvidoImgDir, $envolvidoImgRelBase);

    if ($caminhoFotoRel !== null) {
        $fotoSql = mysqli_real_escape_string($conn, $caminhoFotoRel);
        $sql_update_foto = "
            UPDATE envolvido_osc
               SET foto = '$fotoSql'
             WHERE id = '$envolvidoId'
        ";
        mysqli_query($conn, $sql_update_foto);
    }
}

// --- Salve os dados do imóvel da OSC ---
$situacaoImovel = mysqli_real_escape_string($conn, $_POST['situacaoImovel'] ?? '');
$cep            = mysqli_real_escape_string($conn, $_POST['cep']            ?? '');
$cidade         = mysqli_real_escape_string($conn, $_POST['cidade']         ?? '');
$bairro         = mysqli_real_escape_string($conn, $_POST['bairro']         ?? '');
$logradouro     = mysqli_real_escape_string($conn, $_POST['logradouro']     ?? '');
$numero         = mysqli_real_escape_string($conn, $_POST['numero']         ?? '');

$sql_imovel = "
    INSERT INTO imovel (
        osc_id, cep, cidade, logradouro, bairro, numero, situacao
    ) VALUES (
        '$osc_id', '$cep', '$cidade', '$logradouro', '$bairro', '$numero', '$situacaoImovel'
    )";

if (!mysqli_query($conn, $sql_imovel)) {
    echo json_encode(['success' => false, 'error' => 'Erro ao salvar Imovel: ' . mysqli_error($conn)]);
    exit;
}

$imovel_id = mysqli_insert_id($conn);

// --- Salva as cores da OSC ---
$cores = $_POST['cores'] ?? [];
$cor1  = mysqli_real_escape_string($conn, $cores['bg']  ?? '');
$cor2  = mysqli_real_escape_string($conn, $cores['sec'] ?? '');
$cor3  = mysqli_real_escape_string($conn, $cores['ter'] ?? '');
$cor4  = mysqli_real_escape_string($conn, $cores['qua'] ?? '');
$cor5  = mysqli_real_escape_string($conn, $cores['fon'] ?? '');

// --- Insere as cores ---
$sql_cores = "
    INSERT INTO cores (osc_id, cor1, cor2, cor3, cor4, cor5)
    VALUES ('$osc_id', '$cor1', '$cor2', '$cor3', '$cor4', '$cor5')
";

if (!mysqli_query($conn, $sql_cores)) {
    echo json_encode(['success' => false, 'error' => 'Erro ao salvar cores: ' . mysqli_error($conn)]);
    exit;
}

$cores_id = mysqli_insert_id($conn);

// --- Salva o template visual (logos e banners) ---
$logoSimples  = moverArquivo('logoSimples',  $imgDir, $imgRelBase);
$logoCompleta = moverArquivo('logoCompleta', $imgDir, $imgRelBase);
$banner1      = moverArquivo('banner1',      $imgDir, $imgRelBase);
$banner2      = moverArquivo('banner2',      $imgDir, $imgRelBase);
$banner3      = moverArquivo('banner3',      $imgDir, $imgRelBase);

$labelBanner  = mysqli_real_escape_string($conn, $_POST['labelBanner'] ?? '');

$sql_template = "
    INSERT INTO template_web (
        osc_id, descricao, cores_id, logo_simples, logo_completa, banner1, banner2, banner3, label_banner
    ) VALUES (
        '$osc_id', 'Template Padrão', '$cores_id',
        '$logoSimples', '$logoCompleta', '$banner1', '$banner2', '$banner3', '$labelBanner'
    )";

if (!mysqli_query($conn, $sql_template)) {
    echo json_encode(['success' => false, 'error' => 'Erro ao salvar template: ' . mysqli_error($conn)]);
    exit;
}

$template_id = mysqli_insert_id($conn);

// --- Retorno completo dos cadastros ---
echo json_encode([
    'success'          => true,
    'osc_id'           => $osc_id,
    'usuario_id'       => $usuarioId,
    'cores_id'         => $cores_id,
    'template_id'      => $template_id,
    'imovel_id'        => $imovel_id,
    'envolvidos_ids'   => $envolvidos_ids,
    'atividades_ids'   => $atividades_ids,
]);

mysqli_close($conn);
