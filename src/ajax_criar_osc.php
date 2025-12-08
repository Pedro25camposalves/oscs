<?php
include 'conexao.php';
error_log("teste",0,"error.log");   

// Cria a estrutura de diretórios de documentos/imagens da OSC:
function criarDiretoriosOsc(int $oscId): bool
{
    // __DIR__ = .../OSCS/src
    // baseDir = .../OSCS/src/assets/oscs
    $baseDir = __DIR__ . '/assets/oscs';

    if (!is_dir($baseDir) && !mkdir($baseDir, 0777, true)) {
        return false;
    }

    // Raiz da OSC
    $oscRoot = $baseDir . '/osc-' . $oscId;

    // Pastas que precisam existir para cada OSC
    $dirs = [
        $oscRoot,                
        $oscRoot . '/documentos',        
        $oscRoot . '/imagens',           
        $oscRoot . '/projetos',          
    ];

    foreach ($dirs as $dir) {
        if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
            return false;
        }
    }

    return true;
}

// Lê o JSON vindo do JavaScript
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'JSON inválido']);
    exit;
}

// --- Salva os dados principais na tabela OSC ---
$nomeOsc           = mysqli_real_escape_string($conn, $data['nomeOsc']);
$email             = mysqli_real_escape_string($conn, $data['email']);
$razaoSocial       = mysqli_real_escape_string($conn, $data['razaoSocial']);
$nomeFantasia      = mysqli_real_escape_string($conn, $data['nomeFantasia']);
$sigla             = mysqli_real_escape_string($conn, $data['sigla']);
$situacaoCadastral = mysqli_real_escape_string($conn, $data['situacaoCadastral']);
$anoCNPJ           = mysqli_real_escape_string($conn, $data['anoCNPJ']);
$anoFundacao       = mysqli_real_escape_string($conn, $data['anoFundacao']);
$responsavel       = mysqli_real_escape_string($conn, $data['responsavelLegal']);
$missao            = mysqli_real_escape_string($conn, $data['missao']);
$visao             = mysqli_real_escape_string($conn, $data['visao']);
$valores           = mysqli_real_escape_string($conn, $data['valores']);
$historia          = mysqli_real_escape_string($conn, $data['historia']);
$oQueFaz           = mysqli_real_escape_string($conn, $data['oQueFaz']);
$cnpj              = mysqli_real_escape_string($conn, $data['cnpj']);
$telefone          = mysqli_real_escape_string($conn, $data['telefone']);
$instagram         = mysqli_real_escape_string($conn, $data['instagram']);
$status            = mysqli_real_escape_string($conn, $data['status']);

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

$osc_id = mysqli_insert_id($conn);

// --- Salva as ATIVIDADES (CNAE / Área / Subárea) ---
$atividades = $data['atividades'] ?? [];

foreach ($atividades as $atv) {
    $cnae    = mysqli_real_escape_string($conn, $atv['cnae']   ?? '');
    $area    = mysqli_real_escape_string($conn, $atv['area']   ?? '');
    $subarea = mysqli_real_escape_string($conn, $atv['subarea'] ?? '');

    // Se não tiver nada relevante, pula
    if ($cnae === '' && $area === '') {
        continue;
    }

    $sql_atividade = "
        INSERT INTO osc_atividade (osc_id, cnae, area_atuacao, subarea)
        VALUES ('$osc_id', '$cnae', '$area', '$subarea')
    ";

    if (!mysqli_query($conn, $sql_atividade)) {
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar atividade: ' . mysqli_error($conn)]);
        exit;
    }
}

// Cria diretórios de documentos da OSC
if (!criarDiretoriosOsc((int)$osc_id)) {
    echo json_encode([
        'success' => false,
        'error' => 'OSC criada no banco, mas falha ao criar diretórios de documentos no servidor.'
    ]);
    exit;
}

// --- Salva os dados do ator (Diretor da OSC) ---
$diretores = $data['diretores'] ?? [];

foreach ($diretores as $envolvido) {
    $nome     = mysqli_real_escape_string($conn, $envolvido['nome']);
    $telefone = mysqli_real_escape_string($conn, $envolvido['telefone'] ?? '');
    $email = mysqli_real_escape_string($conn, $envolvido['email'] ?? '');
    $func     = mysqli_real_escape_string($conn, $envolvido['func']);

    if ($nome === '' && $func === '') {
        continue;
    }

    // Insere o ator
    $sql_ator = "
        INSERT INTO ator (nome, telefone, email)
        VALUES ('$nome', '$telefone', '$email')
        ";

    if (!mysqli_query($conn, $sql_ator)) {
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar o ator: ' . mysqli_error($conn)]);
        exit;
    }

    $ator_id = mysqli_insert_id($conn);

    // Relaciona o ator com a OSC
    $sql_ator_osc = "
        INSERT INTO ator_osc (ator_id, osc_id, funcao)
        VALUES ('$ator_id', '$osc_id', '$func')
    ";

    if (!mysqli_query($conn, $sql_ator_osc)) {
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar a relação ator_osc: ' . mysqli_error($conn)]);
        exit;
    }

    $ator_osc_id = mysqli_insert_id($conn);
}

// --- Salva os dados do imovel da OSC ---
$situacaoImovel = mysqli_real_escape_string($conn, $data['situacaoImovel']);
$cep            = mysqli_real_escape_string($conn, $data['cep']);
$cidade         = mysqli_real_escape_string($conn, $data['cidade']);
$bairro         = mysqli_real_escape_string($conn, $data['bairro']);
$logradouro     = mysqli_real_escape_string($conn, $data['logradouro']);
$numero         = mysqli_real_escape_string($conn, $data['numero']);

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

// --- Salva as cores na tabela `cores` ---
$cor1 = mysqli_real_escape_string($conn, $data['cores']['bg']);
$cor2 = mysqli_real_escape_string($conn, $data['cores']['sec']);
$cor3 = mysqli_real_escape_string($conn, $data['cores']['ter']);
$cor4 = mysqli_real_escape_string($conn, $data['cores']['qua']);

$sql_cores = "
    INSERT INTO cores (osc_id, cor1, cor2, cor3, cor4)
    VALUES ('$osc_id', '$cor1', '$cor2', '$cor3', '$cor4')
    ";

if (!mysqli_query($conn, $sql_cores)) {
    echo json_encode(['success' => false, 'error' => 'Erro ao salvar cores: ' . mysqli_error($conn)]);
    exit;
}

$cores_id = mysqli_insert_id($conn);

// --- Salva o template visual (logos, banners) ---
$logoSimples  = mysqli_real_escape_string($conn, $data['logos']['logoSimples']);
$logoCompleta = mysqli_real_escape_string($conn, $data['logos']['logoCompleta']);
$banner1      = mysqli_real_escape_string($conn, $data['banners']['banner1']);
$banner2      = mysqli_real_escape_string($conn, $data['banners']['banner2']);
$banner3      = mysqli_real_escape_string($conn, $data['banners']['banner3']);
$labelBanner  = mysqli_real_escape_string($conn, $data['banners']['labelBanner']);

$sql_template = "
    INSERT INTO template_web (
        osc_id, descricao, cores_id, logo_simples, logo_completa, banner1, banner2, banner3, label_banner
    ) VALUES (
        '$osc_id', 'Template Padrão', '$cores_id', '$logoSimples', '$logoCompleta', '$banner1', '$banner2', '$banner3', '$labelBanner'
    )";

if (!mysqli_query($conn, $sql_template)) {
    echo json_encode(['success' => false, 'error' => 'Erro ao salvar template: ' . mysqli_error($conn)]);
    exit;
}

echo json_encode([
    'success' => true,
    'template_id' => mysqli_insert_id($conn),
    'cores_id' => $cores_id,
    'osc_id' => $osc_id,
    'atividade_osc_id'=> true,
    'ator_id' => $ator_id,
    'ator_osc_id' => true,
    'imovel_id' => $imovel_id
    
]);

mysqli_close($conn);