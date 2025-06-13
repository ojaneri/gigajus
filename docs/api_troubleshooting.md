# Solução de Problemas da API de Intimações

## Problema Atual

Atualmente, estamos enfrentando problemas ao acessar a API de intimações judiciais. O script de diagnóstico `check_api_status.php` identificou os seguintes problemas:

1. **Acesso direto à API**: Retorna erro 403 (Forbidden) - "The Amazon CloudFront distribution is configured to block access from your country."
   - Isso indica que a API está bloqueando acessos de fora do Brasil ou de IPs não autorizados.

2. **Acesso via proxy SOCKS5**: Retorna erro 500 (Server Error)
   - O proxy SOCKS5 está funcionando corretamente, mas a API está retornando um erro interno do servidor.

## Possíveis Causas

1. **Restrições de IP**: A API pode ter implementado novas restrições de acesso baseadas em IP.
2. **Problemas no servidor da API**: O servidor da API pode estar enfrentando problemas técnicos.
3. **Mudanças na autenticação**: A API pode ter alterado o método de autenticação ou os parâmetros necessários.
4. **Problemas com o proxy**: Embora o proxy esteja funcionando para sites normais, pode haver alguma configuração específica que está causando problemas com a API.

## Soluções Implementadas

1. **Melhorias no tratamento de erros**: Foram implementadas melhorias no código para fornecer informações mais detalhadas sobre os erros.
2. **Script de diagnóstico**: Foi criado o script `check_api_status.php` para monitorar o status da API e do proxy.
3. **Logs detalhados**: Os logs foram aprimorados para incluir mais informações sobre as requisições e respostas.

## Próximos Passos

1. **Contatar o suporte da API**: Entrar em contato com o suporte técnico da API para verificar se houve mudanças recentes ou se há problemas conhecidos.
2. **Testar com outro proxy**: Configurar e testar um proxy alternativo para verificar se o problema está relacionado ao proxy atual.
3. **Verificar atualizações na documentação**: Verificar se houve atualizações na documentação da API que possam indicar mudanças nos parâmetros ou na autenticação.
4. **Implementar retry com backoff**: Adicionar um mecanismo de retry com backoff exponencial para lidar com falhas temporárias.

## Monitoramento

O script `check_api_status.php` foi configurado para monitorar o status da API e do proxy. Ele pode ser executado manualmente ou configurado para ser executado periodicamente via cron:

```bash
# Executar a cada hora e enviar notificação em caso de falha
0 * * * * /usr/bin/php /var/www/html/janeri.com.br/gigajus/v2/check_api_status.php --notify >> /var/www/html/janeri.com.br/gigajus/v2/logs/api_status.log 2>&1
```

## Contatos de Suporte

- **Suporte da API**: suporte@pje.jus.br
- **Suporte do Proxy**: suporte@provedor-proxy.com.br

## Histórico de Incidentes

| Data       | Descrição                                                | Resolução                                                |
|------------|----------------------------------------------------------|----------------------------------------------------------|
| 2025-05-16 | API retornando erro 500 via proxy e 403 diretamente      | Em andamento                                             |

## Referências

- [Documentação da API](https://comunicaapi.pje.jus.br/docs)
- [Documentação do Proxy SOCKS5](docs/proxy_socks5_setup.md)