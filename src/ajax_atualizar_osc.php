<?php
session_start();
include 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

function normalizarLista($valor): array {
    if (!isset($valor)) return [];

    if (is_string($valor)) {
        $tmp = json_decode($valor, true);
        if (json_last_error() === JSON_ERROR_NONE) $valor = $tmp;
    }

    if (!is_array($valor)) return [$valor];

    $keys = array_keys($valor);
    $isListaNumerica = ($keys === range(0, count($keys) - 1));
    if ($isListaNumerica) return $valor;

    // array associativo -> vira lista pelos valores (se fizer sentido)
    $itens = [];
    foreach ($valor as $v) {
        if (is_array($v)) $itens[] = $v;
    }
    return $itens ?: [$valor];
}

function pick($data, array $paths, $default = null) {
    foreach ($paths as $path) {
        $ref = $data;
        $ok = true;
        foreach (explode('.', $path) as $k) {
            if (!is_array($ref) || !array_key_exists($k, $ref)) { $ok = false; break; }
            $ref = $ref[$k];
        }
        if ($ok) return $ref;
    }
    return $default;
}

function salvarImagemSeEnviada(
    string $campo,
    string $destDirAbs,
    string $prefixo,
    string $atualRel,
    int $osc_id,
    array &$arquivosParaExcluir
    ): string {
    if (!isset($_FILES[$campo]) || $_FILES[$campo]['error'] !== UPLOAD_ERR_OK) {
        return $atualRel; // não mexe em nada
    }

    $tmp  = $_FILES[$campo]['tmp_name'];
    $name = $_FILES[$campo]['name'] ?? 'img';
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    $permitidas = ['jpg','jpeg','png','webp','gif'];
    if (!in_array($ext, $permitidas, true)) {
        throw new Exception("Arquivo inválido em {$campo} (extensão não permitida)");
    }

    if (!is_dir($destDirAbs) && !mkdir($destDirAbs, 0777, true)) {
        throw new Exception("Não foi possível criar diretório de imagens");
    }

    // 1) salva novo arquivo
    $novoNome = $prefixo . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destAbs  = rtrim($destDirAbs, '/\\') . DIRECTORY_SEPARATOR . $novoNome;

    if (!move_uploaded_file($tmp, $destAbs)) {
        throw new Exception("Falha ao salvar {$campo}");
    }

    // 2) prepara exclusão do antigo (só se existia)
    $atualRel = (string)$atualRel;
    if ($atualRel !== '') {
        // segurança: só deixa apagar arquivo dentro do diretório esperado
        $oldBasename = basename($atualRel);
        if ($oldBasename !== '' && $oldBasename !== '.' && $oldBasename !== '..') {
            $oldAbs = rtrim($destDirAbs, '/\\') . DIRECTORY_SEPARATOR . $oldBasename;

            // só agenda se existir e não for o mesmo nome (paranóia útil)
            if (is_file($oldAbs) && $oldAbs !== $destAbs) {
                $arquivosParaExcluir[] = $oldAbs;
            }
        }
    }

    // 3) caminho relativo pro BD
    return 'assets/oscs/osc-' . $osc_id . '/imagens/' . $novoNome;
}

function salvarFotoEnvolvidoSeEnviada(string $campo, string $destDirAbs, string $prefixo, string $atual, int $osc_id): string {
    if (!isset($_FILES[$campo]) || $_FILES[$campo]['error'] !== UPLOAD_ERR_OK) return $atual;

    $tmp  = $_FILES[$campo]['tmp_name'];
    $name = $_FILES[$campo]['name'] ?? 'img';
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    $permitidas = ['jpg','jpeg','png','webp','gif'];
    if (!in_array($ext, $permitidas, true)) {
        throw new Exception("Arquivo inválido em {$campo} (extensão não permitida)");
    }

    if (!is_dir($destDirAbs) && !mkdir($destDirAbs, 0777, true)) {
        throw new Exception("Não foi possível criar diretório de fotos dos envolvidos");
    }

    $novoNome = $prefixo . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destAbs  = rtrim($destDirAbs, '/\\') . DIRECTORY_SEPARATOR . $novoNome;

    if (!move_uploaded_file($tmp, $destAbs)) {
        throw new Exception("Falha ao salvar {$campo}");
    }

    // caminho relativo salvo no BD
    return 'assets/oscs/osc-' . $osc_id . '/envolvidos/' . $novoNome;
}

$data = $_POST;

if (empty($data)) {
    $json = file_get_contents("php://input");
    $tmp  = json_decode($json, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) {
        $data = $tmp;
    }
}

if (empty($data)) {
    echo json_encode(['success' => false, 'error' => 'Nenhum dado recebido (nem POST nem JSON)']);
    exit;
}

$osc_id = (int)($data['osc_id'] ?? ($_GET['id'] ?? 0));
if (!$osc_id) {
    echo json_encode(['success' => false, 'error' => 'ID da OSC não enviado (osc_id)']);
    exit;
}

mysqli_begin_transaction($conn);

try {
    // ============================================
    // 0) Descobrir IDs reais do template/cores
    // ============================================
    $template_id = null;
    $cores_id = null;

    $stmt = $conn->prepare("
        SELECT id, cores_id, logo_simples, logo_completa, banner1, banner2, banner3, label_banner
        FROM template_web
        WHERE osc_id = ? LIMIT 1
    ");
    $stmt->bind_param("i", $osc_id);
    $stmt->execute();
    $tplRow = $stmt->get_result()->fetch_assoc();
    $logoSimplesAtual  = $tplRow['logo_simples']  ?? '';
    $logoCompletaAtual = $tplRow['logo_completa'] ?? '';
    $banner1Atual      = $tplRow['banner1']       ?? '';
    $banner2Atual      = $tplRow['banner2']       ?? '';
    $banner3Atual      = $tplRow['banner3']       ?? '';
    $labelAtual        = $tplRow['label_banner']  ?? '';

    $logoSimples  = (string)pick($data, ['template.logo_simples'], $logoSimplesAtual);
    $logoCompleta = (string)pick($data, ['template.logo_completa'], $logoCompletaAtual);
    $banner1      = (string)pick($data, ['template.banner1'], $banner1Atual);
    $banner2      = (string)pick($data, ['template.banner2'], $banner2Atual);
    $banner3      = (string)pick($data, ['template.banner3'], $banner3Atual);
    $labelBanner  = (string)pick($data, ['labelBanner','template.label_banner'], $labelAtual);
    $stmt->close();

    if ($tplRow) {
        $template_id = (int)$tplRow['id'];
        $cores_id    = (int)$tplRow['cores_id'];
    } else {
        // Se não existir template, cria (pra não quebrar seu editar_osc.php)
        // e garante cores também
        $cores_id = $osc_id;

        $stmt = $conn->prepare("INSERT IGNORE INTO cores (id_cores, cor1, cor2, cor3, cor4, cor5) VALUES (?, '#ffffff', '#000000', '#cccccc', '#999999', '#000000')");
        $stmt->bind_param("i", $cores_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO template_web (osc_id, cores_id, logo_simples, logo_completa, banner1, banner2, banner3, label_banner) VALUES (?, ?, '', '', '', '', '', '')");
        $stmt->bind_param("ii", $osc_id, $cores_id);
        $stmt->execute();
        $template_id = $stmt->insert_id;
        $stmt->close();
    }

    // ============================================
    // 1) UPDATE OSC
    // ============================================
    $nomeOsc           = (string)pick($data, ['nomeOsc'], '');
    $razaoSocial       = (string)pick($data, ['razaoSocial'], '');
    $nomeFantasia      = (string)pick($data, ['nomeFantasia'], '');
    $sigla             = (string)pick($data, ['sigla'], '');
    $situacaoCadastral = (string)pick($data, ['situacaoCadastral'], '');
    $anoCNPJ           = (string)pick($data, ['anoCNPJ'], '');
    $anoFundacao       = (string)pick($data, ['anoFundacao'], '');
    $responsavelLegal  = (string)pick($data, ['responsavelLegal'], '');

    $missao   = (string)pick($data, ['missao'], '');
    $visao    = (string)pick($data, ['visao'], '');
    $valores  = (string)pick($data, ['valores'], '');
    $historia = (string)pick($data, ['historia'], '');
    $oQueFaz  = (string)pick($data, ['oQueFaz'], '');

    $cnpj      = (string)pick($data, ['cnpj'], '');
    $telefone  = (string)pick($data, ['telefone'], '');
    $email     = (string)pick($data, ['email'], '');
    $instagram = (string)pick($data, ['instagram'], '');
    $status    = (string)pick($data, ['status'], '');

    $stmt = $conn->prepare("
        UPDATE osc SET
            nome               = ?,
            razao_social       = ?,
            nome_fantasia      = ?,
            sigla              = ?,
            situacao_cadastral = ?,
            ano_cnpj           = ?,
            ano_fundacao       = ?,
            responsavel        = ?,
            missao             = ?,
            visao              = ?,
            valores            = ?,
            historia           = ?,
            oque_faz           = ?,
            cnpj               = ?,
            telefone           = ?,
            email              = ?,
            instagram          = ?,
            status             = ?
        WHERE id = ?
    ");
    $stmt->bind_param(
        "ssssssssssssssssssi",
        $nomeOsc,
        $razaoSocial,
        $nomeFantasia,
        $sigla,
        $situacaoCadastral,
        $anoCNPJ,
        $anoFundacao,
        $responsavelLegal,
        $missao,
        $visao,
        $valores,
        $historia,
        $oQueFaz,
        $cnpj,
        $telefone,
        $email,
        $instagram,
        $status,
        $osc_id
    );
    if (!$stmt->execute()) throw new Exception("Erro ao atualizar OSC: " . $stmt->error);
    $stmt->close();

    // ============================================
    // 2) UPDATE IMÓVEL
    // ============================================
    $cep       = (string)pick($data, ['imovel.cep', 'cep'], '');
    $cidade    = (string)pick($data, ['imovel.cidade', 'cidade'], '');
    $bairro    = (string)pick($data, ['imovel.bairro', 'bairro'], '');
    $logradouro= (string)pick($data, ['imovel.logradouro', 'logradouro'], '');
    $numero    = (string)pick($data, ['imovel.numero', 'numero'], '');
    $situacao  = (string)pick($data, ['imovel.situacao', 'situacaoImovel'], '');

    // garante que exista registro
    $stmt = $conn->prepare("SELECT id FROM imovel WHERE osc_id = ? LIMIT 1");
    $stmt->bind_param("i", $osc_id);
    $stmt->execute();
    $imv = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($imv) {
        $stmt = $conn->prepare("
            UPDATE imovel SET cep=?, cidade=?, bairro=?, logradouro=?, numero=?, situacao=?
            WHERE osc_id=?
        ");
        $stmt->bind_param("ssssssi", $cep, $cidade, $bairro, $logradouro, $numero, $situacao, $osc_id);
        if (!$stmt->execute()) throw new Exception("Erro ao atualizar imóvel: " . $stmt->error);
        $stmt->close();
    } else {
        $stmt = $conn->prepare("
            INSERT INTO imovel (osc_id, cep, cidade, bairro, logradouro, numero, situacao)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issssss", $osc_id, $cep, $cidade, $bairro, $logradouro, $numero, $situacao);
        if (!$stmt->execute()) throw new Exception("Erro ao inserir imóvel: " . $stmt->error);
        $stmt->close();
    }

    // ============================================
    // 3) ATIVIDADES (zera e recria)
    // ============================================
    if (isset($data['atividades'])) {
        $atividades = normalizarLista($data['atividades']);

        $stmt = $conn->prepare("DELETE FROM osc_atividade WHERE osc_id = ?");
        $stmt->bind_param("i", $osc_id);
        if (!$stmt->execute()) throw new Exception("Erro ao apagar atividades: " . $stmt->error);
        $stmt->close();

        $stmt = $conn->prepare("
            INSERT INTO osc_atividade (osc_id, cnae, area_atuacao, subarea)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($atividades as $atv) {
            if (!is_array($atv)) continue;

            $cnae    = (string)($atv['cnae'] ?? '');
            $area    = (string)($atv['area'] ?? '');
            $subarea = (string)($atv['subarea'] ?? '');

            if ($cnae === '' && $area === '') continue;

            $stmt->bind_param("isss", $osc_id, $cnae, $area, $subarea);
            if (!$stmt->execute()) throw new Exception("Erro ao inserir atividade: " . $stmt->error);
        }
        $stmt->close();
    }

    // ============================================
    // 4) ENVOLVIDOS (zera e recria)
    // ============================================
    if (isset($data['envolvidos'])) {
        $envolvidos = normalizarLista($data['envolvidos']);

        $stmt = $conn->prepare("DELETE FROM envolvido_osc WHERE osc_id = ?");
        $stmt->bind_param("i", $osc_id);
        if (!$stmt->execute()) throw new Exception("Erro ao apagar envolvidos: " . $stmt->error);
        $stmt->close();

        $stmt = $conn->prepare("
            INSERT INTO envolvido_osc (osc_id, nome, telefone, email, funcao, foto)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $destDirAbs = __DIR__ . '/assets/oscs/osc-' . $osc_id . '/envolvidos';

        foreach ($envolvidos as $i => $env) {
            if (!is_array($env)) continue;

            $nome     = (string)($env['nome'] ?? '');
            $telefone = (string)($env['telefone'] ?? '');
            $emailEnv = (string)($env['email'] ?? '');
            $funcao   = (string)($env['funcao'] ?? '');

            // Foto atual (vem do JSON quando é existente)
            $fotoAtual = (string)($env['foto'] ?? '');

            // Se veio arquivo novo "fotoEnvolvido_{$i}", salva e substitui
            $campoFile = 'fotoEnvolvido_' . $i;
            $foto = salvarFotoEnvolvidoSeEnviada($campoFile, $destDirAbs, 'envolvido', $fotoAtual, $osc_id);

            if ($nome === '' && $funcao === '') continue;

            $stmt->bind_param("isssss", $osc_id, $nome, $telefone, $emailEnv, $funcao, $foto);
            if (!$stmt->execute()) throw new Exception("Erro ao inserir envolvido: " . $stmt->error);
        }

        $stmt->close();
    }

    // ============================================
    // 5) CORES (com cor5)
    // ============================================
    $bg  = (string)pick($data, ['cores.bg'], '#ffffff');
    $sec = (string)pick($data, ['cores.sec'], '#000000');
    $ter = (string)pick($data, ['cores.ter'], '#cccccc');
    $qua = (string)pick($data, ['cores.qua'], '#999999');

    // cor5 pode vir como fon (novo), ou cor5 (alguma variação)
    $fon = (string)pick($data, ['cores.fon', 'cores.cor5'], '#000000');

    $stmt = $conn->prepare("
        UPDATE cores SET cor1=?, cor2=?, cor3=?, cor4=?, cor5=?
        WHERE id_cores=?
    ");
    $stmt->bind_param("sssssi", $bg, $sec, $ter, $qua, $fon, $cores_id);
    if (!$stmt->execute()) throw new Exception("Erro ao atualizar cores: " . $stmt->error);
    $stmt->close();

    // ============================================
    // 6) TEMPLATE (logos + banners + label)
    // ============================================

    $arquivosParaExcluir = [];

    // 6.1) Começa do que já existe no BD
    $logoSimples  = $logoSimplesAtual;
    $logoCompleta = $logoCompletaAtual;
    $banner1      = $banner1Atual;
    $banner2      = $banner2Atual;
    $banner3      = $banner3Atual;

    // 6.2) Label pode vir do form (texto), então pega com fallback correto
    $labelBanner = (string)pick($data, ['labelBanner','template.label_banner'], $labelAtual);

    // 6.3) Se enviou arquivos, substitui e salva no disco
    $destDirAbs = __DIR__ . '/assets/oscs/osc-' . $osc_id . '/imagens';

    $logoSimples  = salvarImagemSeEnviada('logoSimples',  $destDirAbs, 'logo-simples',  $logoSimples,  $osc_id, $arquivosParaExcluir);
    $logoCompleta = salvarImagemSeEnviada('logoCompleta', $destDirAbs, 'logo-completa', $logoCompleta, $osc_id, $arquivosParaExcluir);
    $banner1      = salvarImagemSeEnviada('banner1',      $destDirAbs, 'banner1',       $banner1,      $osc_id, $arquivosParaExcluir);
    $banner2      = salvarImagemSeEnviada('banner2',      $destDirAbs, 'banner2',       $banner2,      $osc_id, $arquivosParaExcluir);
    $banner3      = salvarImagemSeEnviada('banner3',      $destDirAbs, 'banner3',       $banner3,      $osc_id, $arquivosParaExcluir);

    // 6.4) Atualiza BD
    $stmt = $conn->prepare("
        UPDATE template_web SET
            logo_simples=?,
            logo_completa=?,
            banner1=?,
            banner2=?,
            banner3=?,
            label_banner=?
        WHERE id=?
    ");
    $stmt->bind_param("ssssssi", $logoSimples, $logoCompleta, $banner1, $banner2, $banner3, $labelBanner, $template_id);
    if (!$stmt->execute()) throw new Exception("Erro ao atualizar template_web: " . $stmt->error);
    $stmt->close();

    // ============================================
    // FINAL
    // ============================================
    mysqli_commit($conn);

    foreach ($arquivosParaExcluir as $abs) {
        if (is_file($abs)) {
            @unlink($abs);
        }
    }

    echo json_encode([
        'success' => true,
        'osc_id' => $osc_id,
        'cores_id' => $cores_id,
        'template_id' => $template_id
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    mysqli_close($conn);
}
