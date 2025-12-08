<?php

require_once 'conexao.php';

$id_osc = $_POST['id_osc'] ?? null;
$id_projeto = $_POST['id_projeto'] ?? null;
$tipo = $_POST['tipo'] ?? null;

if (!$id_osc) {
    die("ID da OSC não informado.");
}

if (!$tipo) {
    die("Tipo do documento não informado.");
}

if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
    die("Erro no upload do arquivo.");
}

$arquivo = $_FILES['arquivo'];

if (mime_content_type($arquivo['tmp_name']) !== "application/pdf") {
        die("Por favor, envie um arquivo PDF.");
}

$caminhaBase = "assets/oscs/osc-$id_osc/";

if ($id_projeto) {
    $pastaDestino = $caminhaBase . "projetos/projeto-$id_projeto/documentos/";
} else {
    $pastaDestino = $caminhaBase . "documentos/";
}

if (!is_dir($pastaDestino)) {
    die("Diretório de destino não encontrado. Verifique o cadastro da OSC/projeto.");
}

$nomeArquivo = uniqid() . "-" . basename($arquivo['name']);

$caminhoCompleto = $pastaDestino . $nomeArquivo;

if (!move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
    die("Erro ao mover o arquivo.");
}

if ($id_projeto) {
    $sql = "INSERT INTO documento (osc_id, projeto_id, tipo, documento) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiss", $id_osc, $id_projeto, $tipo, $caminhoCompleto);
} else {
    $sql = "INSERT INTO documento (osc_id, tipo, documento) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $id_osc, $tipo, $caminhoCompleto);
}

if ($stmt->execute()) {
    echo json_encode([
        "status" => "ok",
        "mensagem" => "Arquivo enviado com sucesso!",
        "tipo" => $tipo,
        "caminho" => $caminhoCompleto
    ]);
} else {
    echo json_encode([
        "status" => "erro",
        "mensagem" => "Erro ao salvar no banco."
    ]);
}
?>
