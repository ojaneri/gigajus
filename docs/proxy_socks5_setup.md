# Configuração de Proxy SOCKS5 para APIs Externas

## Visão Geral

Este documento descreve a configuração e uso do proxy SOCKS5 para comunicação com APIs externas no sistema GigaJus.

## Configuração

O sistema foi atualizado para usar proxy SOCKS5 para comunicação com a API de intimações judiciais. As configurações do proxy estão definidas nos seguintes arquivos:

1. `config.ini` - Contém as configurações de host, porta e autenticação do proxy
2. `config.php` - Define as constantes usadas pelos scripts PHP
3. `comunicaapi.py` - Script Python que usa o proxy SOCKS5 para comunicação com a API

### Parâmetros de Configuração

Os parâmetros de configuração do proxy SOCKS5 estão definidos em `config.ini`:

```ini
[ProxySocks]
host = 200.234.178.126
port = 59101
auth = janeri:aM9z7EhhbR
```

E em `config.php`:

```php
// Configurações de proxy SOCKS5 para APIs externas
define('PROXY_SOCKS_HOST', '200.234.178.126');
define('PROXY_SOCKS_PORT', '59101');
define('PROXY_SOCKS_AUTH', 'janeri:aM9z7EhhbR');
```

## Uso

### Linha de Comando (curl)

Para testar a conexão com a API usando o proxy SOCKS5 via linha de comando:

```bash
curl -v --socks5 200.234.178.126:59101 --proxy-user janeri:aM9z7EhhbR https://comunicaapi.pje.jus.br/api/v1/comunicacao
```

### PHP (cURL)

O código PHP para usar o proxy SOCKS5 com cURL:

```php
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $api_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_PROXY => PROXY_SOCKS_HOST,
    CURLOPT_PROXYPORT => PROXY_SOCKS_PORT,
    CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5,
    CURLOPT_PROXYUSERPWD => PROXY_SOCKS_AUTH
]);
$response = curl_exec($ch);
```

### Python (requests)

O código Python para usar o proxy SOCKS5 com a biblioteca requests:

```python
proxies = {
    'http': f'socks5://{proxy_auth}@{proxy_host}:{proxy_port}',
    'https': f'socks5://{proxy_auth}@{proxy_host}:{proxy_port}'
}
response = requests.get(api_url, params=params, proxies=proxies, timeout=30)
```

## Dependências

### PHP

Para usar o proxy SOCKS5 com PHP, é necessário que a extensão cURL esteja instalada e configurada.

### Python

Para usar o proxy SOCKS5 com Python, é necessário instalar o pacote PySocks:

```bash
pip install PySocks requests[socks]
```

## Testes

Para testar a configuração do proxy SOCKS5, acesse:

1. `test_socks5_proxy.php` - Testa a conexão com a API usando o proxy SOCKS5 via PHP
2. `python3 comunicaapi.py --verbose` - Testa a conexão com a API usando o proxy SOCKS5 via Python

## Solução de Problemas

### Erro de Conexão

Se ocorrer erro de conexão, verifique:

1. Se o proxy SOCKS5 está acessível (ping 200.234.178.126)
2. Se as credenciais de autenticação estão corretas
3. Se as dependências estão instaladas corretamente

### Logs

Os logs de erro são salvos em:

- `gigajus.log` - Log principal do sistema
- `api.log` - Log de comunicação com APIs externas

## Referências

- [Documentação do cURL - Proxy SOCKS5](https://curl.se/libcurl/c/CURLOPT_PROXY.html)
- [Documentação do Requests - SOCKS Proxy](https://requests.readthedocs.io/en/latest/user/advanced/#socks)