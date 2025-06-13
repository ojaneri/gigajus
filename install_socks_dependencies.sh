#!/bin/bash
# Script para instalar as dependências necessárias para o suporte a proxy SOCKS5
# Autor: Osvaldo Janeri Filho
# Data: 2025-05-15

echo "Instalando dependências para suporte a proxy SOCKS5..."

# Verifica se pip está instalado
if ! command -v pip &> /dev/null; then
    echo "pip não encontrado. Instalando pip..."
    apt-get update
    apt-get install -y python3-pip
fi

# Instala as dependências Python
echo "Instalando PySocks para suporte a proxy SOCKS5 no Python..."
pip install PySocks requests[socks]

# Verifica se a extensão CURL está instalada no PHP
if php -m | grep -q "curl"; then
    echo "Extensão CURL do PHP já está instalada."
else
    echo "Instalando extensão CURL do PHP..."
    apt-get update
    apt-get install -y php-curl
    # Reinicia o serviço Apache se estiver em execução
    if systemctl is-active --quiet apache2; then
        systemctl restart apache2
    fi
fi

echo "Verificando configuração do PHP..."
# Verifica se o PHP está configurado para usar SOCKS5
php -r "echo 'Suporte a CURL: ' . (function_exists('curl_init') ? 'SIM' : 'NÃO') . PHP_EOL;"
php -r "echo 'Versão do CURL: ' . curl_version()['version'] . PHP_EOL;"
php -r "echo 'Suporte a SOCKS5: ' . (defined('CURLPROXY_SOCKS5') ? 'SIM' : 'NÃO') . PHP_EOL;"

echo "Instalação concluída!"
echo "Para testar a configuração do proxy SOCKS5, acesse: http://seu-servidor/gigajus/v2/test_socks5_proxy.php"