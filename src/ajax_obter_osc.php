<?php
session_start();
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
    // 1) OSC
    // ============================================
    $stmt = $conn->prepare("SELECT * FROM osc WHERE id = ? LIMIT 1");
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
    // 2) TEMPLATE_WEB
    // ============================================
    $stmt = $conn->prepare("SELECT * FROM template_web WHERE osc_id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $template = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // ============================================
    // 3) CORES
    // ============================================
    $cores = null;
    if ($template && isset($template['cores_id'])) {
        $stmt = $conn->prepare("SELECT * FROM cores WHERE id_cores = ? LIMIT 1");
        $stmt->bind_param("i", $template['cores_id']);
        $stmt->execute();
        $cores = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    // ============================================
    // 4) IMÓVEIS/ENDEREÇOS DA OSC (endereco_osc) - lista
    // ============================================
    $stmt = $conn->prepare("
        SELECT
            eo.endereco_id,
            eo.situacao,
            eo.principal,
            e.cep,
            e.cidade,
            e.bairro,
            e.logradouro,
            e.numero,
            e.complemento,
            e.descricao
        FROM endereco_osc eo
        INNER JOIN endereco e ON e.id = eo.endereco_id
        WHERE eo.osc_id = ?
        ORDER BY eo.principal DESC, eo.endereco_id DESC
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $imoveisBD = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    $imoveis = [];
    foreach ($imoveisBD as $r) {
        $imoveis[] = [
            'endereco_id'  => (int)($r['endereco_id'] ?? 0),
            'situacao'     => $r['situacao'] ?? '',
            'principal'    => (int)($r['principal'] ?? 0),
            'cep'          => $r['cep'] ?? '',
            'cidade'       => $r['cidade'] ?? '',
            'bairro'       => $r['bairro'] ?? '',
            'logradouro'   => $r['logradouro'] ?? '',
            'numero'       => $r['numero'] ?? '',
            'complemento'  => $r['complemento'] ?? '',
            'descricao'    => $r['descricao'] ?? '',
        ];
    }
    
    if (count($imoveis) === 0) {
        $stmt = $conn->prepare("
            SELECT
                i.id AS imovel_id,
                i.situacao AS situacao,
                e.id AS endereco_id,
                e.cep,
                e.cidade,
                e.bairro,
                e.logradouro,
                e.numero,
                e.complemento,
                e.descricao
            FROM imovel i
            LEFT JOIN endereco e ON e.id = i.endereco_id
            WHERE i.osc_id = ?
            ORDER BY i.id DESC
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $imoveisBD2 = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    
        foreach ($imoveisBD2 as $k => $r) {
            $imoveis[] = [
                'imovel_id'    => (int)($r['imovel_id'] ?? 0),
                'endereco_id'  => (int)($r['endereco_id'] ?? 0),
                'situacao'     => $r['situacao'] ?? '',
                'principal'    => ($k === 0 ? 1 : 0), // no legado, assume o primeiro como principal
                'cep'          => $r['cep'] ?? '',
                'cidade'       => $r['cidade'] ?? '',
                'bairro'       => $r['bairro'] ?? '',
                'logradouro'   => $r['logradouro'] ?? '',
                'numero'       => $r['numero'] ?? '',
                'complemento'  => $r['complemento'] ?? '',
                'descricao'    => $r['descricao'] ?? '',
            ];
        }
    }
    
    
    // ============================================
    // 5) ENVOLVIDOS
    // ============================================
    $stmt = $conn->prepare("
        SELECT id, nome, telefone, email, funcao, foto
        FROM envolvido_osc
        WHERE osc_id = ?
        ORDER BY nome
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $envolvidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    if (!is_array($envolvidos)) $envolvidos = [];

    // ============================================
    // 6) ATIVIDADES
    // ============================================
    $stmt = $conn->prepare("
        SELECT id, cnae, area_atuacao, subarea
        FROM osc_atividade
        WHERE osc_id = ?
        ORDER BY id
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $atividadesBD = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $atividades = [];
    foreach ($atividadesBD as $row) {
        $atividades[] = [
            'id'      => (int)($row['id'] ?? 0),
            'cnae'    => $row['cnae'] ?? '',
            'area'    => $row['area_atuacao'] ?? '',
            'subarea' => $row['subarea'] ?? '',
        ];
    }

    // ============================================
    // 7) DOCUMENTOS
    // ============================================
    $documentos = [
        'INSTITUCIONAL' => [],
        'CERTIDAO'      => [],
        'CONTABIL'      => [
            'BALANCO_PATRIMONIAL' => [],
            'DRE'                 => [],
        ],
    ];

    $stmt = $conn->prepare("
        SELECT
            id_documento,
            categoria,
            subtipo,
            descricao,
            link,
            ano_referencia,
            documento,
            data_upload
        FROM documento
        WHERE osc_id = ? AND projeto_id IS NULL
        ORDER BY categoria, subtipo, ano_referencia DESC, id_documento DESC
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $docsBD = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($docsBD as $d) {
        $cat = $d['categoria'] ?? '';
        $sub = $d['subtipo'] ?? '';
        $path = $d['documento'] ?? '';
        $nome = $path ? basename($path) : '';

        $item = [
            'id_documento'   => (int)($d['id_documento'] ?? 0),
            'categoria'      => $cat,
            'subtipo'        => $sub,
            'descricao'      => $d['descricao'] ?? null,
            'link'           => $d['link'] ?? null,
            'ano_referencia' => $d['ano_referencia'] ?? null,
            'nome'           => $nome,
            'url'            => $path,
            'data_upload'    => $d['data_upload'] ?? null,
        ];

        if ($cat === 'CONTABIL') {
            if (!isset($documentos['CONTABIL'][$sub])) $documentos['CONTABIL'][$sub] = [];
            $documentos['CONTABIL'][$sub][] = $item;
        } else {
            $documentos[$cat][$sub] = $item;
        }
    }

    // ============================================
    // 8) Resposta
    // ============================================
    $resultado = [
        'id' => (int)$osc['id'],

        'nomeOsc'            => $osc['nome'] ?? '',
        'razaoSocial'        => $osc['razao_social'] ?? '',
        'nomeFantasia'       => $osc['nome_fantasia'] ?? '',
        'sigla'              => $osc['sigla'] ?? '',
        'situacaoCadastral'  => $osc['situacao_cadastral'] ?? '',

        'cnpj'       => $osc['cnpj'] ?? '',
        'email'      => $osc['email'] ?? '',
        'telefone'   => $osc['telefone'] ?? '',
        'instagram'  => $osc['instagram'] ?? '',

        'anoCNPJ'     => $osc['ano_cnpj'] ?? '',
        'anoFundacao' => $osc['ano_fundacao'] ?? '',
        'responsavelLegal' => $osc['responsavel'] ?? '',

        'missao'   => $osc['missao'] ?? '',
        'visao'    => $osc['visao'] ?? '',
        'valores'  => $osc['valores'] ?? '',
        'historia' => $osc['historia'] ?? '',
        'oQueFaz'  => $osc['oque_faz'] ?? '',

        'cores' => [
            'bg'  => $cores['cor1'] ?? '#ffffff',
            'sec' => $cores['cor2'] ?? '#000000',
            'ter' => $cores['cor3'] ?? '#cccccc',
            'qua' => $cores['cor4'] ?? '#999999',
            'fon' => $cores['cor5'] ?? '#000000',
        ],

        'logos' => [
            'logoSimples'  => $template['logo_simples'] ?? '',
            'logoCompleta' => $template['logo_completa'] ?? '',
        ],
        'banners' => [
            'banner1'     => $template['banner1'] ?? '',
            'banner2'     => $template['banner2'] ?? '',
            'banner3'     => $template['banner3'] ?? '',
            'labelBanner' => $template['label_banner'] ?? '',
        ],

        'imoveis' => $imoveis,
        'atividades' => $atividades,
        'envolvidos' => $envolvidos,
        'documentos' => $documentos,
        'template' => $template,
    ];

    echo json_encode(['success' => true, 'data' => $resultado]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
