#!/bin/bash

# backup.sh
# Backup de arquivos + banco MySQL com suporte a modo debug (detalhamento passo-a-passo)
# Detecta credenciais em db.txt ou solicita interativamente, e salva se necessário

# Ativar modo debug se argumento for 'debug'
DEBUG=false
if [ "$1" == "debug" ]; then
    DEBUG=true
    echo "🛠️  MODO DEBUG ATIVADO"
fi

function debug_msg() {
    if $DEBUG; then
        echo -e "\n🔍 $1"
        read -p "Pressione [Enter] para continuar..."
    fi
}

# Data e diretórios
DATA_ATUAL=$(date +%Y-%m-%d)
DIR_ATUAL=$(pwd)
NOME_PROJETO=$(basename "$DIR_ATUAL")
NOME_BACKUP="backup-${NOME_PROJETO}-${DATA_ATUAL}.tgz"
DIR_TEMP="/tmp/backup-${NOME_PROJETO}-${DATA_ATUAL}"
DIR_DESTINO=$(dirname "$DIR_ATUAL")
ARQUIVO_CRED="${DIR_ATUAL}/db.txt"

debug_msg "DATA_ATUAL=$DATA_ATUAL\nDIR_ATUAL=$DIR_ATUAL\nNOME_PROJETO=$NOME_PROJETO\nDIR_DESTINO=$DIR_DESTINO"

# Executar zera-logs.sh se existir
if [ -x "./zera-logs.sh" ]; then
    echo "Executando zera-logs.sh..."
    ./zera-logs.sh
    echo "Logs limpos com sucesso."
    debug_msg "zera-logs.sh executado com sucesso"
else
    echo "AVISO: zera-logs.sh não encontrado ou não é executável."
    debug_msg "zera-logs.sh não foi executado"
fi

# Criar diretório temporário
mkdir -p "${DIR_TEMP}"
debug_msg "Diretório temporário criado: ${DIR_TEMP}"

# Copiar arquivos para backup
echo "Copiando arquivos do projeto..."
find "${DIR_ATUAL}" -type f -exec cp --parents {} "${DIR_TEMP}" \;
debug_msg "Arquivos copiados para ${DIR_TEMP}"

# Carregar ou solicitar credenciais
if [ -f "$ARQUIVO_CRED" ]; then
    echo "Lendo credenciais do banco de dados (db.txt)..."
    DB_HOST=$(grep -i "^host=" "$ARQUIVO_CRED" | cut -d'=' -f2)
    DB_USER=$(grep -i "^user=" "$ARQUIVO_CRED" | cut -d'=' -f2)
    DB_PASS=$(grep -i "^pass=" "$ARQUIVO_CRED" | cut -d'=' -f2)
    DB_NAME=$(grep -i "^dbname=" "$ARQUIVO_CRED" | cut -d'=' -f2)
    debug_msg "Credenciais carregadas:\nHOST=$DB_HOST\nUSER=$DB_USER\nDB=$DB_NAME"
else
    echo "Arquivo db.txt não encontrado. Informe os dados:"
    read -p "Host do banco (ex: localhost): " DB_HOST
    read -p "Usuário do banco: " DB_USER
    read -s -p "Senha do banco: " DB_PASS
    echo ""
    read -p "Nome do banco de dados: " DB_NAME

    echo "Salvando credenciais em ${ARQUIVO_CRED}..."
    cat > "$ARQUIVO_CRED" << EOF
host=${DB_HOST}
user=${DB_USER}
pass=${DB_PASS}
dbname=${DB_NAME}
EOF
    chmod 600 "$ARQUIVO_CRED"
    debug_msg "Credenciais salvas em ${ARQUIVO_CRED}"
fi

# Fazer dump do banco
if [ -n "$DB_NAME" ]; then
    echo "Exportando banco de dados ${DB_NAME}..."
    MYSQL_CNF="${DIR_TEMP}/.my.cnf"
    cat > "${MYSQL_CNF}" << EOF
[client]
host=${DB_HOST}
user=${DB_USER}
password=${DB_PASS}
EOF
    chmod 600 "${MYSQL_CNF}"

    mysqldump --defaults-file="${MYSQL_CNF}" --opt "${DB_NAME}" > "${DIR_TEMP}/${NOME_PROJETO}-${DATA_ATUAL}.sql" 2> /tmp/mysqldump.err
    DUMP_STATUS=$?
    rm -f "${MYSQL_CNF}"

    if [ $DUMP_STATUS -ne 0 ] || [ ! -s "${DIR_TEMP}/${NOME_PROJETO}-${DATA_ATUAL}.sql" ]; then
        echo "❌ ERRO: Falha ao exportar banco de dados!"
        echo "Saída de erro do mysqldump:"
        cat /tmp/mysqldump.err
        rm -f /tmp/mysqldump.err
        debug_msg "Erro durante o dump do banco"
    else
        LINHAS=$(wc -l < "${DIR_TEMP}/${NOME_PROJETO}-${DATA_ATUAL}.sql")
        echo "✅ Dump criado com $LINHAS linhas."
        debug_msg "Dump bem-sucedido:\nArquivo: ${DIR_TEMP}/${NOME_PROJETO}-${DATA_ATUAL}.sql"
        rm -f /tmp/mysqldump.err
    fi
else
    echo "AVISO: Nome do banco não definido. Dump ignorado."
    debug_msg "Variável DB_NAME vazia"
fi

# Compactar backup
echo "Compactando arquivos..."
tar -czf "${DIR_ATUAL}/${NOME_BACKUP}" -C "${DIR_TEMP}" .
debug_msg "Backup compactado: ${NOME_BACKUP}"

# Mover para diretório destino
mv "${DIR_ATUAL}/${NOME_BACKUP}" "${DIR_DESTINO}/"
debug_msg "Backup movido para ${DIR_DESTINO}/${NOME_BACKUP}"

# Limpar temporários
rm -rf "${DIR_TEMP}"
debug_msg "Diretório temporário ${DIR_TEMP} removido"

# Verificar resultado final
if [ -f "${DIR_DESTINO}/${NOME_BACKUP}" ]; then
    TAMANHO=$(du -h "${DIR_DESTINO}/${NOME_BACKUP}" | cut -f1)
    echo "✅ Backup finalizado com sucesso!"
    echo "📦 Arquivo: ${DIR_DESTINO}/${NOME_BACKUP}"
    echo "📏 Tamanho: ${TAMANHO}"
    debug_msg "Arquivo final:\n${DIR_DESTINO}/${NOME_BACKUP} (${TAMANHO})"
else
    echo "❌ ERRO: O backup não foi criado!"
    debug_msg "Falha ao gerar backup final"
fi

