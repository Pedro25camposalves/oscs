<?php
// ajax_atualizar_evento.php

$TIPOS_PERMITIDOS = ['OSC_MASTER'];
$RESPOSTA_JSON    = true;

require 'autenticacao.php';
require 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

function json_fail(string $msg, int $http = 400): void {
    http_response_code($http);
    echo json_encode(['success' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

function only_digits(?string $v): string {
    return preg_replace('/\D+/', '', (string)$v);
}

function is_valid_date(?string $d): bool {
    if (!$d) return false;
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

function fs_path_from_url(string $url): string {
    // Converte "assets/..." em caminho absoluto no servidor
    $root = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
    $url  = ltrim($url, '/');
    return $root . '/' . $url;
}

function ensure_dir(string $dir): void {
    if (is_dir($dir)) return;
    if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
        json_fail('Falha ao criar diretório no servidor: ' . $dir, 500);
    }
}

function safe_unlink(?string $absPath): void {
    if (!$absPath) return;
    if (@is_file($absPath)) {
        @unlink($absPath);
    }
}

function ext_from_name(string $name): string {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    return preg_replace('/[^a-z0-9]+/', '', $ext);
}

function handle_image_upload(string $fieldName, string $destDirFs, string $destBaseUrl, string $prefix): ?string {
    if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) return null;
    $f = $_FILES[$fieldName];
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        json_fail('Falha no upload da imagem (' . $fieldName . ').', 400);
    }

    $tmp  = $f['tmp_name'] ?? '';
    $name = $f['name'] ?? 'arquivo';
    $ext  = ext_from_name($name);
    if (!$ext) $ext = 'bin';

    // Nome único
    $stamp = date('YmdHis');
    $rand  = bin2hex(random_bytes(4));
    $fname = $prefix . '-' . $stamp . '-' . $rand . '.' . $ext;

    ensure_dir($destDirFs);
    $destFs = rtrim($destDirFs, '/') . '/' . $fname;
    if (!@move_uploaded_file($tmp, $destFs)) {
        json_fail('Não foi possível salvar a imagem no servidor.', 500);
    }

    return rtrim($destBaseUrl, '/') . '/' . $fname;
}

function read_json_field(string $key): array {
    $raw = $_POST[$key] ?? '';
    if ($raw === '' || $raw === null) return [];
    $arr = json_decode((string)$raw, true);
    if (!is_array($arr)) return [];
    return $arr;
}

// -------- identifica usuário/OSC --------
$usuarioId = $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;
if (!$usuarioId) json_fail('Sessão inválida. Faça login novamente.', 401);

$oscId = $_POST['id_osc'] ?? ($_SESSION['osc_id'] ?? null);
$oscId = is_numeric($oscId) ? (int)$oscId : 0;

if (!$oscId) {
    // tenta pelo usuario
    try {
        $st = $conn->prepare("SELECT osc_id FROM usuario WHERE id = ? LIMIT 1");
        $st->bind_param('i', $usuarioId);
        $st->execute();
        $res = $st->get_result()->fetch_assoc();
        $oscId = (int)($res['osc_id'] ?? 0);
        $st->close();
    } catch (Throwable $e) {
        $oscId = 0;
    }
}

if (!$oscId) {
    // fallback
    try {
        $st = $conn->prepare("SELECT osc_id FROM usuario_osc WHERE usuario_id = ? LIMIT 1");
        $st->bind_param('i', $usuarioId);
        $st->execute();
        $res = $st->get_result()->fetch_assoc();
        $oscId = (int)($res['osc_id'] ?? 0);
        $st->close();
    } catch (Throwable $e) {
        $oscId = 0;
    }
}

if (!$oscId) json_fail('Este usuário não possui OSC vinculada.', 403);

// -------- valida evento + permissão --------
$eventoId = $_POST['evento_oficina_id'] ?? $_POST['id'] ?? null;
if (!is_numeric($eventoId)) json_fail('Evento/Oficina inválido.');
$eventoId = (int)$eventoId;

$st = $conn->prepare("SELECT e.id, e.projeto_id, e.img_capa
                         FROM evento_oficina e
                         JOIN projeto p ON p.id = e.projeto_id
                        WHERE e.id = ? AND p.osc_id = ?
                        LIMIT 1");
$st->bind_param('ii', $eventoId, $oscId);
$st->execute();
$evRow = $st->get_result()->fetch_assoc();
$st->close();

if (!$evRow) json_fail('Evento/Oficina não encontrado ou sem permissão.', 404);

$projetoId   = (int)($evRow['projeto_id'] ?? 0);
$oldImgCapa  = (string)($evRow['img_capa'] ?? '');

// -------- campos principais --------
$tipo       = trim((string)($_POST['tipo'] ?? ''));
$status     = trim((string)($_POST['status'] ?? ''));
$nome       = trim((string)($_POST['nome'] ?? ''));
$descricao  = (string)($_POST['descricao'] ?? '');
$dataInicio = trim((string)($_POST['data_inicio'] ?? ''));
$dataFim    = trim((string)($_POST['data_fim'] ?? ''));
$paiIdRaw   = trim((string)($_POST['pai_id'] ?? ''));

if ($nome === '') json_fail('Informe o nome do Evento/Oficina.');
if ($tipo !== 'EVENTO' && $tipo !== 'OFICINA') json_fail('Tipo inválido.');

$statusPermitidos = ['PENDENTE','PLANEJAMENTO','EXECUCAO','ENCERRADO'];
if (!in_array($status, $statusPermitidos, true)) json_fail('Status inválido.');

if ($dataInicio !== '' && !is_valid_date($dataInicio)) json_fail('Data início inválida.');
if ($dataFim !== '' && !is_valid_date($dataFim)) json_fail('Data fim inválida.');

$paiId = null;
if ($paiIdRaw !== '') {
    if (!is_numeric($paiIdRaw)) json_fail('Evento/Oficina pai inválido.');
    $paiId = (int)$paiIdRaw;
    if ($paiId === $eventoId) json_fail('O Evento/Oficina pai não pode ser ele mesmo.');
    // garante que o pai é do mesmo projeto
    $st = $conn->prepare("SELECT id FROM evento_oficina WHERE id = ? AND projeto_id = ? LIMIT 1");
    $st->bind_param('ii', $paiId, $projetoId);
    $st->execute();
    $okPai = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$okPai) json_fail('Evento/Oficina pai não encontrado no mesmo projeto.');
}

// -------- diretórios --------
$baseUrlProjeto = "assets/oscs/osc-{$oscId}/projetos/projeto-{$projetoId}";
$baseUrlEvento  = $baseUrlProjeto . "/eventos/evento-{$eventoId}";
$imgUrlBase     = $baseUrlEvento . "/imagens";

$imgDirFs = fs_path_from_url($imgUrlBase);
ensure_dir($imgDirFs);

// -------- upload imagem de capa --------
$newImgCapa = handle_image_upload('img_capa', $imgDirFs, $imgUrlBase, 'capa');

// -------- envolvidos + endereços (JSON) --------
$envolvidos = read_json_field('envolvidos');
$enderecos  = read_json_field('enderecos');

// -------- transação --------
$conn->begin_transaction();

try {
    // 1) atualiza evento
    $imgCapaFinal = $newImgCapa ?: ($oldImgCapa ?: null);
    $sql = "UPDATE evento_oficina
               SET tipo = ?,
                   nome = ?,
                   descricao = ?,
                   data_inicio = ?,
                   data_fim = ?,
                   status = ?,
                   pai_id = ?,
                   img_capa = ?
             WHERE id = ? AND projeto_id = ?";
    $st = $conn->prepare($sql);
    $paiIdBind = $paiId; // pode ser null
    $imgBind   = $imgCapaFinal; // pode ser null
    $diBind    = ($dataInicio === '') ? null : $dataInicio;
    $dfBind    = ($dataFim === '') ? null : $dataFim;
    // pai_id pode ser NULL (seu banco permite). Para garantir NULL, bindamos como string.
    $paiBindStr = ($paiIdBind === null) ? null : (string)$paiIdBind;
    $st->bind_param('ssssssssii', $tipo, $nome, $descricao, $diBind, $dfBind, $status, $paiBindStr, $imgBind, $eventoId, $projetoId);
    if (!$st->execute()) json_fail('Falha ao atualizar Evento/Oficina.', 500);
    $st->close();

    // 2) atualiza envolvidos do evento (limpa e reinsere)
    $st = $conn->prepare("DELETE FROM envolvido_evento_oficina WHERE evento_oficina_id = ? AND projeto_id = ?");
    $st->bind_param('ii', $eventoId, $projetoId);
    $st->execute();
    $st->close();

    $exist = (is_array($envolvidos['existentes'] ?? null)) ? $envolvidos['existentes'] : [];
    $novos = (is_array($envolvidos['novos'] ?? null)) ? $envolvidos['novos'] : [];

    // insere existentes (já precisam existir no envolvido_projeto)
    if ($exist) {
        $stChk = $conn->prepare("SELECT 1 FROM envolvido_projeto WHERE envolvido_osc_id = ? AND projeto_id = ? LIMIT 1");
        $stIns = $conn->prepare("INSERT INTO envolvido_evento_oficina (envolvido_osc_id, projeto_id, evento_oficina_id, funcao)
                                 VALUES (?,?,?,?)");
        foreach ($exist as $e) {
            $envId = (int)($e['envolvido_osc_id'] ?? 0);
            $func  = trim((string)($e['funcao'] ?? ''));
            if ($envId <= 0 || $func === '') continue;

            $stChk->bind_param('ii', $envId, $projetoId);
            $stChk->execute();
            $ok = $stChk->get_result()->fetch_assoc();
            if (!$ok) {
                json_fail('Envolvido selecionado não pertence ao projeto.', 400);
            }

            $stIns->bind_param('iiis', $envId, $projetoId, $eventoId, $func);
            if (!$stIns->execute()) json_fail('Falha ao salvar envolvidos do evento.', 500);
        }
        $stChk->close();
        $stIns->close();
    }

    // insere novos (cria envolvido_osc + envolvido_projeto + vínculo no evento)
    if ($novos) {
        $envRootUrl = "assets/oscs/osc-{$oscId}/envolvidos";
        $envRootFs  = fs_path_from_url($envRootUrl);
        ensure_dir($envRootFs);

        $stInsOsc = $conn->prepare("INSERT INTO envolvido_osc (osc_id, nome, telefone, email, foto, funcao)
                                    VALUES (?,?,?,?,?,?)");
        $stInsProj= $conn->prepare("INSERT INTO envolvido_projeto (envolvido_osc_id, projeto_id, funcao)
                                    VALUES (?,?,?)");
        $stInsEv  = $conn->prepare("INSERT INTO envolvido_evento_oficina (envolvido_osc_id, projeto_id, evento_oficina_id, funcao)
                                    VALUES (?,?,?,?)");

        foreach ($novos as $e) {
            $nNome  = trim((string)($e['nome'] ?? ''));
            $nTel   = only_digits($e['telefone'] ?? '');
            $nEmail = trim((string)($e['email'] ?? ''));
            $fEv    = trim((string)($e['funcao_evento'] ?? ''));
            $fProj  = trim((string)($e['funcao_projeto'] ?? 'PARTICIPANTE'));
            $fotoKey= (string)($e['foto_key'] ?? '');

            if ($nNome === '' || $fEv === '') continue;
            if ($fProj === '') $fProj = 'PARTICIPANTE';

            // foto (opcional)
            $fotoUrl = null;
            if ($fotoKey && isset($_FILES[$fotoKey])) {
                $envDirUrl = $envRootUrl . '/tmp';
                $envDirFs  = fs_path_from_url($envDirUrl);
                ensure_dir($envDirFs);
                // primeiro salva em tmp, depois move para pasta definitiva do ID (quando souber)
                $tmpUrl = handle_image_upload($fotoKey, $envDirFs, $envDirUrl, 'foto');
                $fotoUrl = $tmpUrl; // será movida depois
            }

            $stInsOsc->bind_param('isssss', $oscId, $nNome, $nTel, $nEmail, $fotoUrl, $fProj);
            if (!$stInsOsc->execute()) json_fail('Falha ao criar envolvido (OSC).', 500);
            $newEnvId = (int)$conn->insert_id;

            // se salvou foto em tmp, move para pasta definitiva do envolvido
            if ($fotoUrl) {
                $srcFs = fs_path_from_url($fotoUrl);
                $envFinalUrl = $envRootUrl . "/envolvido-{$newEnvId}/imagens";
                $envFinalFs  = fs_path_from_url($envFinalUrl);
                ensure_dir($envFinalFs);

                $basename = basename($fotoUrl);
                $destFs = rtrim($envFinalFs, '/') . '/' . $basename;
                if (@is_file($srcFs)) {
                    @rename($srcFs, $destFs);
                }
                $fotoUrlFinal = rtrim($envFinalUrl, '/') . '/' . $basename;

                $stUp = $conn->prepare("UPDATE envolvido_osc SET foto = ? WHERE id = ? AND osc_id = ?");
                $stUp->bind_param('sii', $fotoUrlFinal, $newEnvId, $oscId);
                $stUp->execute();
                $stUp->close();
            }

            $stInsProj->bind_param('iis', $newEnvId, $projetoId, $fProj);
            if (!$stInsProj->execute()) json_fail('Falha ao vincular envolvido ao projeto.', 500);

            $stInsEv->bind_param('iiis', $newEnvId, $projetoId, $eventoId, $fEv);
            if (!$stInsEv->execute()) json_fail('Falha ao vincular envolvido ao evento.', 500);
        }

        $stInsOsc->close();
        $stInsProj->close();
        $stInsEv->close();
    }

    // 3) endereços do evento (limpa e reinsere)
    $st = $conn->prepare("DELETE FROM endereco_evento_oficina WHERE evento_oficina_id = ?");
    $st->bind_param('i', $eventoId);
    $st->execute();
    $st->close();

    $endExist = (is_array($enderecos['existentes'] ?? null)) ? $enderecos['existentes'] : [];
    $endNovos = (is_array($enderecos['novos'] ?? null)) ? $enderecos['novos'] : [];

    $stUpdEnd = $conn->prepare("UPDATE endereco SET descricao = ?, cep = ?, cidade = ?, logradouro = ?, bairro = ?, numero = ?, complemento = ? WHERE id = ?");
    $stInsEnd = $conn->prepare("INSERT INTO endereco (descricao, cep, cidade, logradouro, bairro, numero, complemento) VALUES (?,?,?,?,?,?,?)");
    $stInsRel = $conn->prepare("INSERT INTO endereco_evento_oficina (endereco_id, evento_oficina_id, principal) VALUES (?,?,?)");

    $principalJa = false;
    $todos = [];
    foreach ($endExist as $e) $todos[] = ['mode' => 'exist', 'data' => $e];
    foreach ($endNovos as $e) $todos[] = ['mode' => 'novo',  'data' => $e];

    foreach ($todos as $item) {
        $e = $item['data'];
        $desc = trim((string)($e['descricao'] ?? ''));
        $cep  = only_digits($e['cep'] ?? '');
        $cid  = trim((string)($e['cidade'] ?? ''));
        $log  = trim((string)($e['logradouro'] ?? ''));
        $bai  = trim((string)($e['bairro'] ?? ''));
        $num  = trim((string)($e['numero'] ?? ''));
        $comp = trim((string)($e['complemento'] ?? ''));
        $pri  = (int)($e['principal'] ?? 0) === 1;

        if ($cep === '' || $cid === '' || $log === '' || $bai === '' || $num === '') {
            // campos mínimos (mantém o padrão do seu modal)
            continue;
        }

        $endId = 0;
        if ($item['mode'] === 'exist') {
            $endId = (int)($e['endereco_id'] ?? 0);
            if ($endId <= 0) continue;
            $stUpdEnd->bind_param('sssssssi', $desc, $cep, $cid, $log, $bai, $num, $comp, $endId);
            $stUpdEnd->execute();
        } else {
            $stInsEnd->bind_param('sssssss', $desc, $cep, $cid, $log, $bai, $num, $comp);
            if (!$stInsEnd->execute()) json_fail('Falha ao criar endereço.', 500);
            $endId = (int)$conn->insert_id;
        }

        if ($pri && !$principalJa) {
            $principalJa = true;
        } else {
            $pri = false;
        }

        $priInt = $pri ? 1 : 0;
        $stInsRel->bind_param('iii', $endId, $eventoId, $priInt);
        if (!$stInsRel->execute()) json_fail('Falha ao vincular endereço ao evento.', 500);
    }

    $stUpdEnd->close();
    $stInsEnd->close();
    $stInsRel->close();

    $conn->commit();

    // apaga capa antiga após commit (evita perder arquivo se der rollback)
    if ($newImgCapa && $oldImgCapa && $oldImgCapa !== $newImgCapa) {
        safe_unlink(fs_path_from_url($oldImgCapa));
    }

    echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    $conn->rollback();

    // se subiu nova imagem e deu ruim, apaga a nova também
    if ($newImgCapa) {
        safe_unlink(fs_path_from_url($newImgCapa));
    }
    json_fail('Falha ao salvar: ' . $e->getMessage(), 500);
}
