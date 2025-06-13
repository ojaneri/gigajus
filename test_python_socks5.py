#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Script para testar a conexão com a API usando proxy SOCKS5 via Python
"""

import configparser
import requests
import sys
import json
from datetime import datetime, timedelta

# Tenta importar o módulo socks
try:
    import socks
    print("✅ Módulo PySocks encontrado!")
except ImportError:
    print("❌ Módulo PySocks não encontrado. Instale com: pip install PySocks")
    sys.exit(1)

def main():
    # Carrega as configurações
    config = configparser.ConfigParser()
    config.read('config.ini')
    
    # Verifica se a seção ProxySocks existe
    if not config.has_section('ProxySocks'):
        print("❌ Seção ProxySocks não encontrada no arquivo config.ini")
        sys.exit(1)
    
    # Obtém as configurações do proxy SOCKS5
    proxy_host = config['ProxySocks']['host']
    proxy_port = config['ProxySocks']['port']
    proxy_auth = config['ProxySocks']['auth']
    
    print(f"📋 Configuração do Proxy SOCKS5:")
    print(f"   Host: {proxy_host}")
    print(f"   Port: {proxy_port}")
    print(f"   Auth: {proxy_auth}")
    
    # Configura os proxies
    proxies = {
        'http': f'socks5://{proxy_auth}@{proxy_host}:{proxy_port}',
        'https': f'socks5://{proxy_auth}@{proxy_host}:{proxy_port}'
    }
    
    # URL da API
    api_url = config['API']['url'] if config.has_section('API') and 'url' in config['API'] else 'https://comunicaapi.pje.jus.br/api/v1/comunicacao'
    
    # Prepara os parâmetros da requisição
    data_inicio = (datetime.now() - timedelta(days=7)).strftime('%Y-%m-%d')
    data_fim = datetime.now().strftime('%Y-%m-%d')
    
    params = {
        'meio': 'D',
        'pagina': 1,
        'tamanhoPagina': 10,
        'dataDisponibilizacaoInicio': data_inicio,
        'dataDisponibilizacaoFim': data_fim
    }
    
    print(f"\n📡 Testando conexão com a API...")
    print(f"   URL: {api_url}")
    print(f"   Parâmetros: {params}")
    
    try:
        # Faz a requisição
        print("\n⏳ Enviando requisição...")
        response = requests.get(api_url, params=params, proxies=proxies, timeout=30)
        
        # Verifica o status code
        print(f"📊 Status Code: {response.status_code}")
        
        if response.status_code == 200:
            print("✅ Conexão bem-sucedida!")
            
            # Tenta decodificar o JSON
            try:
                data = response.json()
                
                # Verifica se é um objeto paginado
                if isinstance(data, dict) and 'content' in data:
                    print(f"📄 Resposta paginada com {len(data['content'])} itens")
                    print(f"   Total de elementos: {data.get('totalElements', 'N/A')}")
                    print(f"   Total de páginas: {data.get('totalPages', 'N/A')}")
                    
                    # Mostra os primeiros itens
                    if len(data['content']) > 0:
                        print("\n📝 Primeiro item:")
                        first_item = data['content'][0]
                        print(f"   Processo: {first_item.get('numero_processo', first_item.get('numeroprocesso', 'N/A'))}")
                        print(f"   Tribunal: {first_item.get('siglaTribunal', first_item.get('tribunal', 'N/A'))}")
                        print(f"   Data: {first_item.get('data_disponibilizacao', first_item.get('datadisponibilizacao', 'N/A'))}")
                else:
                    print(f"📄 Resposta com {len(data)} itens")
                    
                    # Mostra os primeiros itens
                    if len(data) > 0:
                        print("\n📝 Primeiro item:")
                        first_item = data[0]
                        print(f"   Processo: {first_item.get('numero_processo', first_item.get('numeroprocesso', 'N/A'))}")
                        print(f"   Tribunal: {first_item.get('siglaTribunal', first_item.get('tribunal', 'N/A'))}")
                        print(f"   Data: {first_item.get('data_disponibilizacao', first_item.get('datadisponibilizacao', 'N/A'))}")
                
                # Salva a resposta em um arquivo para análise
                with open('python_socks5_test_response.json', 'w', encoding='utf-8') as f:
                    json.dump(data, f, ensure_ascii=False, indent=2)
                print("\n💾 Resposta completa salva em python_socks5_test_response.json")
                
            except json.JSONDecodeError:
                print("❌ Erro ao decodificar JSON da resposta")
                print(f"Resposta: {response.text[:200]}...")
        else:
            print(f"❌ Erro na conexão: Status code {response.status_code}")
            print(f"Resposta: {response.text[:200]}...")
    
    except requests.exceptions.RequestException as e:
        print(f"❌ Erro na requisição: {e}")
    
    print("\n✨ Teste concluído!")

if __name__ == "__main__":
    main()