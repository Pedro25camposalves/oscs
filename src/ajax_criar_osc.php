<?php
    $TIPOS_PERMITIDOS = ['OSC_TECH_ADMIN']; // só OscTech admin pode criar OSC
    $RESPOSTA_JSON    = true;               // endpoint retorna JSON
    require 'autenticacao.php';

    include 'conexao.php';

    // Cria a estrutura de diretórios da OSC:
    function criarDiretoriosOsc(int $oscId): bool
    {
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

    // Move um arquivo de $_FILES para a pasta da OSC
    function moverArquivo(string $fieldName, string $imgDir, string $imgRelBase): ?string
    {
        if (
            !isset($_FILES[$fieldName]) ||
            $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK
        ) {
            return null;
        }

        $originalName = basename($_FILES[$fieldName]['name']);
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $ext = $ext ? ('.' . $ext) : '';

        $fileName = uniqid($fieldName . '_', true) . $ext;

        if (!is_dir($imgDir) && !mkdir($imgDir, 0777, true)) {
            return null;
        }

        $destFull = $imgDir . $fileName;

        if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $destFull)) {
            return $imgRelBase . $fileName; // caminho relativo para gravar no banco
        }

        return null;
    }

    // --- Lê os campos vindos via POST ---
    $nomeOsc           = mysqli_real_escape_string($conn, $_POST['nomeOsc']             ?? '');
    $email             = mysqli_real_escape_string($conn, $_POST['email']               ?? '');
    $razaoSocial       = mysqli_real_escape_string($conn, $_POST['razaoSocial']         ?? '');
    $nomeFantasia      = mysqli_real_escape_string($conn, $_POST['nomeFantasia']        ?? '');
    $sigla             = mysqli_real_escape_string($conn, $_POST['sigla']               ?? '');
    $situacaoCadastral = mysqli_real_escape_string($conn, $_POST['situacaoCadastral']   ?? '');
    $anoCNPJ           = mysqli_real_escape_string($conn, $_POST['anoCNPJ']             ?? '');
    $anoFundacao       = mysqli_real_escape_string($conn, $_POST['anoFundacao']         ?? '');
    $responsavel       = mysqli_real_escape_string($conn, $_POST['responsavelLegal']    ?? '');
    $missao            = mysqli_real_escape_string($conn, $_POST['missao']              ?? '');
    $visao             = mysqli_real_escape_string($conn, $_POST['visao']               ?? '');
    $valores           = mysqli_real_escape_string($conn, $_POST['valores']             ?? '');
    $historia          = mysqli_real_escape_string($conn, $_POST['historia']            ?? '');
    $oQueFaz           = mysqli_real_escape_string($conn, $_POST['oQueFaz']             ?? '');
    $cnpj              = mysqli_real_escape_string($conn, $_POST['cnpj']                ?? '');
    $telefone          = mysqli_real_escape_string($conn, $_POST['telefone']            ?? '');
    $instagram         = mysqli_real_escape_string($conn, $_POST['instagram']           ?? '');
    $status            = mysqli_real_escape_string($conn, $_POST['status']              ?? '');

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

    $osc_id = (int) mysqli_insert_id($conn);

    // Cria os diretórios da OSC
    if (!criarDiretoriosOsc($osc_id)) {
        echo json_encode([
            'success' => false,
            'error' => 'OSC criada no banco, mas falha ao criar diretórios no servidor!'
        ]);
        exit;
    }

    $baseOscDir   = __DIR__ . '/assets/oscs/osc-' . $osc_id;
    $imgDir       = $baseOscDir . '/imagens/';
    $imgRelBase   = 'assets/oscs/osc-' . $osc_id . '/imagens/';

    // --- Salva as ATIVIDADES da OSC (CNAE / Área / Subárea) ---
    $atividadesJson = $_POST['atividades'] ?? '[]';
    $atividades = json_decode($atividadesJson, true);
    if (!is_array($atividades)) {
        $atividades = [];
    }

    foreach ($atividades as $atv) {
        $cnae    = mysqli_real_escape_string($conn, $atv['cnae']   ?? '');
        $area    = mysqli_real_escape_string($conn, $atv['area']   ?? '');
        $subarea = mysqli_real_escape_string($conn, $atv['subarea'] ?? '');

        if ($cnae === '' && $area === '') {
            continue;
        }

        $sql_atividade = "
            INSERT INTO osc_atividade (osc_id, cnae, area_atuacao, subarea)
            VALUES ('$osc_id', '$cnae', '$area', '$subarea')
        ";

        if (!mysqli_query($conn, $sql_atividade)) {
            echo json_encode(['success' => false, 'error' => 'Erro ao salvar as atividade da OSC: ' . mysqli_error($conn)]);
            exit;
        }
    }

    // --- Salva os dados dos envolvidos (atores da OSC) ---
    $envolvidosJson = $_POST['envolvidos'] ?? '[]';
    $envolvidos = json_decode($envolvidosJson, true);

    if (!is_array($envolvidos)) {
        $envolvidos = [];
    }

    $atores_ids = [];
    $atores_osc_ids = [];

    foreach ($envolvidos as $idx => $envolvido) {
        $tipo            = $envolvido['tipo']    ?? 'novo';
        $atorIdExistente = isset($envolvido['ator_id']) ? (int)$envolvido['ator_id'] : 0;

        $nome     = mysqli_real_escape_string($conn, $envolvido['nome']     ?? '');
        $telefone = mysqli_real_escape_string($conn, $envolvido['telefone'] ?? '');
        $email    = mysqli_real_escape_string($conn, $envolvido['email']    ?? '');
        $funcao   = mysqli_real_escape_string($conn, $envolvido['funcao']   ?? '');

        // Decide se usamos ator existente ou criamos um novo
        if ($atorIdExistente > 0 && $tipo === 'existente') {
            // Somente vincula este ator à OSC
            $ator_id = $atorIdExistente;

        } else {
            // Cria um novo ator
            if ($nome === '' && $funcao === '') {
                continue;
            }

            $sql_ator = "
                INSERT INTO ator (nome, telefone, email)
                VALUES ('$nome', '$telefone', '$email')
            ";

            if (!mysqli_query($conn, $sql_ator)) {
                echo json_encode([
                    'success' => false,
                    'error'   => 'Erro ao salvar o ator: ' . mysqli_error($conn)
                ]);
                exit;
            }

            $ator_id = (int) mysqli_insert_id($conn);
            $atores_ids[] = $ator_id;

            // Cria o diretório próprio do ator no servidor
            $atorDir     = __DIR__ . '/assets/atores/ator-' . $ator_id . '/';
            $atorRelBase = 'assets/atores/ator-' . $ator_id . '/';

            $fieldNameFoto = 'fotoEnvolvido_' . $idx;
            $caminhoFotoRel = moverArquivo($fieldNameFoto, $atorDir, $atorRelBase);

            if ($caminhoFotoRel !== null) {
                $caminhoFotoRelSql = mysqli_real_escape_string($conn, $caminhoFotoRel);
                $sql_update_foto = "
                    UPDATE ator
                       SET foto = '$caminhoFotoRelSql'
                     WHERE id = '$ator_id'
                ";
                mysqli_query($conn, $sql_update_foto);
            }
        }

        // Em ambos os casos (novo ou existente), cria o vínculo com a OSC
        $sql_ator_osc = "
            INSERT INTO ator_osc (ator_id, osc_id, funcao)
            VALUES ('$ator_id', '$osc_id', '$funcao')
        ";

        if (!mysqli_query($conn, $sql_ator_osc)) {
            echo json_encode([
                'success' => false,
                'error'   => 'Erro ao salvar a relação ator_osc: ' . mysqli_error($conn)
            ]);
            exit;
        }

        $ator_osc_id = mysqli_insert_id($conn);
        $atores_osc_ids[] = $ator_osc_id;
    }

    // --- Salva os dados do imóvel da OSC ---
    $situacaoImovel = mysqli_real_escape_string($conn, $_POST['situacaoImovel'] ?? '');
    $cep            = mysqli_real_escape_string($conn, $_POST['cep']            ?? '');
    $cidade         = mysqli_real_escape_string($conn, $_POST['cidade']         ?? '');
    $bairro         = mysqli_real_escape_string($conn, $_POST['bairro']         ?? '');
    $logradouro     = mysqli_real_escape_string($conn, $_POST['logradouro']     ?? '');
    $numero         = mysqli_real_escape_string($conn, $_POST['numero']         ?? '');

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
    $cores = $_POST['cores'] ?? [];
    $cor1  = mysqli_real_escape_string($conn, $cores['bg']  ?? '');
    $cor2  = mysqli_real_escape_string($conn, $cores['sec'] ?? '');
    $cor3  = mysqli_real_escape_string($conn, $cores['ter'] ?? '');
    $cor4  = mysqli_real_escape_string($conn, $cores['qua'] ?? '');
    $cor5  = mysqli_real_escape_string($conn, $cores['fon'] ?? '');

    $sql_cores = "
        INSERT INTO cores (osc_id, cor1, cor2, cor3, cor4, cor5)
        VALUES ('$osc_id', '$cor1', '$cor2', '$cor3', '$cor4', '$cor5')
    ";

    if (!mysqli_query($conn, $sql_cores)) {
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar cores: ' . mysqli_error($conn)]);
        exit;
    }

    $cores_id = mysqli_insert_id($conn);

    // --- Salva o template visual (logos e banners) ---
    $logoSimples  = moverArquivo('logoSimples',  $imgDir, $imgRelBase);
    $logoCompleta = moverArquivo('logoCompleta', $imgDir, $imgRelBase);
    $banner1      = moverArquivo('banner1',      $imgDir, $imgRelBase);
    $banner2      = moverArquivo('banner2',      $imgDir, $imgRelBase);
    $banner3      = moverArquivo('banner3',      $imgDir, $imgRelBase);

    $labelBanner  = mysqli_real_escape_string($conn, $_POST['labelBanner'] ?? '');

    $sql_template = "
        INSERT INTO template_web (
            osc_id, descricao, cores_id, logo_simples, logo_completa, banner1, banner2, banner3, label_banner
        ) VALUES (
            '$osc_id', 'Template Padrão', '$cores_id',
            '$logoSimples', '$logoCompleta', '$banner1', '$banner2', '$banner3', '$labelBanner'
        )";

    if (!mysqli_query($conn, $sql_template)) {
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar template: ' . mysqli_error($conn)]);
        exit;
    }

    // --- Retorno completo dos cadastros ---
    echo json_encode([
        'success'       => true,
        'template_id'   => mysqli_insert_id($conn),
        'cores_id'      => $cores_id,
        'osc_id'        => $osc_id,
        'imovel_id'     => $imovel_id,
        'atores_ids'    => $atores_ids,
        'atores_osc_ids'=> $atores_osc_ids
    ]);

    mysqli_close($conn);
