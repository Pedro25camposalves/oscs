<?php
/**
 * ajax_obter_osc.php
 * Endpoint para obter dados reais da OSC do banco MySQL usando mysqli
 */

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID não fornecido']);
    exit;
}

$id = (int) $_GET['id'];

require_once 'conexao.php'; // este arquivo deve criar $conn (mysqli)

try {

    // ============================================
    // 1) BUSCAR OSC
    // ============================================
    $stmt = $conn->prepare("SELECT * FROM osc WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $osc = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$osc) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'OSC não encontrada']);
        exit;
    }

    // ============================================
    // 2) TEMPLATE WEB
    // ============================================
    $stmt = $conn->prepare("SELECT * FROM template_web WHERE osc_id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $template = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // ============================================
    // 3) CORES DO TEMPLATE
    // ============================================
    $cores = null;
    if ($template && isset($template['cores_id'])) {
        $stmt = $conn->prepare("SELECT * FROM cores WHERE id_cores = ?");
        $stmt->bind_param("i", $template['cores_id']);
        $stmt->execute();
        $cores = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    // ============================================
    // 4) ENDEREÇO / IMÓVEL
    // ============================================
    $stmt = $conn->prepare("SELECT * FROM imovel WHERE osc_id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $imovel = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // ============================================
    // 5) DIRETORES (ator + ator_osc)
    // ============================================
    $stmt = $conn->prepare("
        SELECT a.nome, a.telefone, ao.funcao
        FROM ator a
        INNER JOIN ator_osc ao ON ao.ator_id = a.id
        WHERE ao.osc_id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $diretores = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // ============================================
    // 6) Montar resposta final
    // ============================================
    error_log($osc['endereco'],0,"error.log");
    $resultado = [
        'id' => $osc['id'],
        'labelBanner' => $template['label_banner'] ?? '',

        'cores' => [
            'bg'  => $cores['cor1'] ?? '#ffffff',
            'sec' => $cores['cor2'] ?? '#000000',
            'ter' => $cores['cor3'] ?? '#cccccc',
            'qua' => $cores['cor4'] ?? '#999999',
        ],

        'missao' => $osc['missao'],
        'visao' => $osc['visao'],
        'valores' => $osc['valores'],

        // estes campos não existem no banco → definindo como null
        'nomeOsc' => $osc['nome'],
        'historia' => $osc['historia'],
        'area' => $osc['area_atuacao'],
        'recursos' => null,
        'responsavelLegal' => null,
        'oQueFaz' => $osc['oque_faz'],
        'instagram' => $osc['instagram'],
        'status' => $osc['status'],
        'razao_social' => $osc['razao_social'],

        'cnae' => $osc['cnae'],
        'subarea' => $osc['subarea'],

        'nomeFantasia' => $osc['nome_fantasia'],
        'sigla' => $osc['sigla'],
        'situacaoCadastral' => $osc['situacao_cadastral'],

        // 'endereco' => $imovel
        //     ? "{$imovel['logradouro']}, {$imovel['numero']}, {$imovel['bairro']}"
        //     : null,
        'endereco' => $osc['endereco'],

        'anoCNPJ' => $osc['ano_cnpj'],
        'anoFundacao' => $osc['ano_fundacao'],

        'email' => $osc['email'],
        'abreviacao' => $osc['sigla'],
        'cnpj' => $osc['cnpj'],
        'telefone' => $osc['telefone'],

        'diretores' => $diretores,
        'template' => $template
    ];

    echo json_encode(['success' => true, 'data' => $resultado]);

} catch (Exception $e) {

    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;

}