#!/bin/bash

# Script para salvar alterações no git com controle de versão
# Autor: Osvaldo Janeri Filho
# Data: 2025-06-13

# Cores para melhor visualização
VERDE='\033[0;32m'
AMARELO='\033[1;33m'
AZUL='\033[0;34m'
VERMELHO='\033[0;31m'
RESET='\033[0m'
# Função para lidar com problemas de propriedade duvidosa do repositório
handle_dubious_ownership() {
    local repo_path=$(git rev-parse --show-toplevel 2>/dev/null || echo $(pwd))
    
    echo -e "${AMARELO}Detectado problema de propriedade no repositório.${RESET}"
    echo -e "${AMARELO}Adicionando diretório à lista de diretórios seguros...${RESET}"
    
    git config --global --add safe.directory "$repo_path"
    
    if [ $? -eq 0 ]; then
        echo -e "${VERDE}Diretório adicionado com sucesso à lista de diretórios seguros.${RESET}"
        return 0
    else
        echo -e "${VERMELHO}Falha ao adicionar diretório à lista de diretórios seguros.${RESET}"
        return 1
    fi
}

# Verificar se o git está instalado
if ! command -v git &> /dev/null; then
    echo -e "${VERMELHO}Git não está instalado. Por favor, instale o git primeiro.${RESET}"
    exit 1
fi
# Verificar se estamos em um repositório git
if ! git rev-parse --is-inside-work-tree &> /dev/null; then
    # Se falhar devido a problemas de propriedade, tente corrigir
    if [[ $? -eq 128 && $(git rev-parse 2>&1) == *"dubious ownership"* ]]; then
        handle_dubious_ownership
    fi
    
    echo -e "${AMARELO}Inicializando um novo repositório git...${RESET}"
    git init
    
    # Se falhar novamente, tente corrigir
    if [[ $? -eq 128 && $(git rev-parse 2>&1) == *"dubious ownership"* ]]; then
        handle_dubious_ownership
        git init
    fi
fi

# Verificar se o remote já existe
git_remote_output=$(git remote 2>&1)
if [[ $? -eq 128 && $git_remote_output == *"dubious ownership"* ]]; then
    handle_dubious_ownership
    git_remote_output=$(git remote)
fi

if ! echo "$git_remote_output" | grep -q "origin"; then
    echo -e "${AMARELO}Adicionando repositório remoto ojaneri/gigajus...${RESET}"
    git remote add origin https://github.com/ojaneri/gigajus.git
else
    # Garantir que o remote origin está com a URL correta
    git remote set-url origin https://github.com/ojaneri/gigajus.git
    echo -e "${VERDE}URL do repositório remoto configurada: https://github.com/ojaneri/gigajus.git${RESET}"
fi

# Obter a versão atual
VERSAO_ATUAL=$(git describe --tags --abbrev=0 2>/dev/null || echo "Nenhuma versão encontrada")
if [[ $? -eq 128 && $(git describe 2>&1) == *"dubious ownership"* ]]; then
    handle_dubious_ownership
    VERSAO_ATUAL=$(git describe --tags --abbrev=0 2>/dev/null || echo "Nenhuma versão encontrada")
fi

if [[ $VERSAO_ATUAL == "Nenhuma versão encontrada" ]]; then
    echo -e "${AZUL}Nenhuma versão anterior encontrada. Esta será a primeira versão.${RESET}"
else
    echo -e "${VERDE}Versão atual: ${VERSAO_ATUAL}${RESET}"
fi

# Mostrar status atual do git
echo -e "\n${AZUL}Status atual do repositório:${RESET}"
git_status_output=$(git status -s 2>&1)
if [[ $? -eq 128 && $git_status_output == *"dubious ownership"* ]]; then
    handle_dubious_ownership
    git_status_output=$(git status -s)
fi
echo "$git_status_output"

# Perguntar pela nova versão
echo -e "\n${AMARELO}Digite a nova versão (ex: 1.0.0):${RESET}"
read NOVA_VERSAO

# Validar formato da versão (opcional)
if ! [[ $NOVA_VERSAO =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo -e "${VERMELHO}Formato de versão inválido. Use o formato X.Y.Z (ex: 1.0.0)${RESET}"
    exit 1
fi

# Perguntar pela descrição
echo -e "\n${AMARELO}Digite uma breve descrição das alterações:${RESET}"
read DESCRICAO

# Confirmar ação
echo -e "\n${AZUL}Resumo:${RESET}"
echo -e "Nova versão: ${VERDE}${NOVA_VERSAO}${RESET}"
echo -e "Descrição: ${VERDE}${DESCRICAO}${RESET}"
echo -e "\n${AMARELO}Confirmar? (s/n)${RESET}"
read CONFIRMA

if [[ $CONFIRMA != "s" && $CONFIRMA != "S" ]]; then
    echo -e "${VERMELHO}Operação cancelada pelo usuário.${RESET}"
    exit 0
fi

# Adicionar todas as alterações
echo -e "\n${AZUL}Adicionando alterações...${RESET}"
git_add_output=$(git add . 2>&1)
if [[ $? -eq 128 && $git_add_output == *"dubious ownership"* ]]; then
    handle_dubious_ownership
    git add .
fi

# Commit com a descrição
echo -e "\n${AZUL}Realizando commit...${RESET}"
git_commit_output=$(git commit -m "v${NOVA_VERSAO}: ${DESCRICAO}" 2>&1)
if [[ $? -eq 128 && $git_commit_output == *"dubious ownership"* ]]; then
    handle_dubious_ownership
    git commit -m "v${NOVA_VERSAO}: ${DESCRICAO}"
fi

# Criar tag com a nova versão
echo -e "\n${AZUL}Criando tag v${NOVA_VERSAO}...${RESET}"
git_tag_output=$(git tag -a "v${NOVA_VERSAO}" -m "${DESCRICAO}" 2>&1)
if [[ $? -eq 128 && $git_tag_output == *"dubious ownership"* ]]; then
    handle_dubious_ownership
    git tag -a "v${NOVA_VERSAO}" -m "${DESCRICAO}"
fi

# Verificar se o usuário quer fazer push
echo -e "\n${AMARELO}Deseja fazer push para o repositório remoto? (s/n)${RESET}"
read FAZER_PUSH

if [[ $FAZER_PUSH == "s" || $FAZER_PUSH == "S" ]]; then
    echo -e "\n${AZUL}Enviando alterações para o repositório remoto...${RESET}"
    
    # Verificar se o branch atual existe no remoto
    BRANCH_ATUAL=$(git symbolic-ref --short HEAD 2>&1)
    if [[ $? -eq 128 && $BRANCH_ATUAL == *"dubious ownership"* ]]; then
        handle_dubious_ownership
        BRANCH_ATUAL=$(git symbolic-ref --short HEAD)
    fi
    
    # Push do branch e das tags
    git_push_output=$(git push -u origin $BRANCH_ATUAL 2>&1)
    if [[ $? -eq 128 && $git_push_output == *"dubious ownership"* ]]; then
        handle_dubious_ownership
        git push -u origin $BRANCH_ATUAL
    fi
    
    git_push_tags_output=$(git push origin --tags 2>&1)
    if [[ $? -eq 128 && $git_push_tags_output == *"dubious ownership"* ]]; then
        handle_dubious_ownership
        git push origin --tags
    fi
    
    echo -e "\n${VERDE}Alterações enviadas com sucesso para o repositório remoto!${RESET}"
else
    echo -e "\n${AMARELO}Push não realizado. Use 'git push origin <branch>' e 'git push --tags' quando desejar enviar as alterações.${RESET}"
fi

echo -e "\n${VERDE}Processo concluído com sucesso!${RESET}"
echo -e "${VERDE}Versão ${NOVA_VERSAO} salva localmente.${RESET}"