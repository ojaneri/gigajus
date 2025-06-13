# Atualização do Sistema de Endereços

Este documento descreve as alterações realizadas no sistema de endereços dos clientes e fornece instruções para implementação.

## Visão Geral

O sistema de endereços foi melhorado para:

1. Permitir a busca automática de endereços a partir do CEP
2. Armazenar os componentes do endereço em campos separados no banco de dados
3. Facilitar buscas por cidade e estado
4. Manter compatibilidade com o sistema anterior

## Alterações no Banco de Dados

Foram adicionados os seguintes campos à tabela `clientes`:

- `cep`: CEP do endereço (VARCHAR(10))
- `logradouro`: Nome da rua, avenida, etc. (VARCHAR(255))
- `numero`: Número do endereço (VARCHAR(20))
- `complemento`: Complemento do endereço (VARCHAR(255))
- `bairro`: Bairro do endereço (VARCHAR(100))
- `cidade`: Cidade do endereço (VARCHAR(100))
- `estado`: Estado do endereço (VARCHAR(2))

Também foram criados índices para facilitar buscas por:
- CEP
- Cidade
- Estado

## Arquivos Modificados

1. `edit_client.php`: Atualizado para usar o novo sistema de endereços
2. `create_client.php`: Atualizado para usar o novo sistema de endereços

## Novos Arquivos

1. `tools/add_address_fields.sql`: Script SQL para adicionar os novos campos ao banco de dados
2. `tools/update_address_structure.php`: Script PHP para aplicar as alterações no banco de dados e migrar dados existentes

## Instruções de Implementação

### 1. Backup do Banco de Dados

Antes de iniciar, faça um backup completo do banco de dados:

```bash
mysqldump -u [usuario] -p [nome_do_banco] > backup_antes_atualizacao_endereco.sql
```

### 2. Aplicar Alterações no Banco de Dados

Execute o script de atualização da estrutura de endereços:

```bash
php tools/update_address_structure.php
```

Este script irá:
- Adicionar os novos campos à tabela `clientes`
- Criar índices para busca
- Tentar migrar dados existentes para o novo formato

### 3. Atualizar os Arquivos PHP

Os arquivos `edit_client.php` e `create_client.php` já foram atualizados para usar o novo sistema de endereços.

### 4. Testar o Sistema

1. Teste a criação de um novo cliente:
   - Verifique se a busca por CEP está funcionando
   - Verifique se os campos são preenchidos corretamente
   - Verifique se o cliente é salvo com todos os dados de endereço

2. Teste a edição de um cliente existente:
   - Verifique se os dados de endereço são exibidos corretamente
   - Verifique se a busca por CEP está funcionando
   - Verifique se as alterações são salvas corretamente

## Funcionalidades do Novo Sistema

### Busca por CEP

O sistema agora permite buscar automaticamente o endereço a partir do CEP, utilizando a API ViaCEP. Quando o usuário digita um CEP e clica em "Buscar", o sistema preenche automaticamente:

- Logradouro (rua, avenida, etc.)
- Bairro
- Cidade
- Estado

O usuário precisa apenas completar o número e o complemento, se necessário.

### Compatibilidade com o Sistema Anterior

Para manter a compatibilidade com o sistema anterior, o campo `endereco` continua sendo preenchido com o endereço completo, no formato:

```
Logradouro, Número, Complemento, Bairro - Cidade/Estado - CEP: 00000-000
```

### Buscas por Cidade e Estado

Com os novos campos, é possível realizar buscas por cidade e estado de forma mais eficiente. Por exemplo:

```sql
-- Buscar todos os clientes de uma cidade específica
SELECT * FROM clientes WHERE cidade = 'São Paulo';

-- Buscar todos os clientes de um estado específico
SELECT * FROM clientes WHERE estado = 'SP';
```

## Solução de Problemas

Se encontrar problemas durante a implementação, verifique:

1. Se o script de atualização foi executado com sucesso
2. Se os novos campos foram adicionados à tabela `clientes`
3. Se os arquivos PHP foram atualizados corretamente
4. Se a conexão com a API ViaCEP está funcionando (pode ser bloqueada por firewalls)

Para verificar se os campos foram adicionados corretamente:

```sql
SHOW COLUMNS FROM clientes;
```

Para verificar se os índices foram criados corretamente:

```sql
SHOW INDEX FROM clientes;