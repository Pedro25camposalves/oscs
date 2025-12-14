<?php
require 'autenticacao.php';
require_once 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID não fornecido']);
    exit;
}

$id = (int) $_GET['id'];

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
    // 5) ENVOLVIDOS (ator_osc)
    // ============================================
    $stmt = $conn->prepare("
        SELECT
            id,
            nome,
            telefone,
            email,
            funcao,
            foto
        FROM ator_osc
        WHERE osc_id = ?
        ORDER BY nome
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $envolvidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // ============================================
    // 6) ATIVIDADES (osc_atividade)
    // ============================================
    $stmt = $conn->prepare("
        SELECT cnae, area_atuacao, subarea
        FROM osc_atividade
        WHERE osc_id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $atividadesBD = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // transformar para o formato do front: { cnae, area, subarea }
    $atividades = [];
    foreach ($atividadesBD as $row) {
        $atividades[] = [
            'cnae'    => $row['cnae'],
            'area'    => $row['area_atuacao'],
            'subarea' => $row['subarea'],
        ];
    }

    // ============================================
    // 7) Montar resposta final
    // ============================================
    $resultado = [
        'id' => $osc['id'],
        'labelBanner' => $template['label_banner'] ?? '',

        'cores' => [
            'bg'  => $cores['cor1'] ?? '#ffffff',
            'sec' => $cores['cor2'] ?? '#000000',
            'ter' => $cores['cor3'] ?? '#cccccc',
            'qua' => $cores['cor4'] ?? '#999999',
        ],

        'missao'   => $osc['missao'],
        'visao'    => $osc['visao'],
        'valores'  => $osc['valores'],
        'historia' => $osc['historia'],

        'nomeOsc'          => $osc['nome'],
        'nomeFantasia'     => $osc['nome_fantasia'] ?? null,
        'sigla'            => $osc['sigla'],
        'situacaoCadastral'=> $osc['situacao_cadastral'],

        'email'     => $osc['email'],
        'cnpj'      => $osc['cnpj'],
        'telefone'  => $osc['telefone'],
        'instagram' => $osc['instagram'],
        'status'    => $osc['status'],
        'razaoSocial' => $osc['razao_social'],

        'anoCNPJ'     => $osc['ano_cnpj'],
        'anoFundacao' => $osc['ano_fundacao'],
        'responsavelLegal' => $osc['responsavel'],

        'cep'   => $imovel['cep']        ?? null,
        'cidade'=> $imovel['cidade']     ?? null,
        'bairro'=> $imovel['bairro']     ?? null,
        'logradouro' => $imovel['logradouro'] ?? null,
        'numero'     => $imovel['numero']     ?? null,
        'situacaoImovel' => $imovel['situacao'] ?? null,
        'endereco' => $imovel
            ? trim(
                ($imovel['logradouro'] ?? '') . ', ' .
                ($imovel['numero'] ?? '') . ', ' .
                ($imovel['bairro'] ?? '')
              )
            : null,

        'oQueFaz' => $osc['oque_faz'],

        'atividades' => $atividades,

        // aqui troca de fato a nomenclatura
        'envolvidos' => $envolvidos,

        'template' => $template,
    ];

    echo json_encode(['success' => true, 'data' => $resultado]);

} catch (Exception $e) {

    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
