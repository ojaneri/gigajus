# GigaJus

Sistema completo de gerenciamento para escritórios de advocacia, com integração a APIs judiciais, controle de processos e gestão de clientes.

## Funcionalidades Principais

- **Gerenciamento de Clientes**
  - Cadastro completo com busca automática de endereço por CEP
  - Armazenamento de documentos por cliente
  - Campos personalizáveis para informações adicionais

- **Controle de Processos**
  - Acompanhamento de processos judiciais
  - Vinculação de processos a clientes
  - Histórico de movimentações

- **Notificações Judiciais**
  - Integração com APIs de tribunais
  - Monitoramento automático de publicações
  - Alertas de novos andamentos

- **Agenda de Compromissos**
  - Calendário de audiências e prazos
  - Lembretes automáticos
  - Vinculação a processos e clientes

- **Gestão de Documentos**
  - Upload e organização de arquivos
  - Categorização por cliente e processo
  - Controle de versões

## Arquitetura do Sistema

### Principais Arquivos
- `config.php` - Configurações do banco de dados e constantes do sistema
- `create_tables.sql` - Esquema inicial do banco de dados
- `comunicaapi.py` - Integração com APIs externas
- `includes/notifications_helper.php` - Lógica de integração com API do PJE
- `process_notification.php` - Processamento de notificações

### Estrutura de Diretórios
- Arquivos de clientes: `uploads/clientes/Nome do cliente (ID X)/`
- Arquivos CSS: `assets/css/`
- Arquivos JavaScript: `assets/js/`
- Scripts de manutenção: `tools/`

## Estrutura do Banco de Dados

O sistema utiliza um banco de dados MySQL com as seguintes tabelas principais:

### Processos
```sql
CREATE TABLE processos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    numero_processo VARCHAR(255) UNIQUE,
    teor TEXT,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('ativo', 'arquivado') DEFAULT 'ativo'
);
```

### Clientes
```sql
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
```

### Notificações
```sql
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
```

## Sistema de Endereços com CEP

O sistema implementa um mecanismo avançado de gerenciamento de endereços para clientes, com busca automática por CEP e armazenamento em campos separados.

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

## API de Comunicações Judiciais

O sistema integra-se com APIs de tribunais para monitoramento automático de publicações judiciais.

### Endpoint Principal
```
https://comunicaapi.pje.jus.br/api/v1/comunicacao
```

### Parâmetros Principais
| Parâmetro                  | Tipo   | Obrigatório | Descrição                          |
|----------------------------|--------|-------------|------------------------------------|
| dataDisponibilizacaoInicio | date   | Sim         | Data inicial de busca (YYYY-MM-DD) |
| dataDisponibilizacaoFim    | date   | Sim         | Data final de busca (YYYY-MM-DD)   |
| siglaTribunal              | string | Sim         | Sigla do tribunal (ex: TJCE)       |
| pagina                     | int    | Não         | Número da página (padrão: 1)       |
| tamanhoPagina              | int    | Não         | Itens por página (padrão: 100)     |

## Padrões de Design e Estilo

### Padrões de Cores
- Azul claro (processos): `rgba(52, 152, 219, 0.1)` - `#3498db`
- Rosa claro (atendimentos vinculados): `rgba(231, 76, 60, 0.1)` - `#e74c3c`
- Verde claro (atendimentos não vinculados): `rgba(46, 204, 113, 0.1)` - `#2ecc71`
- Amarelo (arquivos): `rgba(241, 196, 15, 0.1)` - `#f39c12`

### Componentes de Interface
- Tabelas responsivas com cabeçalhos fixos
- Formulários organizados em seções com layout flexbox
- Sistema de notificações temporárias
- Modais para confirmações e ações críticas

## Serviços Ativos
- Monitoramento de logs: `tail -f gigajus.log`
- Sincronização com Google Drive (30min)
- Processamento automático de notificações
- Verificação periódica de token (cron job)

## Controle de Versão

Este projeto segue o versionamento semântico (SemVer).

Para salvar alterações e atualizar a versão, utilize o script `salvar-git.sh`:

```bash
./salvar-git.sh
```

O script irá:
1. Mostrar a versão atual
2. Solicitar a nova versão
3. Pedir uma descrição das alterações
4. Salvar as mudanças no git
5. Criar uma tag com a nova versão
6. Enviar as alterações para o repositório remoto

## Versão Atual
v0.5.2 - Implementação do sistema de endereços com busca por CEP
