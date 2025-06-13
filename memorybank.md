# MemoryBank - GigaJus System Overview

## Principais Arquivos
- `config.php` - Configurações do banco de dados e constantes do sistema
- `create_tables.sql` - Esquema inicial do banco de dados
- `comunicaapi.py` - Integração com APIs externas
- `includes/notifications_helper.php` - Lógica de integração com API do PJE
- `process_notification.php` - Processamento de notificações
- `test_api_curl.php` - Testes de integração com API externa

## Estrutura do Banco de Dados
```sql
-- Tabela Principal de Processos
CREATE TABLE processos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    numero_processo VARCHAR(255) UNIQUE,
    teor TEXT,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('ativo', 'arquivado') DEFAULT 'ativo'
);

-- Tabela de Clientes (Atualizada com campos de endereço)
CREATE TABLE clientes (
    id_cliente INT NOT NULL AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    cpf_cnpj VARCHAR(20) NOT NULL,
    endereco TEXT,
    cep VARCHAR(10),
    logradouro VARCHAR(255),
    numero VARCHAR(20),
    complemento VARCHAR(255),
    bairro VARCHAR(100),
    cidade VARCHAR(100),
    estado VARCHAR(2),
    telefone VARCHAR(20) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    outros_dados JSON DEFAULT NULL,
    ativo TINYINT(1) DEFAULT '1',
    PRIMARY KEY (id_cliente),
    INDEX idx_cliente_cidade (cidade),
    INDEX idx_cliente_estado (estado),
    INDEX idx_cliente_cep (cep)
);

-- Tabela de Notificações (Atualizada)
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    numero_processo VARCHAR(255),
    classe VARCHAR(100),
    tribunal VARCHAR(10),
    advogados TEXT,
    data_publicacao DATE,
    teor TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    pagina INT DEFAULT 1,
    tamanho_pagina INT DEFAULT 100
);

-- Relacionamento Usuário-Empresa
CREATE TABLE usuario_empresa (
    id_usuario INT,
    id_empresa INT,
    PRIMARY KEY (id_usuario, id_empresa),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id),
    FOREIGN KEY (id_empresa) REFERENCES empresas(id)
);

-- Tabela de Advogados
CREATE TABLE advogados (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_empresa INT,
    nome_advogado VARCHAR(255),
    oab_numero VARCHAR(20),
    oab_uf CHAR(2),
    FOREIGN KEY (id_empresa) REFERENCES empresas(id)
);
```

## Implementação de Segurança
1. **Token JWT**
   - Validade: 1 hora
   - Estrutura:
     ```php
     {
       "sub": "ID do usuário/processo",
       "iat": "Timestamp de emissão",
       "exp": "Timestamp de expiração"
     }
     ```
   - Assinatura: HMAC-SHA256 com chave secreta

2. **Proxy de API**
   - Endereço: 185.72.240.72:7108
   - Autenticação: Basic Auth (checaativos:Proxy2025)

## API de Comunicações Judiciais
**Endpoint Principal**
```
https://comunicaapi.pje.jus.br/api/v1/comunicacao
```

**Parâmetros Principais**
| Parâmetro                  | Tipo   | Obrigatório | Descrição                          |
|----------------------------|--------|-------------|------------------------------------|
| dataDisponibilizacaoInicio | date   | Sim         | Data inicial de busca (YYYY-MM-DD) |
| dataDisponibilizacaoFim    | date   | Sim         | Data final de busca (YYYY-MM-DD)   |
| siglaTribunal              | string | Sim         | Sigla do tribunal (ex: TJCE)       |
| pagina                     | int    | Não         | Número da página (padrão: 1)       |
| tamanhoPagina              | int    | Não         | Itens por página (padrão: 100)     |

**Fluxo de Integração**
1. Gerar token JWT para autenticação
2. Configurar proxy para requisições externas
3. Processar resposta paginada da API
4. Salvar notificações formatadas no banco

## Serviços Ativos
- Monitoramento de logs: `tail -f gigajus.log`
- Sincronização com Google Drive (30min)
- Processamento automático de notificações
- Verificação periódica de token (cron job)

## Padrões de Design e Estilo

### Estrutura de Diretórios
- Arquivos de clientes: `uploads/clientes/Nome do cliente (ID X)/`
- Arquivos CSS: `assets/css/`
- Arquivos JavaScript: `assets/js/`

### Padrões de Cores
- Azul claro (processos): `rgba(52, 152, 219, 0.1)` - `#3498db`
- Rosa claro (atendimentos vinculados): `rgba(231, 76, 60, 0.1)` - `#e74c3c`
- Verde claro (atendimentos não vinculados): `rgba(46, 204, 113, 0.1)` - `#2ecc71`
- Amarelo (arquivos): `rgba(241, 196, 15, 0.1)` - `#f39c12`

### Botões de Ação
- Editar: `<a href="#" class="btn-icon btn-edit" title="Editar"><i class="fas fa-edit"></i></a>`
- Arquivos: `<a href="#" class="btn-icon btn-files" title="Arquivos"><i class="fas fa-folder-open"></i></a>`
- Detalhes: `<a href="#" class="btn-icon btn-details" title="Detalhes"><i class="fas fa-info-circle"></i></a>`
- Excluir: `<a href="#" class="btn-icon btn-delete" title="Excluir"><i class="fas fa-trash-alt"></i></a>`
- Voltar: `<a href="#" class="btn-icon btn-back" title="Voltar"><i class="fas fa-arrow-left"></i></a>`

### Tabelas
```html
<table class="improved-table">
    <thead>
        <tr>
            <th>Coluna 1</th>
            <th>Coluna 2</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Dado 1</td>
            <td>Dado 2</td>
        </tr>
    </tbody>
</table>
```

### Formulários
```html
<div class="form-container">
    <div class="form-header">
        <h2><i class="fas fa-icon"></i> Título do Formulário</h2>
    </div>
    
    <form method="POST" action="" class="improved-form">
        <div class="form-grid">
            <div class="form-group">
                <label for="campo1">
                    <i class="fas fa-icon"></i> Campo 1
                </label>
                <input type="text" id="campo1" name="campo1" class="form-control">
            </div>
            
            <div class="form-group full-width">
                <label for="campo2">
                    <i class="fas fa-icon"></i> Campo 2
                </label>
                <textarea id="campo2" name="campo2" class="form-control"></textarea>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="#" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Salvar
            </button>
        </div>
    </form>
</div>
```

### Modais
```html
<div id="myModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Título do Modal</h3>
        <div id="modalContent"></div>
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
            <button class="btn btn-primary" id="confirmBtn">Confirmar</button>
        </div>
    </div>
</div>
```

### Estados Vazios
```html
<div class="empty-state">
    <i class="fas fa-folder-open"></i>
    <p>Nenhum item encontrado.</p>
</div>
```
## JavaScript Utilities

### Sistema de Notificações
Localização: `assets/js/common.js`

O sistema possui funções para exibir notificações temporárias na interface. Estas funções estão definidas no arquivo `common.js` e podem ser utilizadas em qualquer página que inclua este arquivo.

#### Funções Disponíveis
- `showNotification(message, type)`: Exibe uma notificação com a mensagem e tipo especificados
- `closeNotification(notification)`: Fecha uma notificação específica (geralmente chamada automaticamente)

#### Tipos de Notificação
- `success`: Notificação de sucesso (ícone de check)
- `error`: Notificação de erro (ícone de X)
- `warning`: Notificação de aviso (ícone de exclamação)
- `info`: Notificação informativa (ícone de informação, padrão)

#### Exemplos de Uso
```javascript
// Notificação de sucesso
showNotification('Operação realizada com sucesso!', 'success');

// Notificação de erro
showNotification('Erro ao processar a solicitação.', 'error');

// Notificação de aviso
showNotification('Atenção: Alguns campos precisam ser revisados.', 'warning');

// Notificação informativa (padrão)
showNotification('O sistema será atualizado em breve.');
```

As notificações são exibidas no canto superior direito da tela, com animação de entrada e saída, e são fechadas automaticamente após 5 segundos ou manualmente pelo usuário.

## Sistema de Endereços com CEP

O sistema implementa um mecanismo avançado de gerenciamento de endereços para clientes, com busca automática por CEP e armazenamento em campos separados.

### Estrutura de Dados
Os endereços são armazenados nos seguintes campos na tabela `clientes`:
- `endereco`: Campo de texto completo (mantido para compatibilidade)
- `cep`: CEP do endereço (VARCHAR(10))
- `logradouro`: Nome da rua, avenida, etc. (VARCHAR(255))
- `numero`: Número do endereço (VARCHAR(20))
- `complemento`: Complemento do endereço (VARCHAR(255))
- `bairro`: Bairro do endereço (VARCHAR(100))
- `cidade`: Cidade do endereço (VARCHAR(100))
- `estado`: Estado do endereço (VARCHAR(2))

### Índices para Busca
Foram criados índices para otimizar buscas por:
- CEP (`idx_cliente_cep`)
- Cidade (`idx_cliente_cidade`)
- Estado (`idx_cliente_estado`)

### Funcionalidades
- **Busca por CEP**: Integração com a API ViaCEP para preenchimento automático de endereços
- **Campos Separados**: Cada componente do endereço tem seu próprio campo
- **Compatibilidade**: O campo `endereco` original é mantido e preenchido automaticamente
- **Buscas Avançadas**: Possibilidade de buscar clientes por cidade ou estado

### Implementação
- Formulários de criação e edição de clientes organizados em seções
- Layout responsivo com CSS flexbox
- Validação de campos no lado do cliente
- Preenchimento automático via API

### Exemplo de Uso da API ViaCEP
```javascript
// Função para buscar endereço pelo CEP
function buscarEnderecoPorCEP(cep) {
    // Remove caracteres não numéricos
    cep = cep.replace(/\D/g, '');
    
    if (cep.length !== 8) {
        alert('CEP inválido. O CEP deve conter 8 dígitos.');
        return;
    }
    
    // Fazer requisição para a API ViaCEP
    fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(response => response.json())
        .then(data => {
            if (data.erro) {
                alert('CEP não encontrado.');
            } else {
                // Preencher os campos com os dados retornados
                document.getElementById('logradouro').value = data.logradouro;
                document.getElementById('bairro').value = data.bairro;
                document.getElementById('cidade').value = data.localidade;
                document.getElementById('estado').value = data.uf;
            }
        });
}
```