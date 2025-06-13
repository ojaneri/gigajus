# API Updates - Comunicações Judiciais

## Resumo das Alterações

Este documento descreve as atualizações realizadas no sistema para compatibilidade com a nova versão da API de Comunicações Judiciais do PJE.

### Alterações nos Parâmetros da API

A API manteve o mesmo endpoint (`https://comunicaapi.pje.jus.br/api/v1/comunicacao`), mas houve mudanças nos nomes dos parâmetros:

| Parâmetro Antigo | Parâmetro Novo            | Descrição                    |
|------------------|---------------------------|------------------------------|
| dataInicio       | dataDisponibilizacaoInicio| Data inicial de disponibilização |
| dataFim          | dataDisponibilizacaoFim   | Data final de disponibilização  |
| tribunal         | siglaTribunal             | Sigla do tribunal            |
| -                | pagina                    | Número da página (paginação) |
| -                | tamanhoPagina             | Tamanho da página (paginação)|
| -                | meio                      | Meio de comunicação (D = Diário) |

### Arquivos Atualizados

1. **includes/notifications_helper.php**
   - Atualização da função `fetchNotificationsFromAPI` para usar os novos parâmetros
   - Melhoria no processamento da resposta da API para lidar com o formato paginado

2. **notifications.php**
   - Atualização dos painéis de debug para mostrar os novos parâmetros
   - Adição de links de teste para facilitar a verificação da API
   - Atualização do comando wget de fallback

3. **comunicaapi.py**
   - Atualização da função `consultar_api` para usar os novos parâmetros
   - Atualização da função `main` para preparar os filtros com os novos nomes de parâmetros

4. **test_api_curl.php** (Novo)
   - Script de teste que executa diretamente o comando curl fornecido
   - Exibe e analisa a resposta da API

## Como Testar

### Via Interface Web

1. Acesse a página de notificações: `notifications.php`
2. Use o formulário de filtros para buscar notificações
3. Clique no botão "Buscar na API" para testar a busca com os novos parâmetros
4. Verifique o painel de debug para confirmar que os parâmetros corretos estão sendo enviados

### Via Script de Teste

1. Acesse o script de teste: `test_api_curl.php`
2. O script executará o comando curl diretamente e exibirá os resultados
3. Os resultados completos serão salvos no arquivo `api_test_result.json`

### Via Linha de Comando

```bash
# Usando o script Python atualizado
python3 comunicaapi.py --tribunal TJCE --oab_numero 25695 --oab_uf CE

# Usando curl diretamente
curl -X GET "https://comunicaapi.pje.jus.br/api/v1/comunicacao?dataDisponibilizacaoInicio=2025-04-08&dataDisponibilizacaoFim=2025-04-08&siglaTribunal=&pagina=1&tamanhoPagina=100&meio=D" -H "Accept: application/json" --proxy "http://checaativos:Proxy2025@185.72.240.72:7108"
```

## Observações

- A API agora retorna resultados paginados, com a estrutura de resposta incluindo metadados de paginação
- O sistema foi adaptado para processar tanto o formato antigo quanto o novo formato de resposta
- Foram adicionados logs adicionais para facilitar a depuração em caso de problemas

## Próximos Passos

- Monitorar o funcionamento da integração com a nova versão da API
- Ajustar o tratamento de erros conforme necessário
- Considerar a implementação de paginação na interface do usuário para lidar com grandes volumes de dados