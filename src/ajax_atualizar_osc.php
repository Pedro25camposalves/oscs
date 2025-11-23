<?php
include 'conexao.php';

// Lê o JSON
$json = file_get_contents("php://input");
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'JSON inválido']);
    exit;
}

$osc_id = intval($_GET['id']);
error_log($osc_id,0,"error.log");
$cores_id = $osc_id;
$template_id = $osc_id;

if (!$osc_id || !$cores_id || !$template_id) {
    echo json_encode(['success' => false, 'error' => 'IDs não enviados']);
    exit;
}

/* -----------------------------------------------------
    1️⃣  UPDATE TABELA OSC
----------------------------------------------------- */

$nomeOsc = mysqli_real_escape_string($conn, $data['nomeOsc']);
$email = mysqli_real_escape_string($conn, $data['email']);
$nomeFantasia = mysqli_real_escape_string($conn, $data['nomeFantasia']);
$sigla = mysqli_real_escape_string($conn, $data['sigla']);
$situacaoCadastral = mysqli_real_escape_string($conn, $data['situacaoCadastral']);
$anoCNPJ = mysqli_real_escape_string($conn, $data['anoCNPJ']);
$anoFundacao = mysqli_real_escape_string($conn, $data['anoFundacao']);
$cnae = mysqli_real_escape_string($conn, $data['cnae']);
$subarea = mysqli_real_escape_string($conn, $data['subarea']);
$missao = mysqli_real_escape_string($conn, $data['missao']);
$visao = mysqli_real_escape_string($conn, $data['visao']);
$valores = mysqli_real_escape_string($conn, $data['valores']);
$historia = mysqli_real_escape_string($conn, $data['historia']);
$oQueFaz = mysqli_real_escape_string($conn, $data['oQueFaz']);


$cnpj = mysqli_real_escape_string($conn, $data['cnpj']);
$telefone = mysqli_real_escape_string($conn, $data['telefone']);
$instagram = mysqli_real_escape_string($conn, $data['instagram']);
$status = mysqli_real_escape_string($conn, $data['status']);

$sql_osc = "
UPDATE osc SET
    nome='$nomeOsc',
    cnpj='$cnpj',
    telefone='$telefone',
    email='$email',
    nome_fantasia='$nomeFantasia',
    sigla='$sigla',
    situacao_cadastral='$situacaoCadastral',
    ano_cnpj='$anoCNPJ',
    ano_fundacao='$anoFundacao',
    cnae='$cnae',
    subarea='$subarea',
    missao='$missao',
    visao='$visao',
    valores='$valores',
    instagram='$instagram',
    status='$status',
    historia='$historia',
    oque_faz='$oQueFaz'
WHERE id=$osc_id
";

if (!mysqli_query($conn, $sql_osc)) {
    echo json_encode(['success' => false, 'error' => 'Erro ao atualizar OSC: ' . mysqli_error($conn)]);
    exit;
}

/* -----------------------------------------------------
    2️⃣  UPDATE TABELA CORES
----------------------------------------------------- */

$cor1 = mysqli_real_escape_string($conn, $data['cores']['bg']);
$cor2 = mysqli_real_escape_string($conn, $data['cores']['sec']);
$cor3 = mysqli_real_escape_string($conn, $data['cores']['ter']);
$cor4 = mysqli_real_escape_string($conn, $data['cores']['qua']);

$sql_cores = "
UPDATE cores SET
    cor1='$cor1',
    cor2='$cor2',
    cor3='$cor3',
    cor4='$cor4'
WHERE id_cores=$cores_id
";

if (!mysqli_query($conn, $sql_cores)) {
    echo json_encode(['success' => false, 'error' => 'Erro ao atualizar cores: ' . mysqli_error($conn)]);
    exit;
}

/* -----------------------------------------------------
    3️⃣  UPDATE TEMPLATE_WEB (logos e banners)
----------------------------------------------------- */

$logoSimples = mysqli_real_escape_string($conn, $data['logos']['logoSimples']);
$logoCompleta = mysqli_real_escape_string($conn, $data['logos']['logoCompleta']);
$banner1 = mysqli_real_escape_string($conn, $data['banners']['banner1']);
$banner2 = mysqli_real_escape_string($conn, $data['banners']['banner2']);
$banner3 = mysqli_real_escape_string($conn, $data['banners']['banner3']);
$labelBanner = mysqli_real_escape_string($conn, $data['banners']['labelBanner']);

$sql_template = "
UPDATE template_web SET
    logo_simples='$logoSimples',
    logo_completa='$logoCompleta',
    banner1='$banner1',
    banner2='$banner2',
    banner3='$banner3',
    label_banner='$labelBanner'
WHERE id=$template_id
";

if (!mysqli_query($conn, $sql_template)) {
    echo json_encode(['success' => false, 'error' => 'Erro ao atualizar template: ' . mysqli_error($conn)]);
    exit;
}

/* -----------------------------------------------------
    4️⃣  FINAL
----------------------------------------------------- */

echo json_encode([
    'success' => true,
    'osc_id' => $osc_id,
    'cores_id' => $cores_id,
    'template_id' => $template_id
]);

mysqli_close($conn);
