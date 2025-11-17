# Guia de Edição de OSCs - Sistema de Formulário

## Mudanças Implementadas

### 1. **editar_osc.php** - Formulário universal (criar/editar)
O arquivo agora funciona em dois modos:

#### Modo Criação
- Acesse: `editar_osc.php` (sem parâmetros)
- O formulário abre em branco
- Botão exibe: **"Salvar informações da OSC"**
- Submissão vai para: `ajax_criar_osc.php`

#### Modo Edição
- Acesse: `editar_osc.php?id=123` (com ID da OSC)
- O formulário **carrega automaticamente** os dados do banco
- Botão exibe: **"Atualizar informações da OSC"**
- Submissão vai para: `ajax_atualizar_osc.php`

### 2. **Novos Endpoints PHP**

#### `ajax_obter_osc.php`
- **Método:** GET
- **Parâmetro:** `?id=123`
- **Resposta:** JSON com dados completos da OSC
```json
{
  "success": true,
  "data": {
    "id": 123,
    "nomeFantasia": "ASSOCEST",
    "email": "contato@...",
    ...
  }
}
```

#### `ajax_atualizar_osc.php`
- **Método:** POST
- **Body:** JSON com todos os dados (incluindo `id`)
- **Resposta:** JSON com resultado da operação
```json
{
  "success": true,
  "message": "OSC atualizada com sucesso",
  "id": 123
}
```

---

## Como Integrar com Seu Banco de Dados

### 1. **Em `ajax_obter_osc.php`**

Substitua a simulação de teste por:

```php
<?php
require_once 'config/database.php'; // sua conexão PDO

$id = (int) $_GET['id'];

try {
    $stmt = $pdo->prepare('SELECT * FROM oscs WHERE id = ?');
    $stmt->execute([$id]);
    $osc = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($osc) {
        // Se os dados estão em JSON (recomendado)
        if (isset($osc['dados_json']) && is_string($osc['dados_json'])) {
            $parsed = json_decode($osc['dados_json'], true);
            if ($parsed) {
                $osc = array_merge($osc, $parsed);
            }
        }
        
        echo json_encode(['success' => true, 'data' => $osc]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'OSC não encontrada']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
```

### 2. **Em `ajax_atualizar_osc.php`**

Substitua a simulação por:

```php
<?php
require_once 'config/database.php'; // sua conexão PDO

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID não fornecido']);
    exit;
}

$id = (int) $input['id'];

try {
    // Armazenar dados como JSON
    $dados_json = json_encode($input);
    
    $stmt = $pdo->prepare('UPDATE oscs SET dados_json = ?, atualizado_em = NOW() WHERE id = ?');
    $resultado = $stmt->execute([$dados_json, $id]);
    
    if ($resultado) {
        echo json_encode([
            'success' => true,
            'message' => 'OSC atualizada com sucesso',
            'id' => $id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro ao atualizar']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
```

---

## Estrutura de Tabela Recomendada

```sql
CREATE TABLE oscs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nomeFantasia VARCHAR(255),
    email VARCHAR(255),
    telefone VARCHAR(20),
    dados_json LONGTEXT COMMENT 'JSON com todos os dados do formulário',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## Fluxo de Funcionamento

### Criação (POST)
```
1. Usuário acessa: editar_osc.php
2. Carrega formulário vazio
3. Preenche dados
4. Clica "Salvar informações da OSC"
5. JavaScript envia POST → ajax_criar_osc.php
6. PHP insere na tabela oscs
7. Retorna sucesso com novo ID
```

### Edição (GET + POST)
```
1. Usuário acessa: editar_osc.php?id=123
2. JavaScript captura ID da URL
3. Faz GET → ajax_obter_osc.php?id=123
4. Preenche campos automaticamente
5. Botão muda para "Atualizar informações da OSC"
6. Usuário edita dados
7. Clica "Atualizar..."
8. JavaScript envia POST → ajax_atualizar_osc.php
9. PHP atualiza registro
10. Retorna sucesso
```

---

## Campos Suportados

O formulário captura os seguintes grupos de dados:

### Configurações Gerais
- `labelBanner`, `cores` (bg, sec, ter, qua)
- Imagens: logos, banners

### Sobre Nós
- `missao`, `visao`, `valores`, `historia`
- `cnae`, `area`, `subarea`

### Transparência
- `recursos`, `nomeFantasia`, `sigla`
- `situacaoCadastral`, `endereco`, `situacaoImovel`
- `anoCNPJ`, `anoFundacao`, `responsavelLegal`
- `email`, `oQueFaz`, `abreviacao`
- `cnpj`, `razao_social`, `telefone`, `instagram`, `status`

### Diretores
- Array com: `nome`, `func` (função), `foto` (base64)

---

## Testes

### Teste de Criação
```bash
curl -X POST http://localhost:8000/editar_osc.php \
  -H "Content-Type: application/json" \
  -d '{"nomeFantasia":"Test OSC","email":"test@example.com"}'
```

### Teste de Obtenção
```bash
curl http://localhost:8000/ajax_obter_osc.php?id=1
```

### Teste de Atualização
```bash
curl -X POST http://localhost:8000/ajax_atualizar_osc.php \
  -H "Content-Type: application/json" \
  -d '{"id":1,"nomeFantasia":"Updated Name","email":"new@example.com"}'
```

---

## Próximos Passos

1. ✅ Implementar queries reais nos arquivos `ajax_*.php`
2. ✅ Testar com dados do seu banco
3. ✅ Adicionar validação de entrada (sanitize/validate)
4. ✅ Implementar autenticação (verificar se usuário tem permissão)
5. ✅ Adicionar logging de mudanças (quem editou e quando)

---

**Dúvidas?** Verifique o console do navegador (F12) para mensagens de erro detalhadas.
