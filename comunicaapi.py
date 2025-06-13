#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Script para consultar a API de intimações judiciais e salvar no banco de dados.
Este script pode ser executado periodicamente via cron para manter o banco de dados atualizado.

Uso:
    python3 comunicaapi.py [--classe CLASSE] [--termo TERMO] [--tribunal TRIBUNAL] 
                          [--oab_numero OAB_NUMERO] [--oab_uf OAB_UF] [--dias DIAS]
                          [--verbose] [--config CONFIG_FILE]

Exemplos:
    python3 comunicaapi.py --oab_numero 123456 --oab_uf CE
    python3 comunicaapi.py --tribunal STF --dias 7
    python3 comunicaapi.py --classe "Habeas Corpus" --termo "João Silva"
"""

import argparse
import configparser
import json
import logging
import mysql.connector
import os
import requests
import sys
from datetime import datetime, timedelta

# Verifica se o suporte a SOCKS está instalado
try:
    import socks
except ImportError:
    logging.warning("Pacote 'PySocks' não encontrado. Suporte a proxy SOCKS5 pode não funcionar.")
    logging.warning("Instale com: pip install PySocks")

# Configuração do logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler("gigajus.log"),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger("comunicaapi")

def carregar_configuracao(config_file="config.ini"):
    """Carrega as configurações do arquivo config.ini"""
    config = configparser.ConfigParser()
    
    # Verifica se o arquivo de configuração existe
    if not os.path.exists(config_file):
        logger.error(f"Arquivo de configuração {config_file} não encontrado.")
        sys.exit(1)
    
    config.read(config_file)
    
    # Verifica se as seções necessárias existem
    if not config.has_section('Database'):
        logger.error("Arquivo de configuração não contém a seção necessária (Database).")
        sys.exit(1)
    
    # Verifica se a seção API existe, se não, cria com valores padrão
    if not config.has_section('API'):
        config.add_section('API')
        config['API']['url'] = 'https://comunicaapi.pje.jus.br/api/v1/comunicacao'
        logger.warning("Seção API não encontrada no arquivo de configuração. Usando valores padrão.")
    
    return config

def conectar_banco(config):
    """Conecta ao banco de dados MySQL usando as configurações fornecidas"""
    try:
        conn = mysql.connector.connect(
            host=config['Database']['host'],
            user=config['Database']['user'],
            password=config['Database']['password'],
            database=config['Database']['database']
        )
        return conn
    except mysql.connector.Error as err:
        logger.error(f"Erro ao conectar ao banco de dados: {err}")
        sys.exit(1)

def consultar_api(config, filtros):
    """Consulta a API de intimações com os filtros fornecidos"""
    api_url = config['API']['url']
    
    # Prepara os parâmetros da requisição
    params = {}
    
    # Adiciona o parâmetro meio (D = Diário)
    params['meio'] = 'D'
    
    # Adiciona parâmetros de paginação
    params['pagina'] = 1
    params['tamanhoPagina'] = 100
    
    # Mapeia os filtros para os novos parâmetros da API
    if 'classe' in filtros and filtros['classe']:
        params['classe'] = filtros['classe']
    
    if 'texto' in filtros and filtros['texto']:
        params['texto'] = filtros['texto']
    
    if 'tribunal' in filtros and filtros['tribunal']:
        params['siglaTribunal'] = filtros['tribunal']
    
    if 'numeroOab' in filtros and filtros['numeroOab']:
        params['numeroOab'] = filtros['numeroOab']
    
    if 'ufOab' in filtros and filtros['ufOab']:
        params['ufOab'] = filtros['ufOab']
    
    if 'dataInicio' in filtros and filtros['dataInicio']:
        params['dataDisponibilizacaoInicio'] = filtros['dataInicio']
    
    if 'dataFim' in filtros and filtros['dataFim']:
        params['dataDisponibilizacaoFim'] = filtros['dataFim']
    
    # Remove parâmetros vazios
    params = {k: v for k, v in params.items() if v}
    
    logger.info(f"Consultando API com os seguintes filtros: {params}")
    
    # Configuração do proxy SOCKS5 - lê do arquivo config.ini
    proxy_host = config['ProxySocks']['host'] if config.has_option('ProxySocks', 'host') else '200.234.178.126'
    proxy_port = config['ProxySocks']['port'] if config.has_option('ProxySocks', 'port') else '59101'
    proxy_auth = config['ProxySocks']['auth'] if config.has_option('ProxySocks', 'auth') else 'janeri:aM9z7EhhbR'
    
    proxies = {
        'http': f'socks5://{proxy_auth}@{proxy_host}:{proxy_port}',
        'https': f'socks5://{proxy_auth}@{proxy_host}:{proxy_port}'
    }
    
    try:
        response = requests.get(api_url, params=params, proxies=proxies, timeout=30)
        response.raise_for_status()  # Levanta exceção para status codes de erro
        
        data = response.json()
        
        # Verifica o formato da resposta e extrai os itens
        if isinstance(data, dict):
            # Formato com status, message, count, items
            if 'items' in data and isinstance(data['items'], list):
                logger.info(f"API retornou {len(data['items'])} intimações no formato 'items'.")
                return data['items']
            # Formato com content (paginado)
            elif 'content' in data and isinstance(data['content'], list):
                logger.info(f"API retornou {len(data['content'])} intimações no formato 'content'.")
                return data['content']
            else:
                logger.warning(f"Formato de resposta desconhecido: {list(data.keys())}")
                return []
        elif isinstance(data, list):
            # Formato de array direto
            logger.info(f"API retornou {len(data)} intimações no formato de array.")
            return data
        else:
            logger.warning(f"Tipo de resposta desconhecido: {type(data)}")
            return []
    except requests.exceptions.RequestException as err:
        logger.error(f"Erro ao consultar API: {err}")
        return []
    except json.JSONDecodeError as err:
        logger.error(f"Erro ao decodificar resposta JSON: {err}")
        # Registra a resposta bruta para debug
        logger.error(f"Resposta bruta: {response.text[:500]}...")
        return []

def salvar_intimacoes(conn, intimacoes):
    """Salva as intimações no banco de dados"""
    count_novas = 0
    count_existentes = 0
    
    cursor = conn.cursor(dictionary=True)
    
    for intimacao in intimacoes:
        # Verifica se tem o campo numero_processo
        if 'numero_processo' not in intimacao:
            logger.warning(f"Ignorando intimação sem número de processo: {intimacao}")
            continue
            
        # Obtém os campos com valores padrão para campos ausentes
        numero_processo = intimacao['numero_processo']
        classe = intimacao.get('classe', intimacao.get('nomeClasse', 'Desconhecida'))
        tribunal = intimacao.get('tribunal', intimacao.get('siglaTribunal', 'Desconhecido'))
        data_publicacao = intimacao.get('data_publicacao', intimacao.get('dataDisponibilizacao', datetime.now().strftime('%Y-%m-%d')))
        advogados = intimacao.get('advogados', '')
        
        # Log detalhado para debug
        logger.debug(f"Processando intimação: numero={numero_processo}, classe={classe}, tribunal={tribunal}")
        
        # Verifica se o processo já existe
        cursor.execute(
            "SELECT id FROM processes WHERE numero_processo = %s",
            (numero_processo,)
        )
        processo = cursor.fetchone()
        
        # Se o processo não existe, cria um novo
        if not processo:
            cursor.execute(
                "INSERT INTO processes (numero_processo, classe, tribunal) VALUES (%s, %s, %s)",
                (numero_processo, classe, tribunal)
            )
            processo_id = cursor.lastrowid
            logger.debug(f"Novo processo criado: {numero_processo}")
        else:
            processo_id = processo['id']
        
        # Verifica se a intimação já existe
        cursor.execute(
            "SELECT id FROM notifications WHERE numero_processo = %s AND data_publicacao = %s",
            (numero_processo, data_publicacao)
        )
        notificacao = cursor.fetchone()
        
        # Se a intimação não existe, cria uma nova
        if not notificacao:
            # Verifica se existe o campo teor na intimação
            teor = intimacao.get('teor', intimacao.get('conteudo', intimacao.get('texto', '')))
            
            # Verifica se a tabela notifications tem a coluna teor
            cursor.execute("SHOW COLUMNS FROM notifications LIKE 'teor'")
            has_teor_column = cursor.fetchone() is not None
            
            try:
                if has_teor_column:
                    # Se a coluna teor existe, inclui no INSERT
                    cursor.execute(
                        """INSERT INTO notifications
                           (processo_id, numero_processo, classe, advogados, tribunal, data_publicacao, teor)
                           VALUES (%s, %s, %s, %s, %s, %s, %s)""",
                        (
                            processo_id,
                            numero_processo,
                            classe,
                            advogados,
                            tribunal,
                            data_publicacao,
                            teor
                        )
                    )
                else:
                    # Se a coluna não existe, usa o INSERT original
                    cursor.execute(
                        """INSERT INTO notifications
                           (processo_id, numero_processo, classe, advogados, tribunal, data_publicacao)
                           VALUES (%s, %s, %s, %s, %s, %s)""",
                        (
                            processo_id,
                            numero_processo,
                            classe,
                            advogados,
                            tribunal,
                            data_publicacao
                        )
                    )
                count_novas += 1
                logger.debug(f"Nova intimação salva: {numero_processo} - {data_publicacao}")
            except mysql.connector.Error as err:
                logger.error(f"Erro ao inserir intimação: {err}")
                logger.error(f"Dados da intimação: {intimacao}")
        else:
            count_existentes += 1
            logger.debug(f"Intimação já existente: {numero_processo} - {data_publicacao}")
    
    conn.commit()
    cursor.close()
    
    return count_novas, count_existentes

def main():
    """Função principal do script"""
    # Parse dos argumentos da linha de comando
    parser = argparse.ArgumentParser(description='Consulta API de intimações judiciais e salva no banco de dados.')
    parser.add_argument('--classe', help='Classe processual (ex: Habeas Corpus)')
    parser.add_argument('--termo', help='Termo de busca (nome da parte, número do processo, etc)')
    parser.add_argument('--tribunal', help='Tribunal (ex: STF, STJ, TJCE)')
    parser.add_argument('--oab_numero', help='Número da OAB')
    parser.add_argument('--oab_uf', help='UF da OAB (ex: CE, SP, RJ)')
    parser.add_argument('--dias', type=int, default=30, help='Número de dias para buscar (padrão: 30)')
    parser.add_argument('--verbose', action='store_true', help='Modo verboso')
    parser.add_argument('--config', default='config.ini', help='Arquivo de configuração (padrão: config.ini)')
    
    args = parser.parse_args()
    
    # Configura o nível de log com base no modo verboso
    if args.verbose:
        logger.setLevel(logging.DEBUG)
    
    # Carrega as configurações
    config = carregar_configuracao(args.config)
    
    # Conecta ao banco de dados
    conn = conectar_banco(config)
    
    # Prepara os filtros para a consulta
    data_inicio = (datetime.now() - timedelta(days=args.dias)).strftime('%Y-%m-%d')
    data_fim = datetime.now().strftime('%Y-%m-%d')
    
    filtros = {}
    
    # Adiciona o parâmetro meio (D = Diário)
    filtros['meio'] = 'D'
    
    # Adiciona parâmetros de paginação
    filtros['pagina'] = 1
    filtros['tamanhoPagina'] = 100
    
    if args.classe:
        filtros['classe'] = args.classe
    
    if args.termo:
        filtros['texto'] = args.termo
    
    if args.tribunal:
        filtros['siglaTribunal'] = args.tribunal
    
    if args.oab_numero:
        filtros['numeroOab'] = args.oab_numero
    
    if args.oab_uf:
        filtros['ufOab'] = args.oab_uf
    
    if data_inicio and data_fim:
        filtros['dataDisponibilizacaoInicio'] = data_inicio
        filtros['dataDisponibilizacaoFim'] = data_fim
    
    # Consulta a API
    intimacoes = consultar_api(config, filtros)
    
    if intimacoes:
        # Processa as intimações para extrair o teor se disponível
        processed_intimacoes = []
        for intimacao in intimacoes:
            # Verifica se intimacao é um dicionário
            if isinstance(intimacao, dict):
                # Extrai o teor da intimação se disponível
                if 'teor' not in intimacao:
                    teor = ''
                    if 'conteudo' in intimacao:
                        teor = intimacao['conteudo']
                    elif 'texto' in intimacao:
                        teor = intimacao['texto']
                    intimacao['teor'] = teor
                processed_intimacoes.append(intimacao)
            else:
                # Se não for um dicionário, registra um aviso e pula
                logger.warning(f"Ignorando intimação inválida (não é um dicionário): {intimacao}")
        
        # Salva as intimações no banco de dados
        count_novas, count_existentes = salvar_intimacoes(conn, processed_intimacoes)
        logger.info(f"Intimações processadas: {len(processed_intimacoes)}")
        logger.info(f"Novas intimações: {count_novas}")
        logger.info(f"Intimações já existentes: {count_existentes}")
    else:
        logger.info("Nenhuma intimação encontrada ou erro na consulta.")
    
    # Fecha a conexão com o banco de dados
    conn.close()
    logger.info("Processamento concluído.")

if __name__ == "__main__":
    main()
