#!/bin/bash

cd /var/www/html/janeri.com.br/gigajus/v2/

# Atualiza o arquivo com a data e hora atual em GMT-3
TZ='America/Sao_Paulo' date '+%Y-%m-%d %H:%M:%S' > /var/www/html/janeri.com.br/gigajus/v2/uploads/ultima_sincronizacao.txt

# Defina o diretório local e o destino do Google Drive
LOCAL_DIR="/var/www/html/janeri.com.br/gigajus/v2/uploads"
REMOTE_DIR="gdrive:/gigajus"

# Log de início
echo "Iniciando sincronização bidirecional: $(date)" >> /var/log/rclone-sync.log

# Usar bisync para sincronização bidirecional segura
echo "Realizando sincronização bidirecional entre o sistema local e o Google Drive..." >> /var/log/rclone-sync.log
rclone bisync "$LOCAL_DIR" "$REMOTE_DIR" --resync --create-empty-src-dirs --log-file=/var/log/rclone-sync.log --log-level=INFO

# Corrigir permissões após a sincronização
echo "Corrigindo permissões..." >> /var/log/rclone-sync.log
chown -R www-data:www-data "$LOCAL_DIR"

# Mensagem de conclusão
echo "Sincronização bidirecional concluída: $(date)" >> /var/log/rclone-sync.log
