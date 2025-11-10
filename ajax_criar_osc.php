<?php
include 'conexao.php';

// Lê o JSON vindo do JavaScript
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// error_log(str_repeat("-", 60));
// error_log("📦 NOVA REQUISIÇÃO ajax_criar_osc.php — " . date("Y-m-d H:i:s"));
// error_log("JSON recebido:" . PHP_EOL . $json);
// error_log("Array decodificado:" . PHP_EOL . print_r($data, true));
// error_log(str_repeat("-", 60));


if (!$data) {
    echo json_encode(['success' => false, 'error' => 'JSON inválido']);
    exit;
}



// --- 1️⃣ Salva os dados principais na tabela OSC ---
$nomeOsc = mysqli_real_escape_string($conn, $data['nomeOsc']);
$email = mysqli_real_escape_string($conn, $data['email']);
$nomeFantasia = mysqli_real_escape_string($conn, $data['nomeFantasia']);
$sigla = mysqli_real_escape_string($conn, $data['sigla']);
$situacaoCadastral = mysqli_real_escape_string($conn, $data['situacaoCadastral']);
$anoCNPJ = mysqli_real_escape_string($conn, $data['anoCNPJ']);
$anoFundacao = mysqli_real_escape_string($conn, $data['anoFundacao']);
$abreviacao = mysqli_real_escape_string($conn, $data['abreviacao']);
$cnae = mysqli_real_escape_string($conn, $data['cnae']);
$subarea = mysqli_real_escape_string($conn, $data['subarea']);
$missao = mysqli_real_escape_string($conn, $data['missao']);
$visao = mysqli_real_escape_string($conn, $data['visao']);
$valores = mysqli_real_escape_string($conn, $data['valores']);

// Campos obrigatórios que não vêm no JSON
$cnpj = '00000000000000'; // coloque o real se tiver
$telefone = '00000000000'; // idem

$sql_osc = "
INSERT INTO osc (
    nome, cnpj, telefone, email, nome_fantasia, sigla, situacao_cadastral,
    ano_cnpj, ano_fundacao, abreviacao, cnae, subarea, missao, visao, valores
) VALUES (
    '$nomeOsc', '$cnpj', '$telefone', '$email', '$nomeFantasia', '$sigla', '$situacaoCadastral',
    '$anoCNPJ', '$anoFundacao', '$abreviacao', '$cnae', '$subarea', '$missao', '$visao', '$valores'
)";

if (!mysqli_query($conn, $sql_osc)) {
    echo json_encode(['success' => false, 'error' => 'Erro ao salvar OSC: ' . mysqli_error($conn)]);
    exit;
}

$osc_id = mysqli_insert_id($conn);

// --- 2️⃣ Salva as cores na tabela `cores` ---
$cor1 = mysqli_real_escape_string($conn, $data['cores']['bg']);
$cor2 = mysqli_real_escape_string($conn, $data['cores']['sec']);
$cor3 = mysqli_real_escape_string($conn, $data['cores']['ter']);
$cor4 = mysqli_real_escape_string($conn, $data['cores']['qua']);

$sql_cores = "
INSERT INTO cores (cor1, cor2, cor3, cor4)
VALUES ('$cor1', '$cor2', '$cor3', '$cor4')
";

if (!mysqli_query($conn, $sql_cores)) {
    echo json_encode(['success' => false, 'error' => 'Erro ao salvar cores: ' . mysqli_error($conn)]);
    exit;
}

$cores_id = mysqli_insert_id($conn);

// --- 3️⃣ Salva o template visual (logos, banners) ---
$logoSimples = mysqli_real_escape_string($conn, $data['logos']['logoSimples']);
$logoCompleta = mysqli_real_escape_string($conn, $data['logos']['logoCompleta']);
$banner1 = mysqli_real_escape_string($conn, $data['banners']['banner1']);
$banner2 = mysqli_real_escape_string($conn, $data['banners']['banner2']);
$banner3 = mysqli_real_escape_string($conn, $data['banners']['banner3']);
$labelBanner = mysqli_real_escape_string($conn, $data['banners']['labelBanner']);

$sql_template = "
INSERT INTO template_web (
    osc_id, descricao, caminho, cores_id,
    logo_simples, logo_completa, banner1, banner2, banner3, label_banner
) VALUES (
    '$osc_id', 'Template Padrão', '/assets/images/oscs/', '$cores_id',
    '$logoSimples', '$logoCompleta', '$banner1', '$banner2', '$banner3', '$labelBanner'
)
";

if (!mysqli_query($conn, $sql_template)) {
    echo json_encode(['success' => false, 'error' => 'Erro ao salvar template: ' . mysqli_error($conn)]);
    exit;
}

// ✅ Tudo certo!
echo json_encode([
    'success' => true,
    'osc_id' => $osc_id,
    'cores_id' => $cores_id,
    'template_id' => mysqli_insert_id($conn)
]);

mysqli_close($conn);