<?php

require_once 'conexao.php';

const CAMINHO = 'assets/oscs/assocest/';  // puxar do jwt
$id_osc = 4;  // puxar do jwt

if (isset($_FILES['arquivo'])) {
    $arquivo = $_FILES['arquivo'];

    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        die("Erro no upload do arquivo.");
    }

    if (mime_content_type($arquivo['tmp_name']) !== "application/pdf") {
        die("Por favor, envie um arquivo PDF.");
    }

    $pastaDestino = CAMINHO;

    if (!file_exists($pastaDestino)) {
        mkdir($pastaDestino, 0777, true);
    }

    $nomeArquivo = uniqid() . "-" . basename($arquivo['name']);

    $caminhoCompleto = $pastaDestino . $nomeArquivo;

    if (move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
        $sql = "INSERT INTO documento (id_osc, documento) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $id_osc, $caminhoCompleto);

        if ($stmt->execute()) {
            echo "Arquivo enviado com sucesso!<br>";
            echo "Caminho salvo no banco: " . $caminhoCompleto;
        } else {
            echo "Erro ao salvar no banco.";
        }

    } else {
        echo "Erro ao mover o arquivo.";
    }

} else {
    echo "Nenhum arquivo ou nome enviado.";
}
?>
